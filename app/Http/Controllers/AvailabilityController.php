<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\AvailabilityOverride;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Get all date overrides for the user
        $overrides = AvailabilityOverride::where('user_id', $user->id)
            ->orderBy('override_date', 'desc')
            ->get();

        // Convert override times to user's timezone for display
        $overridesByDate = [];
        foreach ($overrides as $override) {
            $dateKey = $override->override_date->format('Y-m-d');
            $overrideData = [
                'id' => $override->id,
                'is_unavailable' => $override->is_unavailable,
                'notes' => $override->notes,
            ];

            if (!$override->is_unavailable && $override->start_time && $override->end_time) {
                // Convert UTC times to user's timezone for display
                $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $override->override_date->format('Y-m-d') . ' ' . $override->start_time, 'UTC')
                    ->setTimezone($timezone)
                    ->format('H:i');
                $endTime = Carbon::createFromFormat('Y-m-d H:i:s', $override->override_date->format('Y-m-d') . ' ' . $override->end_time, 'UTC')
                    ->setTimezone($timezone)
                    ->format('H:i');
                
                $overrideData['start_time'] = $startTime;
                $overrideData['end_time'] = $endTime;
            }

            $overridesByDate[$dateKey] = $overrideData;
        }

        return view('artist.availability.index', [
            'availabilityByDay' => $availabilityByDay,
            'overridesByDate' => $overridesByDate,
            'userTimezone' => $timezone,
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
     * Store or update a date override.
     */
    public function storeOverride(Request $request)
    {
        $request->validate([
            'override_id' => ['nullable', 'integer', 'exists:availability_overrides,id'],
            'override_date' => ['required', 'date', 'after_or_equal:today'],
            'is_unavailable' => ['nullable', 'boolean'],
            'start_time' => ['nullable', 'required_with:end_time', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_with:start_time', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $userDetail = $user->userDetail;
        $timezone = $userDetail->timezone ?? 'UTC';

        DB::beginTransaction();
        try {
            $isUnavailable = $request->has('is_unavailable') && $request->is_unavailable;
            $startTimeUTC = null;
            $endTimeUTC = null;

            // If not unavailable, convert times to UTC
            if (!$isUnavailable && $request->start_time && $request->end_time) {
                $overrideDate = Carbon::parse($request->override_date);
                $startTimeUTC = Carbon::createFromFormat('Y-m-d H:i', $overrideDate->format('Y-m-d') . ' ' . $request->start_time, $timezone)
                    ->setTimezone('UTC')
                    ->format('H:i:s');
                $endTimeUTC = Carbon::createFromFormat('Y-m-d H:i', $overrideDate->format('Y-m-d') . ' ' . $request->end_time, $timezone)
                    ->setTimezone('UTC')
                    ->format('H:i:s');
            }

            // If override_id is provided, update the existing record
            if ($request->override_id) {
                $override = AvailabilityOverride::where('id', $request->override_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                $override->update([
                    'override_date' => $request->override_date,
                    'start_time' => $startTimeUTC,
                    'end_time' => $endTimeUTC,
                    'is_unavailable' => $isUnavailable,
                    'notes' => $request->notes ?? null,
                ]);
            } else {
                // Otherwise, create a new override (or update if same date exists)
                AvailabilityOverride::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'override_date' => $request->override_date,
                    ],
                    [
                        'start_time' => $startTimeUTC,
                        'end_time' => $endTimeUTC,
                        'is_unavailable' => $isUnavailable,
                        'notes' => $request->notes ?? null,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Date override saved successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save date override: ' . $e->getMessage(),
            ], 500);
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
     * Get override for a specific date.
     */
    public function getOverride(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $user = Auth::user();
        $userDetail = $user->userDetail;
        $timezone = $userDetail->timezone ?? 'UTC';

        $override = AvailabilityOverride::where('user_id', $user->id)
            ->where('override_date', $request->date)
            ->first();

        if (!$override) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $overrideData = [
            'id' => $override->id,
            'is_unavailable' => $override->is_unavailable,
            'notes' => $override->notes,
        ];

        if (!$override->is_unavailable && $override->start_time && $override->end_time) {
            // Convert UTC times to user's timezone
            $date = Carbon::parse($override->override_date);
            $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $override->start_time, 'UTC')
                ->setTimezone($timezone)
                ->format('H:i');
            $endTime = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $override->end_time, 'UTC')
                ->setTimezone($timezone)
                ->format('H:i');
            
            $overrideData['start_time'] = $startTime;
            $overrideData['end_time'] = $endTime;
        }

        return response()->json([
            'success' => true,
            'data' => $overrideData,
        ]);
    }
}
