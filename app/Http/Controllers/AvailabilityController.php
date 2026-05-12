<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\AvailabilityOverride;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    /**
     * Display the availability management page.
     */
    public function index()
    {
        $user = Auth::user();
        $userDetail = $user->userDetail;
        $timezone = $userDetail->timezone ?? 'UTC';

        // Get all availabilities for the user
        $availabilities = Availability::where('user_id', $user->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Group by day and convert times to user's timezone
        $availabilityByDay = [];
        foreach ($availabilities as $availability) {
            $day = $availability->day_of_week;
            if (!isset($availabilityByDay[$day])) {
                $availabilityByDay[$day] = [];
            }

            // Convert UTC times to user's timezone for display
            // Parse as time in UTC and convert to user timezone
            // Use today's date for proper timezone conversion
            $startTime = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' ' . $availability->start_time, 'UTC')
                ->setTimezone($timezone)
                ->format('H:i');
            $endTime = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d') . ' ' . $availability->end_time, 'UTC')
                ->setTimezone($timezone)
                ->format('H:i');

            $availabilityByDay[$day][] = [
                'id' => $availability->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }

        // Blocked date ranges (full-day unavailability) — see availability_overrides migration
        $blocks = AvailabilityOverride::where('user_id', $user->id)
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $blockedPeriods = $blocks->map(static function (AvailabilityOverride $b) {
            return [
                'id' => $b->id,
                'start_date' => $b->start_date->format('Y-m-d'),
                'end_date' => $b->end_date->format('Y-m-d'),
                'reason' => $b->reason ?? '',
            ];
        })->values()->all();

        $workingHoursInitial = self::buildWorkingHoursInitialForView($availabilityByDay);

        $needsWeeklyAvailabilitySetup = ! $user->hasWeeklyAvailabilitySlots();

        return view('artist.availability.index', [
            'availabilityByDay' => $availabilityByDay,
            'userTimezone' => $timezone,
            'savedAvailabilityStatus' => $userDetail?->availability_status,
            'workingHoursInitial' => $workingHoursInitial,
            'blockedPeriods' => $blockedPeriods,
            'needsWeeklyAvailabilitySetup' => $needsWeeklyAvailabilitySetup,
        ]);
    }

    /**
     * Sunday-first rows for the working-hours UI (matches JS week order).
     *
     * @param  array<string, list<array{id?: int, start_time: string, end_time: string}>>  $availabilityByDay
     * @return list<array{dayKey: string, day: string, letter: string, available: bool, slots: list<array{start: string, end: string}>}>
     */
    protected static function buildWorkingHoursInitialForView(array $availabilityByDay): array
    {
        $week = [
            ['key' => 'sunday', 'label' => 'Sunday', 'letter' => 'S'],
            ['key' => 'monday', 'label' => 'Monday', 'letter' => 'M'],
            ['key' => 'tuesday', 'label' => 'Tuesday', 'letter' => 'T'],
            ['key' => 'wednesday', 'label' => 'Wednesday', 'letter' => 'W'],
            ['key' => 'thursday', 'label' => 'Thursday', 'letter' => 'T'],
            ['key' => 'friday', 'label' => 'Friday', 'letter' => 'F'],
            ['key' => 'saturday', 'label' => 'Saturday', 'letter' => 'S'],
        ];

        $out = [];
        foreach ($week as $meta) {
            $slots = $availabilityByDay[$meta['key']] ?? [];
            $out[] = [
                'dayKey' => $meta['key'],
                'day' => $meta['label'],
                'letter' => $meta['letter'],
                'available' => count($slots) > 0,
                'slots' => array_map(static function (array $s) {
                    return [
                        'start' => $s['start_time'],
                        'end' => $s['end_time'],
                    ];
                }, $slots),
            ];
        }

        return $out;
    }

    /**
     * Persist booking availability status (designs / custom / closed) on user_details.
     */
    public function saveBookingStatus(Request $request)
    {
        $request->validate([
            'availability_status' => ['required', 'in:design_custom,design_only,custom_only,closed'],
        ]);

        $user = Auth::user();
        $userDetail = $user->userDetail;

        if (!$userDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Profile details not found.',
            ], 422);
        }

        $userDetail->update([
            'availability_status' => $request->availability_status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking status saved successfully.',
            'availability_status' => $userDetail->fresh()->availability_status,
        ]);
    }

    /**
     * Save availability data.
     */
    public function store(Request $request)
    {
        $request->validate([
            'availability' => 'nullable|array',
            'availability.*' => 'array',
            'availability.*.*.from' => 'required_with:availability.*.*.to|date_format:H:i',
            'availability.*.*.to' => 'required_with:availability.*.*.from|date_format:H:i|after:availability.*.*.from',
        ]);

        if (is_array($request->availability)) {
            $this->validateAvailabilitySlotsNoOverlap($request->availability);
        }

        $user = Auth::user();
        $userDetail = $user->userDetail;
        $timezone = $userDetail->timezone ?? 'UTC';

        DB::beginTransaction();
        try {
            // Delete all existing availabilities for this user
            Availability::where('user_id', $user->id)->delete();

            // Save new availabilities (only if availability data is provided)
            if ($request->has('availability') && is_array($request->availability)) {
                foreach ($request->availability as $day => $slots) {
                    if (is_array($slots)) {
                        foreach ($slots as $slot) {
                            // Validate slot has both from and to
                            if (isset($slot['from']) && isset($slot['to']) && !empty($slot['from']) && !empty($slot['to'])) {
                                // Convert user's local time to UTC for storage
                                // Use today's date for proper timezone conversion
                                $startTimeUTC = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d') . ' ' . $slot['from'], $timezone)
                                    ->setTimezone('UTC')
                                    ->format('H:i:s');
                                $endTimeUTC = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d') . ' ' . $slot['to'], $timezone)
                                    ->setTimezone('UTC')
                                    ->format('H:i:s');

                                Availability::create([
                                    'user_id' => $user->id,
                                    'day_of_week' => $day,
                                    'start_time' => $startTimeUTC,
                                    'end_time' => $endTimeUTC,
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Availability saved successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save availability: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ensure no two slots on the same day overlap (times in artist-local H:i).
     *
     * @param  array<string, array<int, array{from?: string, to?: string}>>  $availability
     */
    protected function validateAvailabilitySlotsNoOverlap(array $availability): void
    {
        foreach ($availability as $day => $slots) {
            if (! is_array($slots)) {
                continue;
            }

            $intervals = [];
            foreach ($slots as $slot) {
                if (! isset($slot['from'], $slot['to']) || $slot['from'] === '' || $slot['to'] === '') {
                    continue;
                }
                $from = $this->parseHiToMinutes((string) $slot['from']);
                $to = $this->parseHiToMinutes((string) $slot['to']);
                if ($from >= $to) {
                    throw ValidationException::withMessages([
                        'availability' => ['End time must be after start time on '.ucfirst((string) $day).'.'],
                    ]);
                }
                $intervals[] = ['from' => $from, 'to' => $to];
            }

            usort($intervals, fn ($a, $b) => $a['from'] <=> $b['from']);

            for ($i = 0, $n = count($intervals); $i < $n - 1; $i++) {
                if ($intervals[$i]['to'] > $intervals[$i + 1]['from']) {
                    throw ValidationException::withMessages([
                        'availability' => ['Overlapping time slots on '.ucfirst((string) $day).'.'],
                    ]);
                }
            }
        }
    }

    protected function parseHiToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);

        return $h * 60 + $m;
    }

    /**
     * Delete a specific availability.
     */
    public function destroy($id)
    {
        $availability = Availability::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $availability->delete();

        return response()->json([
            'success' => true,
            'message' => 'Availability deleted successfully',
        ]);
    }

    /**
     * Store or update a blocked date range (full days unavailable).
     */
    public function storeOverride(Request $request)
    {
        $request->validate([
            'block_id' => ['nullable', 'integer', 'exists:availability_overrides,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $start = Carbon::parse($request->start_date)->format('Y-m-d');
        $end = Carbon::parse($request->end_date)->format('Y-m-d');

        if (! $request->block_id && $start < Carbon::today()->format('Y-m-d')) {
            throw ValidationException::withMessages([
                'start_date' => ['Start date cannot be in the past.'],
            ]);
        }

        $this->assertBlockPeriodDoesNotOverlap($user->id, $start, $end, $request->block_id ? (int) $request->block_id : null);

        DB::beginTransaction();
        try {
            if ($request->block_id) {
                $block = AvailabilityOverride::where('id', $request->block_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                $block->update([
                    'start_date' => $start,
                    'end_date' => $end,
                    'reason' => $request->reason,
                ]);
                $saved = $block->fresh();
            } else {
                $saved = AvailabilityOverride::create([
                    'user_id' => $user->id,
                    'start_date' => $start,
                    'end_date' => $end,
                    'reason' => $request->reason,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Blocked dates saved successfully.',
                'block' => [
                    'id' => $saved->id,
                    'start_date' => $saved->start_date->format('Y-m-d'),
                    'end_date' => $saved->end_date->format('Y-m-d'),
                    'reason' => $saved->reason ?? '',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save blocked dates: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @param  int|null  $exceptBlockId  When updating, ignore this row for overlap checks
     */
    protected function assertBlockPeriodDoesNotOverlap(int $userId, string $start, string $end, ?int $exceptBlockId): void
    {
        $q = AvailabilityOverride::where('user_id', $userId)
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start);

        if ($exceptBlockId !== null) {
            $q->where('id', '!=', $exceptBlockId);
        }

        if ($q->exists()) {
            throw ValidationException::withMessages([
                'start_date' => ['This period overlaps another blocked range. Adjust or remove the existing block first.'],
            ]);
        }
    }

    /**
     * Delete a date override.
     */
    public function destroyOverride($id)
    {
        $override = AvailabilityOverride::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $override->delete();

        return response()->json([
            'success' => true,
            'message' => 'Date override deleted successfully',
        ]);
    }

    /**
     * Get one blocked period by id (for optional client refresh).
     */
    public function getOverride(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $user = Auth::user();

        $block = AvailabilityOverride::where('user_id', $user->id)
            ->where('id', $request->id)
            ->first();

        if (! $block) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $block->id,
                'start_date' => $block->start_date->format('Y-m-d'),
                'end_date' => $block->end_date->format('Y-m-d'),
                'reason' => $block->reason ?? '',
            ],
        ]);
    }
}
