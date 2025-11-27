<?php

namespace App\Http\Controllers;

use App\Models\InkJinArtist;
use App\Models\InkJinTattoo;
use App\Models\User;
use App\Models\Availability;
use App\Models\AvailabilityOverride;
use App\Services\InkJinApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\GoogleCalendarController;
use App\Models\Booking;
use App\Mail\BookingConfirmationMail;

class InkJinController extends Controller
{
    protected InkJinApiService $apiService;

    public function __construct(InkJinApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Get tattoo by ID
     * 
     * @param int $id Tattoo node ID
     * @return JsonResponse
     */
    public function getTattoo(int $id): JsonResponse
    {
        $tattoo = $this->apiService->getTattooById($id);
        
        if ($tattoo === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tattoo not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $tattoo,
        ]);
    }

    /**
     * Get artist by ID
     * 
     * @param int $id Artist user ID (uid)
     * @return JsonResponse
     */
    public function getArtist(int $id): JsonResponse
    {
        $artist = $this->apiService->getArtistById($id);
        
        if ($artist === null) {
            return response()->json([
                'success' => false,
                'message' => 'Artist not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $artist,
        ]);
    }

    /**
     * Public tattoo page
     * URL: /{artist_name}/{tattoo_name}/{tattoo_id}
     * 
     * @param string $artistName Artist name slug
     * @param string $tattooName Tattoo name slug
     * @param int $tattooId Tattoo ID
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function publicTattooPage(string $artistName, string $tattooName, int $tattooId)
    {
        // Get tattoo by ID
        $tattoo = $this->apiService->getTattooById($tattooId);
        
        if ($tattoo === null) {
            abort(404, 'Tattoo not found');
        }
        
        // Get artist by ID from tattoo
        $artistId = $tattoo['author_id'] ?? null;
        if (!$artistId) {
            abort(404, 'Artist not found');
        }
        
        $artist = $this->apiService->getArtistById($artistId);
        
        if ($artist === null) {
            abort(404, 'Artist not found');
        }
        
        // Verify artist name matches
        $artistSlug = slugify($artist['display_name'] ?? $artist['username'] ?? '');
        $tattooSlug = slugify($tattoo['title'] ?? '');
        
        // If either name doesn't match, redirect to correct URL
        if ($artistSlug !== $artistName || $tattooSlug !== $tattooName) {
            return redirect()->route('public.tattoo', [
                'artist_name' => $artistSlug,
                'tattoo_name' => $tattooSlug,
                'tattoo_id' => $tattooId
            ], 301);
        }
        
        // All checks passed, show the page
        return view('public.tattoo', [
            'tattoo' => $tattoo,
            'artist' => $artist,
        ]);
    }

    /**
     * Public artist profile page
     * URL: /{username}
     * 
     * @param string $username Artist username
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function publicArtistProfile(string $username)
    {
        // Get artist by username
        $artist = $this->apiService->getArtistByUsername($username);
        
        if ($artist === null) {
            abort(404, 'Artist not found');
        }
        
        // Verify username matches (in case of redirect needed)
        $artistUsername = $artist['username'] ?? '';
        if ($artistUsername !== $username) {
            return redirect()->route('public.artist', [
                'username' => $artistUsername
            ], 301);
        }
        
        // Get all tattoos for the artist (they're already in the artist response)
        $tattoos = $artist['artist_tattoos'] ?? [];
        
        // All checks passed, show the page
        return view('public.artist', [
            'artist' => $artist,
            'tattoos' => $tattoos,
        ]);
    }

    /**
     * Get tattoo by ID from database
     * 
     * @param int $id Tattoo ID
     * @return JsonResponse
     */
    public function getTattooFromDb(int $id): JsonResponse
    {
        $tattoo = InkJinTattoo::with('artist')->find($id);
        
        if ($tattoo === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tattoo not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $tattoo,
        ]);
    }

    /**
     * Get artist by ID from database
     * 
     * @param int $id Artist user ID
     * @return JsonResponse
     */
    public function getArtistFromDb(int $id): JsonResponse
    {
        $artist = InkJinArtist::find($id);
        
        if ($artist === null) {
            return response()->json([
                'success' => false,
                'message' => 'Artist not found',
            ], 404);
        }
        
        // Load related tattoos
        $artist->load('tattoos');
        
        return response()->json([
            'success' => true,
            'data' => $artist,
        ]);
    }

