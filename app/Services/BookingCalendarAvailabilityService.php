<?php

namespace App\Services;

use App\Http\Controllers\GoogleCalendarController;
use App\Models\ArtistDesign;
use App\Models\Availability;
use App\Models\CustomRequest;
use App\Models\Booking;
use App\Models\UserDetail;
use Carbon\Carbon;

/**
 * Builds the same calendar / busy-slot payload used by the public book flow,
 * optionally excluding one or more bookings (e.g. the row being rescheduled).
 */
class BookingCalendarAvailabilityService
{
    /**
     * @return array<string, mixed>
     */
    public function calendarPayloadForReschedule(Booking $booking): array
    {
        $booking->loadMissing(['tattoo', 'artist.userDetail']);
        $artist = $booking->artist;
        $userDetail = $artist?->userDetail;
        if (! $artist || ! $userDetail) {
            throw new \RuntimeException('Booking artist data is missing.');
        }

        $artistUserId = (int) $artist->id;
        $artistTimezone = $booking->timezone ?: ($userDetail->timezone ?: 'UTC');

        $tattoo = $booking->tattoo;
        $tattooDurationMinutes = $this->resolveTattooDurationMinutes($tattoo);

        $artistAvailabilitySchedule = Availability::query()
            ->where('user_id', $artistUserId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week')
            ->map(function ($rows) use ($artistTimezone) {
                return $rows->map(function ($availability) use ($artistTimezone) {
                    $startLocal = Carbon::createFromFormat('Y-m-d H:i:s', now('UTC')->format('Y-m-d').' '.$availability->start_time, 'UTC')
                        ->setTimezone($artistTimezone)
                        ->format('H:i');
                    $endLocal = Carbon::createFromFormat('Y-m-d H:i:s', now('UTC')->format('Y-m-d').' '.$availability->end_time, 'UTC')
                        ->setTimezone($artistTimezone)
                        ->format('H:i');

                    return [
                        'start' => $startLocal,
                        'end' => $endLocal,
                    ];
                })->values()->all();
            })
            ->toArray();

        $artistBlockedPeriods = app(ManagedRequestBookingService::class)
            ->artistBlockedPeriods($artistUserId);

        $excludeIds = array_values(array_unique(array_filter([
            (int) $booking->id,
            $booking->consultation_booking_id ? (int) $booking->consultation_booking_id : null,
        ])));

        $from = Carbon::now($artistTimezone)->startOfDay();
        $to = $from->copy()->addDays(180)->endOfDay();
        $artistBusyIntervalsByDate = $this->busyIntervalsByDateForRange($userDetail, $from, $to, $excludeIds);

        $timing = strtolower((string) ($booking->consultation_timing_type ?? 'combined'));
        if ($timing !== 'separate') {
            $timing = 'combined';
        }

        return [
            'artistAvailabilitySchedule' => $artistAvailabilitySchedule,
            'artistTimezone' => $artistTimezone,
            'artistBlockedPeriods' => $artistBlockedPeriods,
            'artistBusyIntervalsByDate' => $artistBusyIntervalsByDate,
            'tattooDurationMinutes' => $tattooDurationMinutes,
            'artistConsultationSettings' => [
                'required' => (bool) ($userDetail->require_consultation ?? false),
                'timing' => $userDetail->consultation_timing ?: 'combined',
                'session_type' => $userDetail->session_type ?: 'both',
                'session_duration_minutes' => (int) ($userDetail->session_duration_minutes ?: 30),
                'require_gap' => (bool) ($userDetail->require_gap_between_consultation_tattoo ?? false),
                'gap_value' => (int) ($userDetail->consultation_tattoo_gap_value ?? 0),
                'gap_unit' => $userDetail->consultation_tattoo_gap_unit ?: 'hours',
            ],
            'booking' => [
                'id' => $booking->id,
                'has_consultation' => (bool) $booking->has_consultation,
                'consultation_timing_type' => $timing,
                'consultation_booking_id' => $booking->consultation_booking_id,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function calendarPayloadForCustomRequest(CustomRequest $customRequest): array
    {
        $customRequest->loadMissing(['artist.userDetail', 'guestSpot']);
        $artist = $customRequest->artist;
        $userDetail = $artist?->userDetail;
        if (!$artist || !$userDetail) {
            throw new \RuntimeException('Custom request artist data is missing.');
        }

        $artistUserId = (int) $artist->id;
        $artistTimezone = $userDetail->timezone ?: 'UTC';
        $tattooDurationMinutes = max(15, $customRequest->sessionDurationMinutes());

        $artistAvailabilitySchedule = Availability::query()
            ->where('user_id', $artistUserId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week')
            ->map(function ($rows) use ($artistTimezone) {
                return $rows->map(function ($availability) use ($artistTimezone) {
                    $startLocal = Carbon::createFromFormat('Y-m-d H:i:s', now('UTC')->format('Y-m-d').' '.$availability->start_time, 'UTC')
                        ->setTimezone($artistTimezone)
                        ->format('H:i');
                    $endLocal = Carbon::createFromFormat('Y-m-d H:i:s', now('UTC')->format('Y-m-d').' '.$availability->end_time, 'UTC')
                        ->setTimezone($artistTimezone)
                        ->format('H:i');

                    return [
                        'start' => $startLocal,
                        'end' => $endLocal,
                    ];
                })->values()->all();
            })
            ->toArray();

        $exceptGuestSpotId = $customRequest->isGuestRequest() ? (int) $customRequest->guest_id : null;
        $artistBlockedPeriods = app(ManagedRequestBookingService::class)
            ->artistBlockedPeriods($artistUserId, $exceptGuestSpotId ?: null);

        $from = Carbon::now($artistTimezone)->startOfDay();
        $to = $from->copy()->addDays(180)->endOfDay();
        $artistBusyIntervalsByDate = $this->busyIntervalsByDateForRange($userDetail, $from, $to);

        $allowedDateRange = null;
        $guestSpot = $customRequest->isGuestRequest() ? $customRequest->guestSpot : null;
        if ($guestSpot && $guestSpot->from_date && $guestSpot->to_date) {
            $allowedDateRange = [
                'start' => $guestSpot->from_date->format('Y-m-d'),
                'end' => $guestSpot->to_date->format('Y-m-d'),
            ];

            $startHi = $this->normalizeAvailabilityTime($guestSpot->start_time);
            $endHi = $this->normalizeAvailabilityTime($guestSpot->end_time);
            if ($startHi && $endHi && $startHi < $endHi) {
                $guestDayRanges = [['start' => $startHi, 'end' => $endHi]];
                $artistAvailabilitySchedule = [
                    'sunday' => $guestDayRanges,
                    'monday' => $guestDayRanges,
                    'tuesday' => $guestDayRanges,
                    'wednesday' => $guestDayRanges,
                    'thursday' => $guestDayRanges,
                    'friday' => $guestDayRanges,
                    'saturday' => $guestDayRanges,
                ];
            }
        }

        return [
            'artistAvailabilitySchedule' => $artistAvailabilitySchedule,
            'artistTimezone' => $artistTimezone,
            'artistBlockedPeriods' => $artistBlockedPeriods,
            'artistBusyIntervalsByDate' => $artistBusyIntervalsByDate,
            'allowedDateRange' => $allowedDateRange,
            'tattooDurationMinutes' => $tattooDurationMinutes,
            'artistConsultationSettings' => [
                'required' => $customRequest->isGuestRequest()
                    ? false
                    : (bool) ($userDetail->require_consultation ?? false),
                'timing' => $userDetail->consultation_timing ?: 'combined',
                'session_type' => $userDetail->session_type ?: 'both',
                'session_duration_minutes' => (int) ($userDetail->session_duration_minutes ?: 30),
                'require_gap' => (bool) ($userDetail->require_gap_between_consultation_tattoo ?? false),
                'gap_value' => (int) ($userDetail->consultation_tattoo_gap_value ?? 0),
                'gap_unit' => $userDetail->consultation_tattoo_gap_unit ?: 'hours',
            ],
            'customRequest' => [
                'id' => $customRequest->id,
                'reference' => $customRequest->referenceLabel(),
                'isGuest' => $customRequest->isGuestRequest(),
            ],
        ];
    }

    /**
     * Same availability payload shape used by the public booking calendar.
     *
     * @return array{
     *     artistAvailabilitySchedule: array<string, list<array{start:string,end:string}>>,
     *     artistTimezone: string,
     *     artistBlockedPeriods: list<array{start_date:string,end_date:string}>,
     *     artistBusyIntervalsByDate: array<string, list<array{start:int,end:int}>>,
     *     tattooDurationMinutes: int
     * }
     */
    public function calendarPayloadForArtist(
        UserDetail $userDetail,
        int $durationMinutes = 120,
        int $busyLookAheadDays = 180,
        ?int $exceptGuestSpotId = null
    ): array {
        $artistUserId = (int) $userDetail->user_id;
        $artistTimezone = $userDetail->timezone ?: 'UTC';
        $from = Carbon::now($artistTimezone)->startOfDay();
        $to = $from->copy()->addDays(max(1, $busyLookAheadDays))->endOfDay();

        return [
            'artistAvailabilitySchedule' => $this->weeklyAvailabilitySchedule($artistUserId, $artistTimezone),
            'artistTimezone' => $artistTimezone,
            'artistBlockedPeriods' => app(ManagedRequestBookingService::class)
                ->artistBlockedPeriods($artistUserId, $exceptGuestSpotId),
            'artistBusyIntervalsByDate' => $this->busyIntervalsByDateForRange($userDetail, $from, $to),
            'tattooDurationMinutes' => max(15, $durationMinutes),
        ];
    }

    /**
     * Open date/time pills for payment-link auto scheduling using booking calendar rules.
     *
     * @return list<array{ymd:string,label:string,book_label:string,times:list<string>}>
     */
    public function openDatePillsForArtist(
        UserDetail $userDetail,
        int $durationMinutes,
        int $lookAheadDays = 365,
        int $maxDates = 60
    ): array {
        $payload = $this->calendarPayloadForArtist($userDetail, $durationMinutes, $lookAheadDays);
        $timezone = $payload['artistTimezone'];
        $schedule = $payload['artistAvailabilitySchedule'];
        $blocked = $payload['artistBlockedPeriods'];
        $busy = $payload['artistBusyIntervalsByDate'];
        $durationMinutes = max(15, $durationMinutes);
        $now = Carbon::now($timezone);
        $dates = [];

        for ($i = 0; $i < $lookAheadDays; $i++) {
            $day = $now->copy()->startOfDay()->addDays($i);
            $ymd = $day->format('Y-m-d');
            if ($this->isDateBlocked($ymd, $blocked)) {
                continue;
            }

            $weekday = strtolower($day->format('l'));
            $ranges = $schedule[$weekday] ?? [];
            if ($ranges === []) {
                continue;
            }

            $times = [];
            foreach ($ranges as $range) {
                $startParts = explode(':', (string) ($range['start'] ?? '0:0'));
                $endParts = explode(':', (string) ($range['end'] ?? '0:0'));
                $startMinutes = ((int) ($startParts[0] ?? 0) * 60) + (int) ($startParts[1] ?? 0);
                $endMinutes = ((int) ($endParts[0] ?? 0) * 60) + (int) ($endParts[1] ?? 0);
                if ($endMinutes <= $startMinutes) {
                    continue;
                }

                // Match booking JS: step 30m, require full duration to fit before range end.
                for ($minute = $startMinutes; $minute < $endMinutes; $minute += 30) {
                    if ($minute + $durationMinutes > $endMinutes) {
                        break;
                    }
                    // Match booking JS: today slots must be strictly after now.
                    if ($i === 0 && $minute <= (($now->hour * 60) + $now->minute)) {
                        continue;
                    }
                    if ($this->slotOverlapsBusy($busy[$ymd] ?? [], $minute, $durationMinutes)) {
                        continue;
                    }
                    $times[] = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
                }
            }

            $times = array_values(array_unique($times));
            if ($times === []) {
                continue;
            }

            $dates[] = [
                'ymd' => $ymd,
                'label' => $day->format('D j'),
                'book_label' => $day->format('D j M'),
                'times' => $times,
            ];

            if (count($dates) >= $maxDates) {
                break;
            }
        }

        return $dates;
    }

    /**
     * @return array<string, list<array{start:string,end:string}>>
     */
    private function weeklyAvailabilitySchedule(int $artistUserId, string $artistTimezone): array
    {
        return Availability::query()
            ->where('user_id', $artistUserId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week')
            ->map(function ($rows) use ($artistTimezone) {
                return $rows->map(function ($availability) use ($artistTimezone) {
                    $startLocal = Carbon::createFromFormat('Y-m-d H:i:s', now('UTC')->format('Y-m-d').' '.$availability->start_time, 'UTC')
                        ->setTimezone($artistTimezone)
                        ->format('H:i');
                    $endLocal = Carbon::createFromFormat('Y-m-d H:i:s', now('UTC')->format('Y-m-d').' '.$availability->end_time, 'UTC')
                        ->setTimezone($artistTimezone)
                        ->format('H:i');

                    return [
                        'start' => $startLocal,
                        'end' => $endLocal,
                    ];
                })->values()->all();
            })
            ->toArray();
    }

    /**
     * @param  list<array{start_date?:string,end_date?:string,start?:string,end?:string}>  $blocked
     */
    private function isDateBlocked(string $ymd, array $blocked): bool
    {
        foreach ($blocked as $period) {
            $start = (string) ($period['start_date'] ?? $period['start'] ?? '');
            $end = (string) ($period['end_date'] ?? $period['end'] ?? '');
            if ($start !== '' && $end !== '' && $ymd >= $start && $ymd <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{start:int,end:int}>  $intervals
     */
    private function slotOverlapsBusy(array $intervals, int $startMinutes, int $durationMinutes): bool
    {
        $endMinutes = $startMinutes + $durationMinutes;
        foreach ($intervals as $interval) {
            $busyStart = (int) ($interval['start'] ?? 0);
            $busyEnd = (int) ($interval['end'] ?? 0);
            if ($startMinutes < $busyEnd && $endMinutes > $busyStart) {
                return true;
            }
        }

        return false;
    }

    private function normalizeAvailabilityTime(mixed $time): ?string
    {
        $raw = trim((string) ($time ?? ''));
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->format('H:i');
        } catch (\Throwable) {
            if (preg_match('/^(\d{1,2}):(\d{2})/', $raw, $match)) {
                return str_pad($match[1], 2, '0', STR_PAD_LEFT).':'.$match[2];
            }

            return null;
        }
    }

    public function resolveTattooDurationMinutes(?ArtistDesign $tattoo): int
    {
        if (! $tattoo) {
            return 120;
        }
        $tattooDurationMinutes = (int) ($tattoo->session_duration ?? 0) * 60;
        if ($tattooDurationMinutes <= 0) {
            preg_match('/(\d+)/', (string) ($tattoo->session_duration ?? ''), $durationMatch);
            $tattooDurationMinutes = isset($durationMatch[1]) ? ((int) $durationMatch[1] * 60) : 120;
        }

        return max(15, $tattooDurationMinutes);
    }

    /**
     * @param  array<string, list<array{start:int,end:int}>>  $map
     */
    private function appendBookingOccupancyToBusyMap(Booking $booking, string $artistTz, array &$map, int $bufferAfterMinutes = 0): void
    {
        $timing = strtolower((string) ($booking->consultation_timing_type ?? 'combined'));
        if ($timing !== 'separate') {
            $timing = 'combined';
        }

        $hasConsult = (bool) $booking->has_consultation;

        if ($hasConsult && $timing === 'separate'
            && $booking->consultation_date
            && $booking->consultation_start_time_utc
            && $booking->consultation_end_time_utc) {
            $cd = $booking->consultation_date instanceof \Carbon\CarbonInterface
                ? $booking->consultation_date->format('Y-m-d')
                : (string) $booking->consultation_date;
            $this->appendUtcRangeToBusyMap(
                $map,
                $cd,
                (string) $booking->consultation_start_time_utc,
                (string) $booking->consultation_end_time_utc,
                $artistTz,
                $bufferAfterMinutes
            );
        }

        if (! $booking->booking_date || ! $booking->start_time_utc || ! $booking->end_time_utc) {
            return;
        }

        $bd = $booking->booking_date instanceof \Carbon\CarbonInterface
            ? $booking->booking_date->format('Y-m-d')
            : (string) $booking->booking_date;

        if ($hasConsult && $timing === 'separate') {
            $this->appendUtcRangeToBusyMap($map, $bd, (string) $booking->start_time_utc, (string) $booking->end_time_utc, $artistTz, $bufferAfterMinutes);

            return;
        }

        $this->appendUtcRangeToBusyMap($map, $bd, (string) $booking->start_time_utc, (string) $booking->end_time_utc, $artistTz, $bufferAfterMinutes);
    }

    /**
     * @param  array<string, list<array{start:int,end:int}>>  $map
     */
    private function appendUtcRangeToBusyMap(array &$map, string $ymd, string $startUtc, string $endUtc, string $tz, int $bufferAfterMinutes = 0): void
    {
        try {
            $startAt = Carbon::parse($ymd.' '.$startUtc, 'UTC')->timezone($tz);
            $endAt = Carbon::parse($ymd.' '.$endUtc, 'UTC')->timezone($tz);
        } catch (\Throwable) {
            return;
        }

        if ($bufferAfterMinutes > 0) {
            $endAt = $endAt->copy()->addMinutes(max(0, $bufferAfterMinutes));
        }

        if ($endAt <= $startAt) {
            return;
        }

        $this->appendLocalRangeSegmentsToBusyMap($map, $startAt, $endAt);
    }

    /**
     * Busy intervals by local date for an artist (bookings + Google Calendar free/busy).
     * Used by payment-link auto slots and other server-side availability checks.
     *
     * @param  list<int>  $excludeBookingIds
     * @return array<string, list<array{start:int,end:int}>>
     */
    public function busyIntervalsByDateForRange(
        UserDetail $userDetail,
        Carbon $from,
        Carbon $to,
        array $excludeBookingIds = []
    ): array {
        $artistTz = $userDetail->timezone ?: 'UTC';
        $buffer = max(0, (int) ($userDetail->session_buffer_period ?? 0));
        $map = [];
        $excludeIds = array_values(array_unique(array_filter(array_map('intval', $excludeBookingIds))));

        $bookings = Booking::query()
            ->where('artist_user_id', $userDetail->user_id)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($from, $to) {
                $query->where(function ($inner) use ($from, $to) {
                    $inner->whereDate('booking_date', '>=', $from->toDateString())
                        ->whereDate('booking_date', '<=', $to->toDateString());
                })->orWhere(function ($inner) use ($from, $to) {
                    $inner->whereNotNull('consultation_date')
                        ->whereDate('consultation_date', '>=', $from->toDateString())
                        ->whereDate('consultation_date', '<=', $to->toDateString());
                });
            })
            ->get();

        foreach ($bookings as $booking) {
            if (in_array((int) $booking->id, $excludeIds, true)) {
                continue;
            }
            $this->appendBookingOccupancyToBusyMap($booking, $artistTz, $map, $buffer);
        }

        $this->appendGoogleCalendarBusyToBusyMap($userDetail, $artistTz, $map, $buffer, $from, $to);

        return $map;
    }

    /**
     * Merge connected Google Calendar events into the local busy map.
     *
     * @param  array<string, list<array{start:int,end:int}>>  $map
     */
    private function appendGoogleCalendarBusyToBusyMap(
        UserDetail $userDetail,
        string $artistTz,
        array &$map,
        int $bufferAfterMinutes = 0,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): void {
        if (empty($userDetail->google_calendar_token)) {
            return;
        }

        $startDate = $from?->copy()->timezone($artistTz)->startOfDay()
            ?? Carbon::now($artistTz)->startOfDay();
        $endDate = $to?->copy()->timezone($artistTz)->endOfDay()
            ?? $startDate->copy()->addDays(180)->endOfDay();

        $busyBlocks = GoogleCalendarController::getBusyBlocksForDateRange(
            $userDetail,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $artistTz
        );

        foreach ($busyBlocks as $block) {
            $startUtc = $block['start_datetime_utc'] ?? null;
            $endUtc = $block['end_datetime_utc'] ?? null;
            if (! $startUtc || ! $endUtc) {
                continue;
            }

            try {
                $startAt = $startUtc instanceof Carbon ? $startUtc->copy() : Carbon::parse((string) $startUtc, 'UTC');
                $endAt = $endUtc instanceof Carbon ? $endUtc->copy() : Carbon::parse((string) $endUtc, 'UTC');
            } catch (\Throwable) {
                continue;
            }

            $this->appendDateTimeRangeToBusyMap($map, $startAt, $endAt, $artistTz, $bufferAfterMinutes);
        }
    }

    /**
     * @param  array<string, list<array{start:int,end:int}>>  $map
     */
    private function appendDateTimeRangeToBusyMap(array &$map, Carbon $startAtUtc, Carbon $endAtUtc, string $tz, int $bufferAfterMinutes = 0): void
    {
        $startAt = $startAtUtc->copy()->setTimezone($tz);
        $endAt = $endAtUtc->copy()->setTimezone($tz);

        if ($bufferAfterMinutes > 0) {
            $endAt = $endAt->copy()->addMinutes(max(0, $bufferAfterMinutes));
        }

        if ($endAt <= $startAt) {
            return;
        }

        $this->appendLocalRangeSegmentsToBusyMap($map, $startAt, $endAt);
    }

    /**
     * Split a local busy range across calendar days (handles all-day and multi-day blocks).
     *
     * @param  array<string, list<array{start:int,end:int}>>  $map
     */
    private function appendLocalRangeSegmentsToBusyMap(array &$map, Carbon $startAt, Carbon $endAt): void
    {
        $d = $startAt->copy()->startOfDay();
        $lastDay = $endAt->copy()->startOfDay();
        // Exclusive midnight end (typical all-day FreeBusy) belongs to previous day only.
        if ($endAt->equalTo($lastDay) && $endAt->gt($startAt)) {
            $lastDay = $lastDay->copy()->subDay();
        }

        $guard = 0;
        $maxDays = min(400, max(1, (int) $d->diffInDays($lastDay) + 2));

        while ($d->lte($lastDay) && $guard++ < $maxDays) {
            $dayStart = $d->copy()->startOfDay();
            $dayEndExclusive = $d->copy()->addDay()->startOfDay();
            $segFrom = $startAt->copy()->max($dayStart);
            $segTo = $endAt->copy()->min($dayEndExclusive);

            if ($segTo > $segFrom) {
                $key = $d->format('Y-m-d');
                $startMinutes = ($segFrom->hour * 60) + $segFrom->minute;
                $endMinutes = $startMinutes + (int) max(1, $segFrom->diffInMinutes($segTo));
                if ($endMinutes > 24 * 60) {
                    $endMinutes = 24 * 60;
                }
                if ($endMinutes > $startMinutes) {
                    if (! isset($map[$key])) {
                        $map[$key] = [];
                    }
                    $map[$key][] = ['start' => $startMinutes, 'end' => $endMinutes];
                }
            }

            $d->addDay();
        }
    }
}
