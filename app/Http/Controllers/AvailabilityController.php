<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\AvailabilityOverride;
use App\Models\UserDetail;
use App\Models\UserStudio;
use App\Models\Waitlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
                'studio_ids' => self::normalizeStudioIds($availability->studio_ids ?? null),
            ];
        }

        $scheduleStudios = self::activeStudiosForUser((int) $user->id);
        $allStudioIds = array_map(static fn (array $s) => (int) $s['id'], $scheduleStudios);

        $overrides = AvailabilityOverride::where('user_id', $user->id)
            ->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $blockedPeriods = $overrides->map(static function (AvailabilityOverride $b) {
            $studioIds = self::normalizeStudioIds($b->studio_ids ?? null);

            return [
                'id' => $b->id,
                'start_date' => $b->start_date->format('Y-m-d'),
                'end_date' => $b->end_date->format('Y-m-d'),
                'reason' => $b->reason ?? '',
                'studio_ids' => $studioIds,
                'is_location' => $studioIds !== [],
            ];
        })->values()->all();

        $workingHoursInitial = self::buildWorkingHoursInitialForView($availabilityByDay, $allStudioIds);

        $needsWeeklyAvailabilitySetup = ! $user->hasWeeklyAvailabilitySlots();

        return view('artist.availability.index', [
            'availabilityByDay' => $availabilityByDay,
            'userTimezone' => $timezone,
            'savedAvailabilityStatus' => $userDetail?->availability_status,
            'workingHoursInitial' => $workingHoursInitial,
            'blockedPeriods' => $blockedPeriods,
            'needsWeeklyAvailabilitySetup' => $needsWeeklyAvailabilitySetup,
            'scheduleStudios' => $scheduleStudios,
            'multiStudio' => count($scheduleStudios) >= 2,
            'travelBufferMinutes' => (int) ($userDetail?->travel_buffer_minutes ?: 120),
        ]);
    }

    /**
     * Active studios for schedule UI — one entry per user_studios row with status=1.
     * Id is always user_studios.id (studio_id not required).
     *
     * @return list<array{id: int, name: string}>
     */
    protected static function activeStudiosForUser(int $userId): array
    {
        return UserStudio::activeLinksForUser($userId)
            ->map(function ($link) {
                $name = trim((string) ($link->studio?->name ?: $link->studio_name ?: ''));

                return [
                    'id' => (int) $link->id,
                    'name' => $name !== '' ? $name : 'Studio',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $value
     * @return list<int>
     */
    protected static function normalizeStudioIds($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static function ($id) {
            $n = (int) $id;

            return $n > 0 ? $n : null;
        }, $value))));
    }

    /**
     * Monday-first rows for the working-hours UI (matches Schedule / 03-schedule).
     *
     * @param  array<string, list<array{id?: int, start_time: string, end_time: string, studio_ids?: list<int>}>>  $availabilityByDay
     * @param  list<int>  $allStudioIds
     * @return list<array{dayKey: string, day: string, letter: string, available: bool, studio_ids: list<int>, slots: list<array{start: string, end: string}>}>
     */
    protected static function buildWorkingHoursInitialForView(array $availabilityByDay, array $allStudioIds = []): array
    {
        $week = [
            ['key' => 'monday', 'label' => 'Monday', 'letter' => 'M'],
            ['key' => 'tuesday', 'label' => 'Tuesday', 'letter' => 'T'],
            ['key' => 'wednesday', 'label' => 'Wednesday', 'letter' => 'W'],
            ['key' => 'thursday', 'label' => 'Thursday', 'letter' => 'T'],
            ['key' => 'friday', 'label' => 'Friday', 'letter' => 'F'],
            ['key' => 'saturday', 'label' => 'Saturday', 'letter' => 'S'],
            ['key' => 'sunday', 'label' => 'Sunday', 'letter' => 'S'],
        ];

        $defaultStudioIds = $allStudioIds !== [] ? [(int) $allStudioIds[0]] : [];

        $out = [];
        foreach ($week as $meta) {
            $slots = $availabilityByDay[$meta['key']] ?? [];
            $dayStudioIds = $defaultStudioIds;
            if ($slots !== []) {
                $fromSlot = self::normalizeStudioIds($slots[0]['studio_ids'] ?? null);
                if ($fromSlot !== []) {
                    $dayStudioIds = $fromSlot;
                } elseif ($allStudioIds !== []) {
                    // Legacy rows without studio_ids → treat as all active studios
                    $dayStudioIds = $allStudioIds;
                }
            }

            $out[] = [
                'dayKey' => $meta['key'],
                'day' => $meta['label'],
                'letter' => $meta['letter'],
                'available' => count($slots) > 0,
                'studio_ids' => $dayStudioIds,
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

        $previousStatus = (string) ($userDetail->availability_status ?? '');

        $userDetail->update([
            'availability_status' => $request->availability_status,
        ]);

        $newStatus = (string) $userDetail->fresh()->availability_status;
        $openedBooks = $previousStatus === 'closed' && $newStatus !== 'closed';

        $waitlistNotify = null;
        if ($openedBooks) {
            $pendingEntries = Waitlist::query()
                ->booksOnly()
                ->with('customRequest')
                ->where('user_id', $user->id)
                ->where('status', Waitlist::STATUS_PENDING)
                ->get();

            $pendingCount = $pendingEntries->count();

            if ($pendingCount > 0) {
                $requestMoveCount = $pendingEntries
                    ->filter(fn (Waitlist $entry) => $entry->customRequest?->isHeldOnWaitlist())
                    ->count();

                $waitlistNotify = [
                    'pending_count' => $pendingCount,
                    'request_move_count' => $requestMoveCount,
                    'notify_url' => route('artist.clients.waitlist.notify'),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking status saved successfully.',
            'availability_status' => $newStatus,
            'waitlist_notify' => $waitlistNotify,
        ]);
    }

    /**
     * Save availability data.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $scheduleStudios = self::activeStudiosForUser((int) $user->id);
        $allStudioIds = array_map(static fn (array $s) => (int) $s['id'], $scheduleStudios);
        $allowedStudioIds = $allStudioIds;

        $studioIdRule = $allowedStudioIds !== []
            ? ['integer', Rule::in($allowedStudioIds)]
            : ['nullable', 'integer'];

        $request->validate([
            'availability' => 'nullable|array',
            'availability.*' => 'array',
            'availability.*.*.from' => 'required_with:availability.*.*.to|date_format:H:i',
            'availability.*.*.to' => 'required_with:availability.*.*.from|date_format:H:i|after:availability.*.*.from',
            'day_studio_mode' => ['nullable', 'array'],
            'day_studio_mode.*' => ['nullable', Rule::in(['studio', 'all'])],
            'day_studio_id' => ['nullable', 'array'],
            'day_studio_id.*' => $studioIdRule,
            'day_studio_ids' => ['nullable', 'array'],
            'day_studio_ids.*' => ['nullable', 'array'],
            'day_studio_ids.*.*' => $studioIdRule,
            'travel_buffer_minutes' => ['nullable', 'integer', Rule::in([30, 60, 90, 120, 180, 240])],
        ]);

        if (is_array($request->availability)) {
            $this->validateAvailabilitySlotsNoOverlap($request->availability);
        }

        $userDetail = $user->userDetail;
        $timezone = $userDetail->timezone ?? 'UTC';
        $dayModeMap = is_array($request->input('day_studio_mode')) ? $request->input('day_studio_mode') : [];
        $dayIdMap = is_array($request->input('day_studio_id')) ? $request->input('day_studio_id') : [];
        $dayIdsMap = is_array($request->input('day_studio_ids')) ? $request->input('day_studio_ids') : [];

        DB::beginTransaction();
        try {
            Availability::where('user_id', $user->id)->delete();

            if ($request->has('availability') && is_array($request->availability)) {
                foreach ($request->availability as $day => $slots) {
                    if (! is_array($slots)) {
                        continue;
                    }

                    $requestedIds = self::normalizeStudioIds($dayIdsMap[$day] ?? []);
                    if ($requestedIds === [] && isset($dayIdMap[$day])) {
                        $requestedIds = self::normalizeStudioIds([(int) $dayIdMap[$day]]);
                    }

                    $dayStudioIds = $this->resolveDayStudioIds(
                        (string) ($dayModeMap[$day] ?? 'studio'),
                        $requestedIds,
                        $allStudioIds
                    );

                    foreach ($slots as $slot) {
                        if (! isset($slot['from'], $slot['to']) || $slot['from'] === '' || $slot['to'] === '') {
                            continue;
                        }

                        $startTimeUTC = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d').' '.$slot['from'], $timezone)
                            ->setTimezone('UTC')
                            ->format('H:i:s');
                        $endTimeUTC = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d').' '.$slot['to'], $timezone)
                            ->setTimezone('UTC')
                            ->format('H:i:s');

                        Availability::create([
                            'user_id' => $user->id,
                            'day_of_week' => $day,
                            'start_time' => $startTimeUTC,
                            'end_time' => $endTimeUTC,
                            'studio_ids' => $dayStudioIds,
                        ]);
                    }
                }
            }

            if ($userDetail && $request->filled('travel_buffer_minutes') && count($allStudioIds) >= 2) {
                $userDetail->forceFill([
                    'travel_buffer_minutes' => (int) $request->input('travel_buffer_minutes'),
                ])->save();
            }

            DB::commit();

            $user->unsetRelation('availabilities');

            return response()->json([
                'success' => true,
                'message' => 'Availability saved successfully',
                'needs_weekly_availability_setup' => ! $user->hasWeeklyAvailabilitySlots(),
                'travel_buffer_minutes' => (int) ($userDetail?->fresh()?->travel_buffer_minutes ?: 120),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save availability: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve studio ids for a day: any subset of active studios, or all. Never empty when studios exist.
     *
     * @param  list<int>  $studioIds
     * @param  list<int>  $allStudioIds
     * @return list<int>
     */
    protected function resolveDayStudioIds(string $mode, array $studioIds, array $allStudioIds): array
    {
        if ($allStudioIds === []) {
            return [];
        }

        if ($mode === 'all') {
            return $allStudioIds;
        }

        $normalized = array_values(array_intersect(self::normalizeStudioIds($studioIds), $allStudioIds));
        if ($normalized === []) {
            return [(int) $allStudioIds[0]];
        }

        return $normalized;
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
     * Store or update a blocked date range, or a location override for a date range.
     * studio_ids empty/null = block; non-empty = work at those studio(s).
     */
    public function storeOverride(Request $request)
    {
        $user = Auth::user();
        $scheduleStudios = self::activeStudiosForUser((int) $user->id);
        $allStudioIds = array_map(static fn (array $s) => (int) $s['id'], $scheduleStudios);

        $studioIdRule = $allStudioIds !== []
            ? ['integer', Rule::in($allStudioIds)]
            : ['nullable', 'integer'];

        $request->validate([
            'block_id' => ['nullable', 'integer', 'exists:availability_overrides,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
            'type' => ['nullable', Rule::in(['blocked', 'location'])],
            'studio_ids' => ['nullable', 'array'],
            'studio_ids.*' => $studioIdRule,
            'location_mode' => ['nullable', Rule::in(['studio', 'all'])],
        ]);

        $type = (string) ($request->input('type') ?: 'blocked');
        $studioIds = [];
        if ($type === 'location') {
            if (count($allStudioIds) < 1) {
                throw ValidationException::withMessages([
                    'studio_ids' => ['You need an active studio to set a location.'],
                ]);
            }
            $mode = (string) ($request->input('location_mode') ?: 'studio');
            if ($mode === 'all') {
                $studioIds = $allStudioIds;
            } else {
                $studioIds = array_values(array_intersect(
                    self::normalizeStudioIds($request->input('studio_ids')),
                    $allStudioIds
                ));
                if ($studioIds === []) {
                    $studioIds = [(int) $allStudioIds[0]];
                }
            }
        }

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
            $payload = [
                'start_date' => $start,
                'end_date' => $end,
                'reason' => $request->reason,
                'studio_ids' => $studioIds === [] ? null : $studioIds,
            ];

            if ($request->block_id) {
                $block = AvailabilityOverride::where('id', $request->block_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                $block->update($payload);
                $saved = $block->fresh();
            } else {
                $saved = AvailabilityOverride::create(array_merge($payload, [
                    'user_id' => $user->id,
                ]));
            }

            DB::commit();

            $savedIds = self::normalizeStudioIds($saved->studio_ids ?? null);
            $isLocation = $savedIds !== [];

            return response()->json([
                'success' => true,
                'message' => $isLocation ? 'Location saved successfully.' : 'Blocked dates saved successfully.',
                'block' => [
                    'id' => $saved->id,
                    'start_date' => $saved->start_date->format('Y-m-d'),
                    'end_date' => $saved->end_date->format('Y-m-d'),
                    'reason' => $saved->reason ?? '',
                    'studio_ids' => $savedIds,
                    'is_location' => $isLocation,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save: '.$e->getMessage(),
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
                'start_date' => ['This period overlaps another blocked or location range. Adjust or remove the existing entry first.'],
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

        $wasLocation = $override->isLocationOverride();
        $override->delete();

        return response()->json([
            'success' => true,
            'message' => $wasLocation ? 'Location change removed' : 'Dates unblocked',
        ]);
    }

    /**
     * Get one blocked / location period by id (for optional client refresh).
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

        $studioIds = self::normalizeStudioIds($block->studio_ids ?? null);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $block->id,
                'start_date' => $block->start_date->format('Y-m-d'),
                'end_date' => $block->end_date->format('Y-m-d'),
                'reason' => $block->reason ?? '',
                'studio_ids' => $studioIds,
                'is_location' => $studioIds !== [],
            ],
        ]);
    }
}