    /**
     * Public artist profile page from database
     * URL: /artist/{username}
     * 
     * @param string $username Artist username
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function publicArtistProfileFromDb(string $username)
    {
        // Get artist by artist_handle from database
        $artist = InkJinArtist::where('artist_handle', $username)->first();
        
        if ($artist === null) {
            abort(404, 'Artist not found');
        }
        
        // Verify artist_handle matches (in case of redirect needed)
        $artistHandle = $artist->artist_handle ?? '';
        if ($artistHandle !== $username) {
            return redirect()->route('public.artist.db', [
                'username' => $artistHandle
            ], 301);
        }
        
        // Get all tattoos for the artist
        $tattoos = $artist->tattoos()->where('visibility', 'public')->get();
        
        // Convert artist model to array format for view compatibility
        $artistData = [
            'uid' => $artist->id,
            'username' => $artist->artist_handle,
            'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
            'field_profile_picture' => null, // Not in table structure
            'field_profile_description' => null, // Not in table structure
            'field_profile_instagram' => $artist->instagram,
            'field_profile_tiktok' => $artist->tiktok,
            'field_profile_website' => $artist->website,
            'field_profile_mobile_phone' => $artist->mobile_phone,
            'field_profile_tattooing_since' => $artist->since,
            'field_profile_studio' => $artist->studio,
            'field_profile_primary_style' => $artist->style,
            'field_profile_style' => $artist->style,
            'field_address_city' => $artist->city,
            'field_address_country' => $artist->country,
            'followed_count' => 0,
            'artist_tattoo_count' => [['tattoo_count' => $tattoos->count()]],
        ];
        
        // Convert tattoos collection to array format
        $tattoosData = $tattoos->map(function ($tattoo) {
            return [
                'nid' => $tattoo->id,
                'title' => $tattoo->title,
                'field_tattoo_image_preview' => $tattoo->filename,
            ];
        })->toArray();
        
        // All checks passed, show the page
        return view('public.artist', [
            'artist' => $artistData,
            'tattoos' => $tattoosData,
            'tattooRoute' => 'public.tattoo.db', // Use database route for tattoos
        ]);
    }

    /**
     * Public tattoo page from database
     * URL: /tattoo/{artist_display_name}/{tattoo_title}/{tattoo_id}
     * 
     * @param string $artistDisplayName Artist display name slug
     * @param string $tattooTitle Tattoo title slug
     * @param int $tattooId Tattoo ID
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function publicTattooPageFromDb(string $artistDisplayName, string $tattooTitle, int $tattooId)
    {
        // Get tattoo by ID from database
        $tattoo = InkJinTattoo::find($tattooId);
        
        if ($tattoo === null) {
            abort(404, 'Tattoo not found');
        }
        
        // Get artist from database
        $artist = $tattoo->artist;
        
        if ($artist === null) {
            abort(404, 'Artist not found');
        }
        
        // Verify names match
        $artistSlug = slugify($artist->display_name ?? $artist->artist_handle ?? '');
        $tattooSlug = slugify($tattoo->title ?? '');
        
        // If either name doesn't match, redirect to correct URL
        if ($artistSlug !== $artistDisplayName || $tattooSlug !== $tattooTitle) {
            return redirect()->route('public.tattoo.db', [
                'artist_display_name' => $artistSlug,
                'tattoo_title' => $tattooSlug,
                'tattoo_id' => $tattooId
            ], 301);
        }
        
        // Convert tattoo model to array format for view compatibility
        $tattooData = [
            'tattoo_id' => $tattoo->id,
            'title' => $tattoo->title,
            'field_tattoo_image_preview' => $tattoo->filename,
            'field_tattoo_description' => $tattoo->description,
            'field_tags_names' => $tattoo->tags,
            'field_tattoo_color' => $tattoo->color,
            'field_tattoo_style_primary' => $tattoo->primary_style,
            'field_tattoo_style' => $tattoo->other_styles,
            'field_tattoo_suggested_placement' => $tattoo->suggested_placement,
            'field_tattoo_width' => $tattoo->size_width,
            'field_tattoo_height' => $tattoo->size_height,
            'field_tattoo_price' => $tattoo->price,
            'field_tattoo_max_price' => $tattoo->max_price,
            'field_tattoo_cost_per_session' => $tattoo->cost_per_session,
            'field_tattoo_min_sessions' => $tattoo->min_sessions,
            'field_tattoo_max_sessions' => $tattoo->max_sessions,
            'field_tattoo_session_time' => $tattoo->session_time_h,
            'field_tattoo_currency' => $tattoo->currency,
            'field_tattoo_price_model' => $tattoo->price_model,
            'field_tattoo_notes' => $tattoo->notes,
            'author_id' => $artist->id,
            'author_username' => $artist->artist_handle,
            'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
        ];
        
        // Convert artist model to array format for view compatibility
        $artistData = [
            'uid' => $artist->id,
            'username' => $artist->artist_handle,
            'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
            'field_profile_picture' => null, // Not in table structure
            'field_profile_description' => null, // Not in table structure
            'field_profile_instagram' => $artist->instagram,
            'field_profile_tiktok' => $artist->tiktok,
            'field_profile_website' => $artist->website,
            'field_profile_mobile_phone' => $artist->mobile_phone,
            'field_profile_tattooing_since' => $artist->since,
            'field_profile_studio' => $artist->studio,
            'field_profile_primary_style' => $artist->style,
            'field_profile_style' => $artist->style,
            'field_address_city' => $artist->city,
            'field_address_country' => $artist->country,
        ];
        
        // All checks passed, show the page
        return view('public.tattoo', [
            'tattoo' => $tattooData,
            'artist' => $artistData,
        ]);
    }

    /**
     * Book tattoo page
     * URL: /tattoo/{artist_display_name}/{tattoo_title}/{tattoo_id}/book
     * 
     * @param string $artistDisplayName Artist display name slug
     * @param string $tattooTitle Tattoo title slug
     * @param int $tattooId Tattoo ID
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function bookTattoo(string $artistDisplayName, string $tattooTitle, int $tattooId)
    {
        // Get tattoo by ID from database
        $tattoo = InkJinTattoo::find($tattooId);
        
        if ($tattoo === null) {
            abort(404, 'Tattoo not found');
        }
        
        // Get artist from database
        $artist = $tattoo->artist;
        
        if ($artist === null) {
            abort(404, 'Artist not found');
        }
        
        // Verify names match
        $artistSlug = slugify($artist->display_name ?? $artist->artist_handle ?? '');
        $tattooSlug = slugify($tattoo->title ?? '');
        
        // If either name doesn't match, redirect to correct URL
        if ($artistSlug !== $artistDisplayName || $tattooSlug !== $tattooTitle) {
            return redirect()->route('public.tattoo.book', [
                'artist_display_name' => $artistSlug,
                'tattoo_title' => $tattooSlug,
                'tattoo_id' => $tattooId
            ], 301);
        }
        
        // Find user in users table where app_id matches the artist ID
        // Note: This assumes artists are linked to users via app_id
        $user = User::where('app_id', $artist->id)->first();
        
        // Initialize availability data
        $availabilityData = [
            'availabilities' => collect(),
            'overrides' => collect(),
            'availableDates' => [],
            'unavailableDates' => [],
            'userTimezone' => 'UTC',
        ];
        
        if ($user) {
            // Get user detail for timezone
            $userDetail = $user->userDetail;
            $timezone = $userDetail->timezone ?? 'UTC';
            $availabilityData['userTimezone'] = $timezone;
            
            // Get all availabilities for this user
            $availabilities = Availability::where('user_id', $user->id)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
            
            // Get all availability overrides (get all future overrides, no date limit)
            // This includes dates where artist marked themselves as unavailable
            $overrides = AvailabilityOverride::where('user_id', $user->id)
                ->where('override_date', '>=', Carbon::today())
                ->orderBy('override_date')
                ->get();
            
            $availabilityData['availabilities'] = $availabilities;
            // Format overrides for JSON (convert dates to strings)
            $availabilityData['overrides'] = $overrides->map(function ($override) {
                return [
                    'override_date' => $override->override_date->format('Y-m-d'),
                    'is_unavailable' => $override->is_unavailable,
                    'start_time' => $override->start_time,
                    'end_time' => $override->end_time,
                ];
            });
            
            // Calculate available/unavailable dates for all future dates
            // We'll calculate dynamically based on weekly schedule and overrides
            // For performance, we'll calculate up to 2 years ahead, but the calendar can navigate further
            $availableDates = [];
            $unavailableDates = [];
            $startDate = Carbon::today();
            $endDate = Carbon::today()->addYears(2);
            
            // Group weekly availabilities by day of week (stored as lowercase strings: monday, tuesday, etc.)
            $weeklyAvailability = [];
            foreach ($availabilities as $availability) {
                $dayOfWeek = strtolower($availability->day_of_week);
                if (!isset($weeklyAvailability[$dayOfWeek])) {
                    $weeklyAvailability[$dayOfWeek] = [];
                }
                $weeklyAvailability[$dayOfWeek][] = [
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                ];
            }
            
            // Map Carbon dayOfWeek (0=Sunday, 6=Saturday) to lowercase day names
            $dayNameMap = [
                0 => 'sunday',
                1 => 'monday',
                2 => 'tuesday',
                3 => 'wednesday',
                4 => 'thursday',
                5 => 'friday',
                6 => 'saturday',
            ];
            
            // Process each date
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $dateKey = $currentDate->format('Y-m-d');
                
                // Convert date to artist's timezone to get the correct day of week
                // This is critical: the artist set availability for "Monday" in their timezone (e.g., Asia/Karachi)
                // So we need to check what day of week this date is in the artist's timezone, not UTC
                // Example: If artist is in Asia/Karachi (GMT+5) and sets Monday availability,
                // we need to check if this date is Monday in Asia/Karachi timezone
                // Create date at midnight in artist's timezone
                $dateInArtistTimezone = Carbon::create(
                    $currentDate->year,
                    $currentDate->month,
                    $currentDate->day,
                    0, 0, 0, $timezone
                );
                $carbonDayOfWeek = $dateInArtistTimezone->dayOfWeek; // 0 = Sunday, 6 = Saturday
                $dayName = $dayNameMap[$carbonDayOfWeek];
                
                // Check for override first (overrides take precedence)
                $override = $overrides->firstWhere('override_date', $dateKey);
                
                if ($override) {
                    if ($override->is_unavailable) {
                        $unavailableDates[] = $dateKey;
                    } else {
                        // Custom availability for this date
                        $availableDates[] = $dateKey;
                    }
                } else {
                    // Check weekly availability based on day of week in artist's timezone
                    if (isset($weeklyAvailability[$dayName]) && count($weeklyAvailability[$dayName]) > 0) {
                        $availableDates[] = $dateKey;
                    } else {
                        $unavailableDates[] = $dateKey;
                    }
                }
                
                $currentDate->addDay();
            }
            
            $availabilityData['availableDates'] = $availableDates;
            $availabilityData['unavailableDates'] = $unavailableDates;
        }
        
        // Convert tattoo model to array format for view compatibility
        $tattooData = [
            'tattoo_id' => $tattoo->id,
            'title' => $tattoo->title,
            'field_tattoo_image_preview' => $tattoo->filename,
            'author_id' => $artist->id,
            'author_username' => $artist->artist_handle,
            'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
        ];
        
        // Convert artist model to array format for view compatibility
        $artistData = [
            'uid' => $artist->id,
            'username' => $artist->artist_handle,
            'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
            'field_profile_picture' => null, // Not in table structure
        ];
        
        return view('public.book', [
            'tattoo' => $tattooData,
            'artist' => $artistData,
            'availabilityData' => $availabilityData,
        ]);
    }

    /**
     * Public artists list page from database
     * URL: /artists
     * 
     * @return View
     */
    public function publicArtistsList()
    {
        // Get all artists from database with eager loading to avoid N+1 queries
        $artists = InkJinArtist::withCount(['tattoos' => function($query) {
                $query->where('visibility', 'public');
            }])
            ->where('visibility', 'public')
            ->orderBy('profile_name', 'asc')
            ->orderBy('artist_handle', 'asc')
            ->get();
        
        // Convert artists to array format for view compatibility
        $artistsData = $artists->map(function ($artist) {
            return [
                'uid' => $artist->id,
                'username' => $artist->artist_handle,
                'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
                'field_profile_picture' => null, // Not in table structure
                'field_profile_description' => null, // Not in table structure
                'field_profile_instagram' => $artist->instagram,
                'field_profile_tiktok' => $artist->tiktok,
                'field_profile_website' => $artist->website,
                'field_profile_studio' => $artist->studio,
                'field_profile_primary_style' => $artist->style,
                'field_address_city' => $artist->city,
                'field_address_country' => $artist->country,
                'followed_count' => 0,
                'tattoo_count' => $artist->tattoos_count ?? 0,
            ];
        })->toArray();
        
        return view('public.artists', [
            'artists' => $artistsData,
        ]);
    }

