<?php

namespace App\Http\Requests;

use App\Models\BookingRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OfferArtistSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BookingRequest|null $bookingRequest */
        $bookingRequest = $this->route('bookingRequest');

        return $bookingRequest
            && (int) $bookingRequest->artist_id === (int) $this->user()->id;
    }

    public function rules(): array
    {
        /** @var BookingRequest $bookingRequest */
        $bookingRequest = $this->route('bookingRequest');
        $requiresConsult = $bookingRequest->hasConsultation();

        $slotRules = [
            'required',
            'array',
            'min:1',
        ];

        $rules = [
            'artist_session_slots' => $slotRules,
            'artist_session_slots.*.date' => ['required', 'date', 'after_or_equal:today'],
            'artist_session_slots.*.ranges' => ['required', 'array', 'min:1'],
            'artist_session_slots.*.ranges.*.from' => ['required', 'date_format:H:i'],
            'artist_session_slots.*.ranges.*.to' => ['required', 'date_format:H:i'],
            'artist_notes_to_client' => ['nullable', 'string', 'max:2000'],
        ];

        if ($requiresConsult) {
            $rules['artist_consultation_slots'] = $slotRules;
            $rules['artist_consultation_slots.*.date'] = ['required', 'date', 'after_or_equal:today'];
            $rules['artist_consultation_slots.*.ranges'] = ['required', 'array', 'min:1'];
            $rules['artist_consultation_slots.*.ranges.*.from'] = ['required', 'date_format:H:i'];
            $rules['artist_consultation_slots.*.ranges.*.to'] = ['required', 'date_format:H:i'];
        } else {
            $rules['artist_consultation_slots'] = ['nullable', 'array', 'max:0'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'artist_session_slots.required' => 'Add at least one tattoo session date with time windows.',
            'artist_session_slots.min' => 'Add at least one tattoo session date with time windows.',
            'artist_consultation_slots.required' => 'Add at least one consultation date with time windows.',
            'artist_consultation_slots.min' => 'Add at least one consultation date with time windows.',
            'artist_session_slots.*.date.after_or_equal' => 'Session dates must be today or in the future.',
            'artist_consultation_slots.*.date.after_or_equal' => 'Consultation dates must be today or in the future.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $sessionSlots = $this->input('artist_session_slots', []);
            $this->validateSlotCollection($validator, $sessionSlots, 'artist_session_slots', 'Tattoo session');

            /** @var BookingRequest $bookingRequest */
            $bookingRequest = $this->route('bookingRequest');
            if ($bookingRequest->hasConsultation()) {
                $consultSlots = $this->input('artist_consultation_slots', []);
                $this->validateSlotCollection($validator, $consultSlots, 'artist_consultation_slots', 'Consultation');
            }
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $slots
     */
    private function validateSlotCollection(Validator $validator, array $slots, string $prefix, string $label): void
    {
        $dates = [];

        foreach ($slots as $index => $slot) {
            $date = (string) ($slot['date'] ?? '');
            if ($date !== '') {
                if (isset($dates[$date])) {
                    $validator->errors()->add(
                        "{$prefix}.{$index}.date",
                        "{$label}: each date can only appear once."
                    );
                }
                $dates[$date] = true;
            }

            $ranges = is_array($slot['ranges'] ?? null) ? $slot['ranges'] : [];
            $parsed = [];

            foreach ($ranges as $rangeIndex => $range) {
                $from = (string) ($range['from'] ?? '');
                $to = (string) ($range['to'] ?? '');
                $start = $this->timeToMinutes($from);
                $end = $this->timeToMinutes($to);

                if ($start === null || $end === null || $start >= $end) {
                    $validator->errors()->add(
                        "{$prefix}.{$index}.ranges.{$rangeIndex}.from",
                        "{$label}: from time must be earlier than to time."
                    );
                    continue;
                }

                $parsed[] = ['start' => $start, 'end' => $end, 'rangeIndex' => $rangeIndex];
            }

            usort($parsed, fn (array $a, array $b) => $a['start'] <=> $b['start']);

            for ($i = 0; $i < count($parsed); $i++) {
                for ($j = $i + 1; $j < count($parsed); $j++) {
                    if ($parsed[$i]['start'] < $parsed[$j]['end'] && $parsed[$j]['start'] < $parsed[$i]['end']) {
                        $validator->errors()->add(
                            "{$prefix}.{$index}.ranges.{$parsed[$j]['rangeIndex']}.from",
                            "{$label}: time windows on the same date cannot overlap."
                        );
                        break 2;
                    }
                }
            }
        }
    }

    private function timeToMinutes(string $value): ?int
    {
        if ($value === '' || !str_contains($value, ':')) {
            return null;
        }

        try {
            $time = Carbon::createFromFormat('H:i', strlen($value) === 5 ? $value : substr($value, 0, 5));
        } catch (\Throwable) {
            return null;
        }

        return $time->hour * 60 + $time->minute;
    }

    /**
     * @return array{
     *     artist_session_slots: array<int, array{date: string, ranges: array<int, array{from: string, to: string}>}>,
     *     artist_consultation_slots: array<int, array{date: string, ranges: array<int, array{from: string, to: string}>}>|null,
     *     artist_notes_to_client: string|null
     * }
     */
    public function normalizedPayload(): array
    {
        /** @var BookingRequest $bookingRequest */
        $bookingRequest = $this->route('bookingRequest');

        $notes = $this->input('artist_notes_to_client');
        $notes = is_string($notes) && trim($notes) !== '' ? trim($notes) : null;

        return [
            'artist_session_slots' => $this->normalizeSlots($this->input('artist_session_slots', [])),
            'artist_consultation_slots' => $bookingRequest->hasConsultation()
                ? $this->normalizeSlots($this->input('artist_consultation_slots', []))
                : null,
            'artist_notes_to_client' => $notes,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $slots
     * @return array<int, array{date: string, ranges: array<int, array{from: string, to: string}>}>
     */
    private function normalizeSlots(array $slots): array
    {
        $normalized = [];

        foreach ($slots as $slot) {
            $date = (string) ($slot['date'] ?? '');
            if ($date === '') {
                continue;
            }

            $ranges = [];
            foreach ((array) ($slot['ranges'] ?? []) as $range) {
                $from = $this->normalizeTime((string) ($range['from'] ?? ''));
                $to = $this->normalizeTime((string) ($range['to'] ?? ''));
                if ($from === '' || $to === '') {
                    continue;
                }
                $ranges[] = ['from' => $from, 'to' => $to];
            }

            if ($ranges !== []) {
                $normalized[] = ['date' => $date, 'ranges' => $ranges];
            }
        }

        return $normalized;
    }

    private function normalizeTime(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (strlen($value) >= 5) {
            return substr($value, 0, 5);
        }

        return $value;
    }
}
