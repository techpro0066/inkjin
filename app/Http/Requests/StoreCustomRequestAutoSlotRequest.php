<?php

namespace App\Http\Requests;

use App\Models\CustomRequest;
use App\Services\BookingCalendarAvailabilityService;
use App\Services\ManagedRequestBookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCustomRequestAutoSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CustomRequest|null $customRequest */
        $customRequest = $this->route('customRequest');

        return $customRequest
            && (int) $customRequest->user_id === (int) $this->user()->id
            && $customRequest->canAccessConfirmTimesPage()
            && !$customRequest->usesArtistOfferedSlotsPicker();
    }

    public function rules(): array
    {
        /** @var CustomRequest $customRequest */
        $customRequest = $this->route('customRequest');
        $slotRules = [
            'client_session_slots' => ['required', 'array'],
            'client_session_slots.0.date' => ['required', 'date'],
            'client_session_slots.0.ranges' => ['required', 'array', 'min:1'],
            'client_session_slots.0.ranges.0.from' => ['required', 'date_format:H:i'],
            'client_session_slots.0.ranges.0.to' => ['required', 'date_format:H:i'],
        ];

        if ($customRequest->autoRequiresConsultation()) {
            $slotRules['client_consultation_slots'] = ['required', 'array'];
            $slotRules['client_consultation_slots.0.date'] = ['required', 'date'];
            $slotRules['client_consultation_slots.0.ranges'] = ['required', 'array', 'min:1'];
            $slotRules['client_consultation_slots.0.ranges.0.from'] = ['required', 'date_format:H:i'];
            $slotRules['client_consultation_slots.0.ranges.0.to'] = ['required', 'date_format:H:i'];
        }

        return $slotRules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var CustomRequest $customRequest */
            $customRequest = $this->route('customRequest');
            $customRequest->loadMissing(['artist.userDetail']);

            $calendar = app(BookingCalendarAvailabilityService::class);
            $payload = $calendar->calendarPayloadForCustomRequest($customRequest);
            $timezone = (string) ($payload['artistTimezone'] ?? 'UTC');
            $sessionDuration = (int) ($payload['tattooDurationMinutes'] ?? 120);
            $consultDuration = (int) ($payload['artistConsultationSettings']['session_duration_minutes'] ?? 30);
            $managed = app(ManagedRequestBookingService::class);

            $this->assertDateNotInPast(
                $validator,
                'client_session_slots',
                (string) $this->input('client_session_slots.0.date'),
                $timezone
            );

            $this->validateSlotGroup(
                $validator,
                'client_session_slots',
                $sessionDuration,
                $payload,
                $timezone,
                $managed,
                (int) $customRequest->artist_id
            );

            if (!$customRequest->autoRequiresConsultation()) {
                return;
            }

            $this->assertDateNotInPast(
                $validator,
                'client_consultation_slots',
                (string) $this->input('client_consultation_slots.0.date'),
                $timezone
            );

            $this->validateSlotGroup(
                $validator,
                'client_consultation_slots',
                $consultDuration,
                $payload,
                $timezone,
                $managed,
                (int) $customRequest->artist_id
            );

            if ($customRequest->autoConsultationTiming() !== 'separate') {
                return;
            }

            $consultDate = (string) $this->input('client_consultation_slots.0.date');
            $sessionDate = (string) $this->input('client_session_slots.0.date');
            $consultFrom = substr((string) $this->input('client_consultation_slots.0.ranges.0.from'), 0, 5);
            $sessionFrom = substr((string) $this->input('client_session_slots.0.ranges.0.from'), 0, 5);

            try {
                $consultStart = Carbon::createFromFormat('Y-m-d H:i', $consultDate.' '.$consultFrom, $timezone);
                $sessionStart = Carbon::createFromFormat('Y-m-d H:i', $sessionDate.' '.$sessionFrom, $timezone);
            } catch (\Throwable) {
                return;
            }

            $gapDays = max(0, (int) ($customRequest->artist?->userDetail?->consultation_tattoo_gap_value ?? 0));
            $minSessionDay = $consultStart->copy()->startOfDay()->addDays($gapDays + 1);
            if ($sessionStart->lt($minSessionDay)) {
                $validator->errors()->add('client_session_slots', 'Tattoo session must be scheduled after the required gap following consultation.');
            }
        });
    }

    private function assertDateNotInPast(Validator $validator, string $key, string $date, string $timezone): void
    {
        if ($date === '') {
            return;
        }

        try {
            $selectedDay = Carbon::createFromFormat('Y-m-d', $date, $timezone)->startOfDay();
            $todayInArtistTz = Carbon::now($timezone)->startOfDay();
        } catch (\Throwable) {
            $validator->errors()->add($key, 'Invalid date.');

            return;
        }

        if ($selectedDay->lt($todayInArtistTz)) {
            $validator->errors()->add($key, 'Selected date must be today or later in the artist\'s timezone.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateSlotGroup(
        Validator $validator,
        string $key,
        int $durationMinutes,
        array $payload,
        string $timezone,
        ManagedRequestBookingService $managed,
        int $artistId,
    ): void {
        $date = (string) $this->input($key.'.0.date');
        $from = substr((string) $this->input($key.'.0.ranges.0.from'), 0, 5);
        $to = substr((string) $this->input($key.'.0.ranges.0.to'), 0, 5);

        if ($from >= $to) {
            $validator->errors()->add($key, 'End time must be after start time.');

            return;
        }

        if ($managed->artistLocalDateIsBlocked($artistId, $date)) {
            $validator->errors()->add($key, 'This date is not available.');

            return;
        }

        try {
            $start = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$from, $timezone);
            $end = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$to, $timezone);
        } catch (\Throwable) {
            $validator->errors()->add($key, 'Invalid date or time.');

            return;
        }

        // Carbon 3: diffInMinutes is signed; use start → end (same as Booking model).
        if ($start->diffInMinutes($end) < $durationMinutes) {
            $validator->errors()->add($key, 'Selected time does not allow enough duration for this session.');

            return;
        }

        $startMinutes = $start->hour * 60 + $start->minute;
        if ($this->slotOverlapsBusy($payload['artistBusyIntervalsByDate'][$date] ?? [], $startMinutes, $durationMinutes)) {
            $validator->errors()->add($key, 'This time conflicts with another booking. Please choose another slot.');
        }
    }

    /**
     * @param  array<int, array{start: int, end: int}>  $busy
     */
    private function slotOverlapsBusy(array $busy, int $startMinutes, int $durationMinutes): bool
    {
        $endMinutes = $startMinutes + $durationMinutes;
        foreach ($busy as $interval) {
            $bs = (int) ($interval['start'] ?? 0);
            $be = (int) ($interval['end'] ?? 0);
            if ($startMinutes < $be && $endMinutes > $bs) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizedPayload(): array
    {
        /** @var CustomRequest $customRequest */
        $customRequest = $this->route('customRequest');
        $customRequest->loadMissing(['artist.userDetail']);

        $calendar = app(BookingCalendarAvailabilityService::class);
        $calendarPayload = $calendar->calendarPayloadForCustomRequest($customRequest);
        $sessionDuration = (int) ($calendarPayload['tattooDurationMinutes'] ?? $customRequest->sessionDurationMinutes());
        $consultDuration = (int) ($calendarPayload['artistConsultationSettings']['session_duration_minutes'] ?? 30);

        $payload = [
            'client_session_slots' => [$this->normalizeSlotInput('client_session_slots', $sessionDuration)],
            'client_consultation_slots' => null,
        ];

        if ($customRequest->autoRequiresConsultation() && $this->has('client_consultation_slots.0.date')) {
            $payload['client_consultation_slots'] = [$this->normalizeSlotInput('client_consultation_slots', $consultDuration)];
        }

        return $payload;
    }

    /**
     * Client picks a start time from the calendar; end time is derived from quote duration.
     *
     * @return array{date: string, ranges: array<int, array{from: string, to: string}>}
     */
    private function normalizeSlotInput(string $key, int $durationMinutes): array
    {
        $input = $this->input($key, []);
        $date = trim((string) ($input[0]['date'] ?? ''));
        $from = substr(trim((string) ($input[0]['ranges'][0]['from'] ?? '')), 0, 5);
        $to = $from;

        try {
            $to = Carbon::createFromFormat('H:i', $from)
                ->addMinutes(max(1, $durationMinutes))
                ->format('H:i');
        } catch (\Throwable) {
            // Keep submitted to if duration math fails.
        }

        return [
            'date' => $date,
            'ranges' => [['from' => $from, 'to' => $to]],
        ];
    }
}