    /**
     * Get available time slots for a specific date
     * URL: /api/availability/{tattoo_id}?date=2025-01-20
     * 
     * @param Request $request
     * @param int $tattooId Tattoo ID
     * @return void (dd for debugging)
     */
    public function getAvailabilitySlots(Request $request, int $tattooId)
    {
        // Validate date parameter
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        $date = Carbon::parse($request->date);
        $dateKey = $date->format('Y-m-d');
        
        // Get tattoo
        $tattoo = InkJinTattoo::find($tattooId);
        if (!$tattoo) {
            dd(['error' => 'Tattoo not found', 'tattoo_id' => $tattooId]);
        }

        // Get artist
        $artist = $tattoo->artist;
        if (!$artist) {
            dd(['error' => 'Artist not found', 'tattoo_id' => $tattooId]);
        }

        // Get tattoo session time
        $sessionTimeHours = $tattoo->session_time_h ?? 2;

        // Find user linked to artist
        $user = User::where('app_id', $artist->id)->first();
        if (!$user) {
            dd([
                'error' => 'Artist user account not found',
                'artist_id' => $artist->id,
                'tattoo_id' => $tattooId,
                'date' => $dateKey,
            ]);
        }

        // Get user detail for timezone and buffer period
        $userDetail = $user->userDetail;
        $timezone = $userDetail->timezone ?? 'UTC';
        
        // Get session buffer period (time needed after each session)
        $bufferPeriodMinutes = (int) ($userDetail->session_buffer_period ?? 0); // Default no buffer
        
        // Get day of week in artist's timezone
        $dateInArtistTimezone = Carbon::create(
            $date->year,
            $date->month,
            $date->day,
            0, 0, 0, $timezone
        );
        
        $dayNameMap = [
            0 => 'sunday',
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
        ];
        $dayName = $dayNameMap[$dateInArtistTimezone->dayOfWeek];

        // Check for date override first
        $override = AvailabilityOverride::where('user_id', $user->id)
            ->where('override_date', $dateKey)
            ->first();

        $isUnavailable = false;
        $availabilityWindows = [];

        if ($override) {
            if ($override->is_unavailable) {
                $isUnavailable = true;
            } else {
                // Custom availability for this date
                if ($override->start_time && $override->end_time) {
                    $availabilityWindows[] = [
                        'start_time' => $override->start_time,
                        'end_time' => $override->end_time,
                        'type' => 'override',
                    ];
                }
            }
        } else {
            // Get weekly availability for this day from availability table
            $availabilities = Availability::where('user_id', $user->id)
                ->where('day_of_week', $dayName)
                ->orderBy('start_time')
                ->get();

            foreach ($availabilities as $availability) {
                $availabilityWindows[] = [
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'type' => 'weekly',
                ];
            }
        }

        // Get Google Calendar events for this date (if calendar is connected)
        $calendarEvents = [];
        if ($userDetail && $userDetail->google_calendar_token && $userDetail->google_calendar_id) {
            try {
                $calendarEvents = GoogleCalendarController::getEventsForDate($userDetail, $dateKey, $timezone);
            } catch (\Exception $e) {
                Log::warning('Failed to fetch Google Calendar events: ' . $e->getMessage());
                // Continue without calendar events if there's an error
            }
        }

        // Get existing bookings for this date (exclude cancelled, rescheduled, and no_show)
        $existingBookings = Booking::where('artist_user_id', $user->id)
            ->where('booking_date', $dateKey)
            ->whereIn('status', ['pending', 'confirmed']) // Only check active bookings
            ->get();

        // Get current time in UTC for filtering past slots
        $nowUTC = Carbon::now('UTC');
        $selectedDateUTC = Carbon::createFromFormat('Y-m-d', $dateKey, 'UTC')->startOfDay();
        $isToday = $selectedDateUTC->isSameDay($nowUTC);

        // Generate time slots based on availability windows and session_time_h
        // Logic: Generate slots that fit within the availability window
        // Example: Available 6:00-13:00, slot duration 3 hours
        // Last valid slot: 10:00-13:00 (ends exactly at 13:00)
        // Also exclude slots that overlap with Google Calendar events
        // And exclude slots that have already passed (if booking for today)
        $timeSlots = [];
        
        if (!$isUnavailable && !empty($availabilityWindows)) {
            $slotDurationMinutes = $sessionTimeHours * 60;
            $slotInterval = 30; // 30-minute intervals between slots for flexibility
            
            foreach ($availabilityWindows as $window) {
                // Parse start and end times (already in UTC format H:i:s)
                $windowStartUTC = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $window['start_time'], 'UTC');
                $windowEndUTC = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $window['end_time'], 'UTC');
                
                // If booking for today, start from current time (rounded up to next interval)
                // Otherwise start from the beginning of the availability window
                if ($isToday && $windowStartUTC->lt($nowUTC)) {
                    // Start from current time, rounded up to next 30-minute interval
                    $currentSlotStart = $nowUTC->copy()->addMinutes($slotInterval - ($nowUTC->minute % $slotInterval));
                    // Ensure we don't start before the window start
                    if ($currentSlotStart->lt($windowStartUTC)) {
                        $currentSlotStart = $windowStartUTC->copy();
                    }
                } else {
                    // Start generating slots from the beginning of the availability window
                    $currentSlotStart = $windowStartUTC->copy();
                }
                
                // Continue while we can fit a full slot duration before the window ends
                // The last slot will end exactly at windowEndUTC (or earlier)
                while ($currentSlotStart->copy()->addMinutes($slotDurationMinutes)->lte($windowEndUTC)) {
                    $slotEndTime = $currentSlotStart->copy()->addMinutes($slotDurationMinutes);
                    
                    // Ensure slot doesn't exceed the availability window
                    // This check ensures the slot ends at or before the window end time
                    if ($slotEndTime->lte($windowEndUTC)) {
                        // If booking for today, check if slot start time has already passed
                        if ($isToday && $currentSlotStart->lte($nowUTC)) {
                            // Skip this slot as it has already passed
                            $currentSlotStart->addMinutes($slotInterval);
                            continue;
                        }
                        
                        // Check if this slot overlaps with any Google Calendar event
                        $hasCalendarOverlap = false;
                        $earliestNextStartAfterCalendar = null;
                        foreach ($calendarEvents as $event) {
                            $eventStart = $event['start_datetime_utc'];
                            $eventEnd = $event['end_datetime_utc'];
                            
                            // Check for overlap: slot overlaps if it starts before event ends AND ends after event starts
                            if ($currentSlotStart->lt($eventEnd) && $slotEndTime->gt($eventStart)) {
                                $hasCalendarOverlap = true;
                                // Track the earliest time we can start after this event
                                if ($earliestNextStartAfterCalendar === null || $eventEnd->gt($earliestNextStartAfterCalendar)) {
                                    $earliestNextStartAfterCalendar = $eventEnd->copy();
                                }
                                break;
                            }
                        }
                        
                        // Check if this slot overlaps with any existing booking (including buffer time)
                        $hasBookingOverlap = false;
                        $earliestNextStartAfterBooking = null;
                        foreach ($existingBookings as $existingBooking) {
                            // Parse booking times (stored as TIME format H:i:s)
                            $bookingStartUTC = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $existingBooking->start_time_utc, 'UTC');
                            $bookingEndUTC = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $existingBooking->end_time_utc, 'UTC');
                            
                            // Add buffer time after booking ends
                            $bookingEndWithBuffer = $bookingEndUTC->copy()->addMinutes($bufferPeriodMinutes);
                            
                            // Check for overlap:
                            // 1. Slot overlaps with booking itself (starts before booking ends AND ends after booking starts)
                            // 2. Slot starts within buffer time after booking ends (slot starts before booking end + buffer)
                            // Simplified: If slot starts before (booking end + buffer) AND slot ends after booking start, it's blocked
                            if ($currentSlotStart->lt($bookingEndWithBuffer) && $slotEndTime->gt($bookingStartUTC)) {
                                $hasBookingOverlap = true;
                                // Track the earliest time we can start after this booking (with buffer)
                                if ($earliestNextStartAfterBooking === null || $bookingEndWithBuffer->gt($earliestNextStartAfterBooking)) {
                                    $earliestNextStartAfterBooking = $bookingEndWithBuffer->copy();
                                }
                            }
                        }
                        
                        // Only add slot if it doesn't overlap with calendar events or existing bookings
                        if (!$hasCalendarOverlap && !$hasBookingOverlap) {
                            // Convert to artist's timezone for display
                            $slotStartLocal = $currentSlotStart->copy()->setTimezone($timezone);
                            $slotEndLocal = $slotEndTime->copy()->setTimezone($timezone);
                            
                            $timeSlots[] = [
                                'start_time_utc' => $currentSlotStart->format('H:i:s'),
                                'end_time_utc' => $slotEndTime->format('H:i:s'),
                                'start_time_local' => $slotStartLocal->format('H:i'),
                                'end_time_local' => $slotEndLocal->format('H:i'),
                                'start_time_display' => $slotStartLocal->format('g:i A'),
                                'end_time_display' => $slotEndLocal->format('g:i A'),
                                'duration_hours' => $sessionTimeHours,
                                'type' => $window['type'] ?? 'weekly',
                            ];
                            
                            // Move to next slot start time (30-minute intervals)
                            $currentSlotStart->addMinutes($slotInterval);
                        } else {
                            // Slot is blocked, calculate the next valid start time
                            $nextValidStart = null;
                            
                            // If blocked by booking, jump to after booking + buffer
                            if ($hasBookingOverlap && $earliestNextStartAfterBooking) {
                                $nextValidStart = $earliestNextStartAfterBooking->copy();
                            }
                            
                            // If blocked by calendar event, jump to after event end
                            if ($hasCalendarOverlap && $earliestNextStartAfterCalendar) {
                                $calendarNextStart = $earliestNextStartAfterCalendar->copy();
                                if ($nextValidStart === null || $calendarNextStart->gt($nextValidStart)) {
                                    $nextValidStart = $calendarNextStart->copy();
                                }
                            }
                            
                            if ($nextValidStart && $nextValidStart->gt($currentSlotStart)) {
                                // Use the calculated next valid start time (respects buffer time exactly)
                                // When respecting buffer time, use the exact time without rounding to intervals
                                // This allows slots to start at the precise buffer time (e.g., 12:19 for 19-minute buffer)
                                
                                // Round seconds to 0 for cleanliness, but keep exact minutes
                                if ($nextValidStart->second > 0) {
                                    $nextValidStart->addMinute()->second(0);
                                } else {
                                    $nextValidStart->second(0);
                                }
                                
                                // Use the exact calculated time (no rounding to 15/30-minute intervals)
                                $currentSlotStart = $nextValidStart->copy();
                            } else {
                                // If no valid next start calculated, just move to next interval
                                $currentSlotStart->addMinutes($slotInterval);
                            }
                        }
                    } else {
                        // Slot would exceed window, move to next interval
                        $currentSlotStart->addMinutes($slotInterval);
                    }
                    
