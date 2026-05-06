<?php

namespace App\Services;

use App\Models\ArtistDesign;
use App\Models\Availability;
use App\Models\AvailabilityOverride;
use App\Models\Booking;
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

        $artistBlockedPeriods = AvailabilityOverride::query()
            ->where('user_id', $artistUserId)
            ->orderBy('start_date')
            ->get()
            ->map(static function (AvailabilityOverride $o) {
                return [
                    'start_date' => $o->start_date->format('Y-m-d'),
                    'end_date' => $o->end_date->format('Y-m-d'),
                ];
            })
            ->values()
            ->all();

        $sessionBufferMinutes = max(0, (int) ($userDetail->session_buffer_period ?? 0));

        $excludeIds = array_values(array_unique(array_filter([
            (int) $booking->id,
            $booking->consultation_booking_id ? (int) $booking->consultation_booking_id : null,
        ])));

        $artistBusyIntervalsByDate = [];
        $existingBookings = Booking::query()
            ->where('artist_user_id', $artistUserId)
            ->where('status', 'confirmed')
            ->get();

        foreach ($existingBookings as $b) {
            if (in_array((int) $b->id, $excludeIds, true)) {
                continue;
            }
            $this->appendBookingOccupancyToBusyMap($b, $artistTimezone, $artistBusyIntervalsByDate, $sessionBufferMinutes);
        }

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

        $d = $startAt->copy()->startOfDay();
        $lastDay = $endAt->copy()->startOfDay();
        $guard = 0;

        while ($d->lte($lastDay) && $guard++ < 14) {
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