                    // Safety check: if next slot start + duration would exceed window, stop
                    if ($currentSlotStart->copy()->addMinutes($slotDurationMinutes)->gt($windowEndUTC)) {
                        break;
                    }
                }
            }
        }

        // Get active questions for the artist's user
        $questions = \App\Models\UserQuestion::where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($question) {
                return [
                    'id' => $question->id,
                    'question' => $question->question,
                    'type' => $question->type,
                    'options' => $question->options,
                    'max_images' => $question->max_images,
                ];
            });

        // Return JSON response
        return response()->json([
            'success' => true,
            'date' => $dateKey,
            'day_of_week' => $dayName,
            'timezone' => $timezone,
            'is_unavailable' => $isUnavailable,
            'tattoo' => [
                'id' => $tattoo->id,
                'title' => $tattoo->title,
                'session_time_h' => $sessionTimeHours,
                'min_sessions' => $tattoo->min_sessions,
                'max_sessions' => $tattoo->max_sessions,
                'cost_per_session' => $tattoo->cost_per_session,
                'currency' => $tattoo->currency,
            ],
            'artist' => [
                'id' => $artist->id,
                'artist_handle' => $artist->artist_handle,
                'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
            ],
            'time_slots' => $timeSlots,
            'questions' => $questions,
            'override' => $override ? [
                'id' => $override->id,
                'is_unavailable' => $override->is_unavailable,
                'start_time' => $override->start_time,
                'end_time' => $override->end_time,
                'notes' => $override->notes,
            ] : null,
        ]);
    }

    /**
     * Submit booking with questions answers
     * URL: /api/booking/{tattoo_id}
     * 
     * @param Request $request
     * @param int $tattooId Tattoo ID
     * @return JsonResponse
     */
    public function submitBooking(Request $request, int $tattooId)
    {
        // Get tattoo
        $tattoo = InkJinTattoo::find($tattooId);
        if (!$tattoo) {
            return response()->json([
                'success' => false,
                'message' => 'Tattoo not found',
            ], 404);
        }

        // Get artist
        $artist = $tattoo->artist;
        if (!$artist) {
            return response()->json([
                'success' => false,
                'message' => 'Artist not found',
            ], 404);
        }

        // Find user linked to artist
        $user = User::where('app_id', $artist->id)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Artist user account not found',
            ], 404);
        }

        // Get active questions for validation
        $questions = \App\Models\UserQuestion::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        // Build validation rules (only if there are active questions)
        $rules = [];
        $customMessages = [];
        
        if ($questions->count() > 0) {
            foreach ($questions as $question) {
                $fieldName = "questions.{$question->id}";
                $rules[$fieldName] = 'required';
                
                if ($question->type === 'image') {
                    $maxImages = $question->max_images ?? 1;
                    if ($maxImages > 1) {
                        $rules[$fieldName] = "required|array|max:{$maxImages}"; // Limit array size
                        $rules["{$fieldName}.*"] = 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120'; // 5MB max per file
                        $customMessages["{$fieldName}.max"] = "You can upload a maximum of {$maxImages} images for: {$question->question}";
                    } else {
                        $rules[$fieldName] = 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120'; // 5MB max
                    }
                } else if ($question->type === 'free') {
                    $rules[$fieldName] = 'required|string|max:5000';
                } else if (in_array($question->type, ['select', 'radio'])) {
                    $rules[$fieldName] = 'required|string';
                }
                
                $customMessages["{$fieldName}.required"] = "Please answer: {$question->question}";
            }
        }

        // Validate slot data
        $rules['slot.date'] = 'required|date';
        $rules['slot.start_time_utc'] = 'required|string';
        $rules['slot.end_time_utc'] = 'required|string';

        $validator = Validator::make($request->all(), $rules, $customMessages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get user details for payment calculation
        $userDetail = $user->userDetail;
        if (!$userDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Artist payment settings not found',
            ], 404);
        }

        // Calculate deposit amount
        $depositAmount = 0;
        $depositType = $userDetail->minimum_deposit_type ?? 'fixed';
        $depositValue = $userDetail->minimum_deposit_amount ?? 0;
        $currency = $userDetail->currency ?? 'USD';
        
        // Get tattoo price (use price or cost_per_session)
        $tattooPrice = 0;
        if ($tattoo->price) {
            $tattooPrice = (float) $tattoo->price;
        } elseif ($tattoo->cost_per_session) {
            $tattooPrice = (float) $tattoo->cost_per_session;
        }
        
        if ($depositType === 'percentage' && $tattooPrice > 0) {
            $depositAmount = ($depositValue / 100) * $tattooPrice;
        } else {
            $depositAmount = $depositValue;
        }
        
        // Round to 2 decimal places
        $depositAmount = round($depositAmount, 2);
        
        // Platform fee (InkJin website fee)
        $platformFee = 10.00; // $10 platform fee
        
        // Calculate total amount (deposit + platform fee)
        $totalAmount = $depositAmount + $platformFee;
        
        // Check if Stripe account is connected
        $hasStripeAccount = !empty($userDetail->stripe_account_id);
        
            // Process and store file uploads (images)
            $processedAnswers = [];
            $questionsInput = $request->input('questions', []);
            
            foreach ($questions as $question) {
                $questionId = $question->id;
                $fieldName = "questions.{$questionId}";
                
                if ($question->type === 'image' && $request->hasFile($fieldName)) {
                    $maxImages = $question->max_images ?? 1;
                    
                    if ($maxImages > 1) {
                        // Handle multiple images
                        $files = $request->file($fieldName);
                        if (!is_array($files)) {
                            $files = [$files]; // Convert single file to array
                        }
                        
                        $imagePaths = [];
                        foreach ($files as $file) {
                            $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                            $path = $file->storeAs('booking_answers', $filename, 'public');
                            $imagePaths[] = asset('storage/' . $path);
                        }
                        $processedAnswers[$questionId] = $imagePaths;
                    } else {
                        // Handle single image
                        $file = $request->file($fieldName);
                        if (is_array($file)) {
                            $file = $file[0]; // Take first file if array
                        }
                        $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs('booking_answers', $filename, 'public');
                        $processedAnswers[$questionId] = asset('storage/' . $path);
                    }
                } else {
                    // Store text answer
                    $processedAnswers[$questionId] = $questionsInput[$questionId] ?? null;
                }
            }
            
            // Return payment information instead of success
        return response()->json([
            'success' => true,
            'message' => 'Questions answered successfully. Please proceed to payment.',
            'payment_required' => $depositAmount > 0,
            'payment' => [
                'deposit_amount' => $depositAmount,
                'platform_fee' => $platformFee,
                'total_amount' => $totalAmount,
                'deposit_type' => $depositType,
                'deposit_value' => $depositValue,
                'currency' => $currency,
                'tattoo_price' => $tattooPrice,
                'has_stripe_account' => $hasStripeAccount,
                'stripe_account_id' => $userDetail->stripe_account_id,
            ],
            'booking_data' => [
                'tattoo_id' => $tattoo->id,
                'slot' => $request->input('slot'),
                'answers' => $processedAnswers,
            ],
        ]);
    }

    /**
     * Create Stripe payment intent for booking deposit
     * URL: /api/booking/{tattoo_id}/payment-intent
     * 
     * @param Request $request
     * @param int $tattooId Tattoo ID
     * @return JsonResponse
     */
    public function createPaymentIntent(Request $request, int $tattooId)
    {
        // Get tattoo
        $tattoo = InkJinTattoo::find($tattooId);
        if (!$tattoo) {
            return response()->json([
                'success' => false,
                'message' => 'Tattoo not found',
            ], 404);
        }

        // Get artist
        $artist = $tattoo->artist;
        if (!$artist) {
            return response()->json([
                'success' => false,
                'message' => 'Artist not found',
            ], 404);
        }

        // Find user linked to artist
        $user = User::where('app_id', $artist->id)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Artist user account not found',
            ], 404);
        }

        // Get user details
        $userDetail = $user->userDetail;
        if (!$userDetail || !$userDetail->stripe_account_id) {
            return response()->json([
                'success' => false,
                'message' => 'Artist Stripe account not connected',
            ], 400);
        }

        // Validate and get amount
        $amount = $request->input('amount');
        $currency = $request->input('currency', 'USD');
        
        // Convert to float and validate
        $amount = is_numeric($amount) ? (float) $amount : null;
        
        if (!$amount || $amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment amount. Amount must be greater than 0.',
            ], 400);
        }
        
        // Round to 2 decimal places
        $amount = round($amount, 2);

        try {
            // Initialize Stripe
            $stripeSecret = env('STRIPE_SECRET');
            if (!$stripeSecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe is not configured',
                ], 500);
            }
            
            \Stripe\Stripe::setApiKey($stripeSecret);
            
            // Platform fee (InkJin website fee)
            $platformFee = 10.00; // $10 platform fee
            
            // Calculate total amount (payment amount + platform fee)
            $totalAmount = $amount + $platformFee;
            
            // Ensure total amount is valid
            if ($totalAmount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid total amount calculation',
                ], 400);
            }
            
            // Convert amounts to cents (Stripe uses smallest currency unit)
            $totalAmountInCents = (int) round($totalAmount * 100);
            $platformFeeInCents = (int) round($platformFee * 100);
            
            // Validate minimum amounts (Stripe minimum is typically $0.50)
            if ($totalAmountInCents < 50) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount is too small. Minimum payment is $0.50.',
                ], 400);
            }
            
            // Create payment intent with destination charge (Stripe Connect)
            // The total amount is charged to customer
            // Platform fee goes to InkJin account
            // Remaining amount goes to artist's connected account
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $totalAmountInCents,
                'currency' => strtolower($currency),
                'application_fee_amount' => $platformFeeInCents, // $10 platform fee goes to InkJin
                'transfer_data' => [
                    'destination' => $userDetail->stripe_account_id,
                ],
                'metadata' => [
                    'tattoo_id' => $tattoo->id,
                    'artist_id' => $artist->id,
                    'user_id' => $user->id,
                    'type' => 'booking_deposit',
                    'platform_fee' => $platformFee,
                    'payment_amount' => $amount,
                    'total_amount' => $totalAmount,
                ],
            ]);

            return response()->json([
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe error: ' . $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Payment Intent Creation Error: ' . $e->getMessage(), [
                'tattoo_id' => $tattooId,
                'amount' => $amount ?? null,
                'currency' => $currency,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save booking after successful payment and send confirmation emails
     * URL: /api/booking/{tattoo_id}/confirm
     * 
     * @param Request $request
     * @param int $tattooId Tattoo ID
     * @return JsonResponse
     */
    public function confirmBooking(Request $request, int $tattooId)
    {
        try {
            // Prepare data for validation - convert full_amount_paid to proper boolean
            $requestData = $request->all();
            
            // Handle full_amount_paid - accept true, false, 1, 0, "true", "false", "1", "0"
            // Convert before validation to avoid validation issues
            if (isset($requestData['full_amount_paid'])) {
                $fullAmountPaid = $requestData['full_amount_paid'];
                // Convert to boolean - handles strings, integers, and booleans
                if (is_string($fullAmountPaid)) {
                    $fullAmountPaid = strtolower(trim($fullAmountPaid));
                    $requestData['full_amount_paid'] = in_array($fullAmountPaid, ['true', '1', 'on', 'yes']);
                } elseif (is_numeric($fullAmountPaid)) {
                    $requestData['full_amount_paid'] = (bool) $fullAmountPaid;
                } else {
                    $requestData['full_amount_paid'] = (bool) $fullAmountPaid;
                }
            } else {
                $requestData['full_amount_paid'] = false;
            }
            
            // Validate request - make slot validation more flexible
            // Don't validate full_amount_paid as boolean since we've already converted it
            $validator = Validator::make($requestData, [
                'payment_intent_id' => 'required|string',
                'slot' => 'required|array',
                'slot.date' => 'required|date',
                'slot.start_time_utc' => 'required|string',
                'slot.end_time_utc' => 'required|string',
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'amount' => 'required|numeric|min:0',
                'currency' => 'required|string|min:3|max:3',
                'full_amount_paid' => 'nullable', // Already converted to boolean, just check it exists if needed
                'questions' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                Log::error('Booking confirmation validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Get tattoo
            $tattoo = InkJinTattoo::find($tattooId);
            if (!$tattoo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tattoo not found',
                ], 404);
            }

            // Get artist
            $artist = $tattoo->artist;
            if (!$artist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Artist not found',
                ], 404);
            }

            // Find artist user
            $artistUser = User::where('app_id', $artist->id)->first();
            if (!$artistUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Artist user account not found',
                ], 404);
            }
            
            // Log artist email for debugging
            Log::info('Artist user found for booking confirmation', [
                'artist_user_id' => $artistUser->id,
                'artist_email' => $artistUser->email,
                'artist_name' => $artistUser->name,
            ]);

            // Get or create customer user
            $customerEmail = $requestData['customer_email'];
            $customerName = $requestData['customer_name'];
            
            // Check if user is authenticated first
            $customerUser = Auth::check() ? Auth::user() : null;
            
            // If not authenticated, find or create user by email
            if (!$customerUser) {
                $customerUser = User::where('email', $customerEmail)->first();
                
                // If customer doesn't exist, create a guest user account
                if (!$customerUser) {
                    $customerUser = User::create([
                        'name' => $customerName,
                        'email' => $customerEmail,
                        'password' => bcrypt(str()->random(32)), // Random password for guest users
                        'email_verified_at' => null, // Guest users don't need verified email
                    ]);
                }
            }

            // Get user detail for timezone and settings
            $userDetail = $artistUser->userDetail;
            $timezone = $userDetail->timezone ?? 'UTC';
            
            // Get artist settings for cancellation window and reschedule limit
            $cancellationWindowRaw = $userDetail->cancellation_window ?? null;
            $rescheduleLimit = $userDetail->reschedule_times ?? null; // 'never', 'once', 'twice', 'unlimited' or number
            
            // Parse cancellation_window - could be "24h", "48h", "72h" or just a number
            $cancellationWindowHours = null;
            if ($cancellationWindowRaw) {
                if (is_numeric($cancellationWindowRaw)) {
                    $cancellationWindowHours = (int) $cancellationWindowRaw;
                } else {
                    // Try to extract number from string like "48h", "72h", etc.
                    preg_match('/(\d+)/', (string) $cancellationWindowRaw, $matches);
                    if (!empty($matches[1])) {
                        $cancellationWindowHours = (int) $matches[1];
                    }
                }
            }
            
            // Convert reschedule_times to numeric limit
            $rescheduleLimitNumeric = null;
            if ($rescheduleLimit) {
                if (is_numeric($rescheduleLimit)) {
                    $rescheduleLimitNumeric = (int) $rescheduleLimit;
                } elseif (strtolower($rescheduleLimit) === 'never') {
                    $rescheduleLimitNumeric = 0;
                } elseif (strtolower($rescheduleLimit) === 'once') {
                    $rescheduleLimitNumeric = 1;
                } elseif (strtolower($rescheduleLimit) === 'twice') {
                    $rescheduleLimitNumeric = 2;
                } elseif (strtolower($rescheduleLimit) === 'unlimited') {
                    $rescheduleLimitNumeric = null; // null means unlimited
                }
            }

            // Calculate amounts
            $amount = (float) $requestData['amount'];
            $platformFee = 10.00;
            $totalAmount = $amount + $platformFee;
            $fullAmountPaid = $requestData['full_amount_paid'] ?? false;

            // Process questions answers (files should already be stored from submitBooking)
            $questionsAnswers = $requestData['questions'] ?? [];

            // Get slot data
            $slot = $requestData['slot'];
            
            // Calculate cancellation deadline
            $cancellationDeadline = null;
            if ($cancellationWindowHours) {
                $bookingDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $slot['date'] . ' ' . $slot['start_time_utc'], 'UTC');
                $cancellationDeadline = $bookingDateTime->copy()->subHours($cancellationWindowHours);
            }
            
            // Determine booking type (currently only flash tattoos are supported via this flow)
            // Custom tattoos will be implemented later with a different flow
            $bookingType = 'flash'; // Default to flash since we're booking from inkjin_tattoos
            
            // Create initial action history
            $actionHistory = [
                [
                    'action' => 'created',
                    'user_id' => $customerUser->id,
                    'user_type' => 'customer',
                    'timestamp' => now()->toDateTimeString(),
                    'notes' => 'Booking created via website',
                ],
                [
                    'action' => 'payment_received',
                    'user_id' => $customerUser->id,
                    'user_type' => 'system',
                    'timestamp' => now()->toDateTimeString(),
                    'amount' => $totalAmount,
                    'payment_intent_id' => $requestData['payment_intent_id'],
                    'notes' => 'Payment processed successfully',
                ],
            ];
            
            // Create booking
            $booking = Booking::create([
                'user_id' => $customerUser->id,
                'artist_user_id' => $artistUser->id,
                'tattoo_id' => $tattoo->id,
                'booking_type' => $bookingType,
                'booking_date' => $slot['date'],
                'start_time_utc' => $slot['start_time_utc'],
                'end_time_utc' => $slot['end_time_utc'],
                'timezone' => $timezone,
                'status' => 'confirmed',
                'payment_intent_id' => $requestData['payment_intent_id'],
                'payment_status' => 'paid',
                'deposit_amount' => $fullAmountPaid ? 0 : $amount,
                'full_amount_paid' => (bool) $fullAmountPaid,
                'platform_fee' => $platformFee,
                'total_amount_paid' => $totalAmount,
                'currency' => strtoupper($requestData['currency']),
                'questions_answers' => !empty($questionsAnswers) ? $questionsAnswers : null,
                'cancellation_deadline' => $cancellationDeadline,
                'cancellation_window_hours' => $cancellationWindowHours,
                'reschedule_count' => 0,
                'reschedule_limit' => $rescheduleLimitNumeric,
                'action_history' => $actionHistory,
            ]);

            // Create Google Calendar event for the artist (if calendar is connected)
            $calendarEventId = null;
            try {
                $artistUserDetail = $artistUser->userDetail;
                if ($artistUserDetail && $artistUserDetail->google_calendar_token && $artistUserDetail->google_calendar_id) {
                    $calendarEventId = GoogleCalendarController::createCalendarEvent($artistUserDetail, $booking);
                    
                    if ($calendarEventId) {
                        // Add to action history
                        $actionHistory[] = [
                            'action' => 'calendar_event_created',
                            'user_id' => null,
                            'user_type' => 'system',
                            'timestamp' => now()->toDateTimeString(),
                            'calendar_event_id' => $calendarEventId,
                            'notes' => 'Google Calendar event created successfully',
                        ];
                        
                        // Update booking with calendar event ID and updated action history
                        $booking->update([
                            'google_calendar_event_id' => $calendarEventId,
                            'action_history' => $actionHistory,
                        ]);
                        
                        Log::info('Google Calendar event created for booking', [
                            'booking_id' => $booking->id,
                            'event_id' => $calendarEventId,
                        ]);
                    }
                } else {
                    Log::info('Google Calendar not connected for artist, skipping event creation', [
                        'booking_id' => $booking->id,
                        'artist_user_id' => $artistUser->id,
                    ]);
                }
            } catch (\Exception $e) {
                // Don't fail the booking if calendar event creation fails
                Log::error('Failed to create Google Calendar event (non-critical)', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Get questions for email template
            $questionTexts = [];
            if (!empty($questionsAnswers)) {
                $questionModels = \App\Models\UserQuestion::whereIn('id', array_keys($questionsAnswers))
                    ->where('user_id', $artistUser->id)
                    ->get()
                    ->keyBy('id');
                
                foreach ($questionModels as $id => $question) {
                    $questionTexts[$id] = $question->question;
                }
            }

            // Send confirmation email to customer
            $customerEmailSent = false;
            try {
                Mail::to($customerUser->email)->send(
                    new BookingConfirmationMail($booking, false, $questionTexts)
                );
                $customerEmailSent = true;
                Log::info('Booking confirmation email sent to customer', [
                    'booking_id' => $booking->id,
                    'customer_email' => $customerUser->email,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send booking confirmation email to customer: ' . $e->getMessage(), [
                    'booking_id' => $booking->id,
                    'customer_email' => $customerUser->email,
                    'exception' => $e->getTraceAsString(),
                ]);
            }

            // Add a delay to avoid rate limiting (Mailtrap/testing services have strict limits)
            // Wait 3 seconds before sending artist email to avoid "too many emails per second" error
            sleep(3);

            // Send notification email to artist with retry mechanism
            $artistEmailSent = false;
            $maxRetries = 3;
            $retryDelay = 2; // seconds
            
            try {
                // Verify artist email exists
                if (empty($artistUser->email)) {
                    Log::error('Artist email is empty, cannot send booking notification', [
                        'booking_id' => $booking->id,
                        'artist_user_id' => $artistUser->id,
                    ]);
                } else {
                    // Retry mechanism for rate limit issues
                    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                        try {
                            Mail::to($artistUser->email)->send(
                                new BookingConfirmationMail($booking, true, $questionTexts)
                            );
                            $artistEmailSent = true;
                            Log::info('Booking notification email sent to artist', [
                                'booking_id' => $booking->id,
                                'artist_email' => $artistUser->email,
                                'attempt' => $attempt,
                            ]);
                            break; // Success, exit retry loop
                        } catch (\Exception $e) {
                            $errorMessage = $e->getMessage();
                            
                            // Check if it's a rate limit error
                            if (strpos($errorMessage, 'Too many emails per second') !== false || 
                                strpos($errorMessage, '550') !== false) {
                                
                                if ($attempt < $maxRetries) {
                                    // Wait before retrying with exponential backoff
                                    $waitTime = $retryDelay * $attempt;
                                    Log::warning('Rate limit hit, retrying artist email', [
                                        'booking_id' => $booking->id,
                                        'attempt' => $attempt,
                                        'wait_time' => $waitTime,
                                    ]);
                                    sleep($waitTime);
                                    continue; // Retry
                                }
                            }
                            
                            // If not rate limit or max retries reached, throw the exception
                            throw $e;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to send booking notification email to artist after retries: ' . $e->getMessage(), [
                    'booking_id' => $booking->id,
                    'artist_email' => $artistUser->email ?? 'N/A',
                    'artist_user_id' => $artistUser->id,
                    'max_retries' => $maxRetries,
                    'exception' => $e->getTraceAsString(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking confirmed successfully',
                'booking_id' => $booking->id,
                'emails_sent' => [
                    'customer' => $customerEmailSent,
                    'artist' => $artistEmailSent,
                ],
                'calendar_event_created' => !empty($calendarEventId),
                'calendar_event_id' => $calendarEventId,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Booking confirmation error: ' . $e->getMessage(), [
                'tattoo_id' => $tattooId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm booking: ' . $e->getMessage(),
            ], 500);
        }
    }
}

