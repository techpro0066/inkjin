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
            $availabilityData['weeklyAvailability'] = $weeklyAvailability;
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
        
        // Get consultation info
        $consultationInfo = [
            'requires_consultation' => false,
            'consultation_timing' => null,
            'is_separate' => false,
            'is_combined' => false,
            'session_duration_minutes' => null,
            'gap_required' => false,
            'gap_value' => null,
            'gap_unit' => null,
        ];
        
        if ($user && $user->userDetail) {
            $userDetail = $user->userDetail;
            $consultationInfo['requires_consultation'] = $userDetail->require_consultation ?? false;
            $consultationInfo['consultation_timing'] = $userDetail->consultation_timing ?? null;
            $consultationInfo['is_separate'] = ($userDetail->require_consultation ?? false) && ($userDetail->consultation_timing === 'separate');
            $consultationInfo['is_combined'] = ($userDetail->require_consultation ?? false) && ($userDetail->consultation_timing === 'combined');
            $consultationInfo['session_duration_minutes'] = $userDetail->session_duration_minutes ?? null;
            $consultationInfo['gap_required'] = $userDetail->require_gap_between_consultation_tattoo ?? false;
            $consultationInfo['gap_value'] = $userDetail->consultation_tattoo_gap_value ?? null;
            $consultationInfo['gap_unit'] = $userDetail->consultation_tattoo_gap_unit ?? null;
        }
        
        return view('public.book', [
            'tattoo' => $tattooData,
            'artist' => $artistData,
            'availabilityData' => $availabilityData,
            'consultationInfo' => $consultationInfo,
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
        
        // Check if artist requires consultation with combined timing
        $requiresConsultation = $userDetail && ($userDetail->require_consultation ?? false);
        $consultationTiming = $userDetail->consultation_timing ?? null;
        $isCombinedConsultation = $requiresConsultation && $consultationTiming === 'combined';
        $consultationDurationMinutes = $isCombinedConsultation ? (int) ($userDetail->session_duration_minutes ?? 0) : 0;
        
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

        // Get existing bookings for this date (exclude cancelled and no_show)
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
            // Calculate slot duration: tattoo session + consultation (if combined)
            $slotDurationMinutes = ($sessionTimeHours * 60) + $consultationDurationMinutes;
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
                                'consultation_duration_minutes' => $consultationDurationMinutes,
                                'total_duration_minutes' => $slotDurationMinutes,
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
            'consultation_info' => [
                'requires_consultation' => $requiresConsultation,
                'consultation_timing' => $consultationTiming,
                'is_combined' => $isCombinedConsultation,
                'consultation_duration_minutes' => $consultationDurationMinutes,
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
            
            // Get consultation timing info
            $requiresConsultation = $userDetail && ($userDetail->require_consultation ?? false);
            $consultationTiming = $userDetail->consultation_timing ?? null;
            $isCombinedConsultation = $requiresConsultation && $consultationTiming === 'combined';
            $consultationDurationMinutes = $isCombinedConsultation ? (int) ($userDetail->session_duration_minutes ?? 0) : 0;
            
            // Get artist settings for cancellation window
            $cancellationWindowRaw = $userDetail->cancellation_window ?? null;
            
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

            // Calculate amounts
            $amount = (float) $requestData['amount'];
            $platformFee = 10.00;
            $totalAmount = $amount + $platformFee;
            $fullAmountPaid = $requestData['full_amount_paid'] ?? false;

            // Process questions answers (files should already be stored from submitBooking)
            $questionsAnswers = $requestData['questions'] ?? [];

            // Get slot data
            $slot = $requestData['slot'];
            
            // Calculate booking and consultation times for combined mode
            $bookingStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $slot['date'] . ' ' . $slot['start_time_utc'], 'UTC');
            $bookingEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $slot['date'] . ' ' . $slot['end_time_utc'], 'UTC');
            
            // For combined consultation, calculate separate times
            $tattooSessionDurationMinutes = $tattoo->session_time_h * 60;
            $consultationEndTime = null;
            $tattooSessionStartTime = null;
            
            if ($isCombinedConsultation && $consultationDurationMinutes > 0) {
                // Consultation happens first, then tattoo session
                $consultationEndTime = $bookingStartTime->copy()->addMinutes($consultationDurationMinutes);
                $tattooSessionStartTime = $consultationEndTime->copy();
            }
            
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
                'action_history' => $actionHistory,
                'consultation_timing_type' => $consultationTiming,
                'has_consultation' => $requiresConsultation,
                // Store consultation times for combined mode
                'consultation_date' => $isCombinedConsultation ? $slot['date'] : null,
                'consultation_start_time_utc' => $isCombinedConsultation ? $bookingStartTime->format('H:i:s') : null,
                'consultation_end_time_utc' => $isCombinedConsultation && $consultationEndTime ? $consultationEndTime->format('H:i:s') : null,
                'consultation_completed' => false, // Will be marked complete after consultation
            ]);

            // Create Google Calendar event for the artist (if calendar is connected)
            // Only create Meet link if artist requires consultation
            $calendarEventId = null;
            $meetLink = null;
            try {
                $artistUserDetail = $artistUser->userDetail;
                
                // Check if artist requires consultation - only create Meet link if true
                $requiresConsultation = $artistUserDetail && ($artistUserDetail->require_consultation ?? false);
                
                if ($artistUserDetail && $artistUserDetail->google_calendar_token && $artistUserDetail->google_calendar_id) {
                    // Always create calendar event (for scheduling), pass require_consultation flag
                    $calendarResult = GoogleCalendarController::createCalendarEvent($artistUserDetail, $booking, $requiresConsultation);
                    
                    if ($calendarResult && isset($calendarResult['event_id'])) {
                        $calendarEventId = $calendarResult['event_id'];
                        
                        // Only store Meet link if consultation is required
                        if ($requiresConsultation) {
                            $meetLink = $calendarResult['meet_link'] ?? null;
                        }
                        
                        // Add to action history
                        $historyEntry = [
                            'action' => 'calendar_event_created',
                            'user_id' => null,
                            'user_type' => 'system',
                            'timestamp' => now()->toDateTimeString(),
                            'calendar_event_id' => $calendarEventId,
                            'notes' => 'Google Calendar event created successfully',
                        ];
                        
                        // Add Meet link info only if consultation is required
                        if ($requiresConsultation && $meetLink) {
                            $historyEntry['meet_link'] = $meetLink;
                            $historyEntry['notes'] = 'Google Calendar event and Meet link created successfully';
                        }
                        
                        $actionHistory[] = $historyEntry;
                        
                        // Update booking with calendar event ID, Meet link (if consultation required), and updated action history
                        $updateData = [
                            'google_calendar_event_id' => $calendarEventId,
                            'action_history' => $actionHistory,
                        ];
                        if ($requiresConsultation && $meetLink) {
                            $updateData['google_meet_link'] = $meetLink;
                        }
                        $booking->update($updateData);
                        
                        Log::info('Google Calendar event created for booking', [
                            'booking_id' => $booking->id,
                            'event_id' => $calendarEventId,
                            'requires_consultation' => $requiresConsultation,
                            'meet_link' => $meetLink,
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

            // Refresh booking to get accessor attributes
            $booking->refresh();

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
                'booking_time' => $booking->booking_time,
                'consultation_time' => $booking->consultation_time,
                'consultation_timing_type' => $booking->consultation_timing_type,
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

    /**
     * Get consultation slots for separate consultation timing
     * 
     * @param Request $request
     * @param int $tattooId
     * @return JsonResponse
     */
    public function getConsultationSlots(Request $request, int $tattooId)
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

        // Get user detail
        $userDetail = $user->userDetail;
        if (!$userDetail || !$userDetail->require_consultation || $userDetail->consultation_timing !== 'separate') {
            return response()->json([
                'success' => false,
                'message' => 'Consultation slots not available for this tattoo',
            ], 400);
        }

        $timezone = $userDetail->timezone ?? 'UTC';
        $consultationDurationMinutes = (int) ($userDetail->session_duration_minutes ?? 30);
        $sessionTimeHours = $consultationDurationMinutes / 60;
        $bufferPeriodMinutes = (int) ($userDetail->session_buffer_period ?? 0);

        // Use existing slot generation logic but with consultation duration
        return $this->generateSlotsForDate($tattoo, $user, $userDetail, $dateKey, $timezone, $sessionTimeHours, $bufferPeriodMinutes, true);
    }

    /**
     * Get tattoo session slots with gap filtering for separate consultation timing
     * 
     * @param Request $request
     * @param int $tattooId
     * @return JsonResponse
     */
    public function getTattooSessionSlots(Request $request, int $tattooId)
    {
        // Validate parameters
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'consultation_date' => 'required|date',
            'consultation_start_time_utc' => 'required|string',
            'consultation_end_time_utc' => 'required|string',
        ]);

        $date = Carbon::parse($request->date);
        $dateKey = $date->format('Y-m-d');
        
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

        // Get user detail
        $userDetail = $user->userDetail;
        $timezone = $userDetail->timezone ?? 'UTC';
        $sessionTimeHours = $tattoo->session_time_h ?? 2;
        $bufferPeriodMinutes = (int) ($userDetail->session_buffer_period ?? 0);

        // Calculate minimum date/time from consultation end + gap
        $consultationEndTime = Carbon::parse(
            $request->consultation_date . ' ' . $request->consultation_end_time_utc,
            'UTC'
        );

        $gapRequired = $userDetail->require_gap_between_consultation_tattoo ?? false;
        $minimumDateTime = $consultationEndTime->copy();

        if ($gapRequired) {
            $gapValue = $userDetail->consultation_tattoo_gap_value ?? 0;
            $gapUnit = $userDetail->consultation_tattoo_gap_unit ?? 'days';
            $gapMinutes = $this->convertGapToMinutes($gapValue, $gapUnit);
            $minimumDateTime = $consultationEndTime->copy()->addMinutes($gapMinutes);
        }

        // Check if requested date is before minimum date
        $requestedDate = Carbon::parse($dateKey, 'UTC')->startOfDay();
        $minimumDate = $minimumDateTime->copy()->startOfDay();

        if ($requestedDate->lt($minimumDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected date is before the minimum allowed date',
                'minimum_date' => $minimumDateTime->format('Y-m-d'),
                'minimum_datetime' => $minimumDateTime->format('Y-m-d H:i:s'),
            ], 400);
        }

        // Generate slots with gap filtering
        // Pass minimum start time if:
        // 1. Same date as consultation (with or without gap)
        // 2. Different date but it's the minimum date (gap pushes to next day)
        $minimumStartTime = null;
        $isSameDate = Carbon::parse($request->consultation_date, 'UTC')->startOfDay()->eq($requestedDate);
        $isMinimumDate = $requestedDate->eq($minimumDate);
        
        if ($isSameDate) {
            if ($gapRequired) {
                // Minimum start time is consultation end + gap
                $minimumStartTime = $minimumDateTime->copy();
            } else {
                // No gap, start right after consultation ends
                $minimumStartTime = $consultationEndTime->copy();
            }
        } else if ($isMinimumDate && $gapRequired) {
            // Different date but it's the minimum date (gap requirement)
            // Minimum start time is the time portion of minimumDateTime (consultation end time on minimum date)
            $minimumStartTime = $minimumDateTime->copy();
        }
        
        $response = $this->generateSlotsForDate($tattoo, $user, $userDetail, $dateKey, $timezone, $sessionTimeHours, $bufferPeriodMinutes, false, $minimumStartTime);

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $data = json_decode($response->getContent(), true);

        // Filter slots based on minimum datetime and consultation overlap
        if (isset($data['time_slots'])) {
            $filteredSlots = [];
            $isMinimumDate = $requestedDate->eq($minimumDate);
            
            // Parse consultation times for overlap checking
            $consultationDate = Carbon::parse($request->consultation_date, 'UTC')->startOfDay();
            $consultationStartTime = Carbon::parse($request->consultation_date . ' ' . $request->consultation_start_time_utc, 'UTC');
            $consultationEndTime = Carbon::parse($request->consultation_date . ' ' . $request->consultation_end_time_utc, 'UTC');
            $isSameDate = $requestedDate->eq($consultationDate);

            foreach ($data['time_slots'] as $slot) {
                $slotStartTime = Carbon::parse($dateKey . ' ' . $slot['start_time_utc'], 'UTC');
                $slotEndTime = Carbon::parse($dateKey . ' ' . $slot['end_time_utc'], 'UTC');
                
                // Skip if slot doesn't meet minimum datetime requirement
                // This applies both when gap is required and when same date with no gap
                if ($isMinimumDate && $slotStartTime->lt($minimumDateTime)) {
                    continue;
                }
                
                // If same date as consultation, exclude slots that overlap with consultation time
                if ($isSameDate) {
                    // Check if slot overlaps with consultation time
                    // Overlap occurs if: slot starts before consultation ends AND slot ends after consultation starts
                    if ($slotStartTime->lt($consultationEndTime) && $slotEndTime->gt($consultationStartTime)) {
                        continue; // Skip overlapping slot
                    }
                    
                    // When no gap required and same date, ensure slot starts at or after consultation end time
                    if (!$gapRequired && $slotStartTime->lt($consultationEndTime)) {
                        continue; // Skip slots that start before consultation ends
                    }
                    
                    // When gap required and same date, ensure slot starts at or after minimum datetime (consultation end + gap)
                    if ($gapRequired && $slotStartTime->lt($minimumDateTime)) {
                        continue; // Skip slots that start before minimum datetime
                    }
                }
                
                    $filteredSlots[] = $slot;
            }

            $data['time_slots'] = $filteredSlots;
            $data['consultation_info'] = [
                'consultation_date' => $request->consultation_date,
                'consultation_end_time' => $request->consultation_end_time_utc,
                'consultation_start_time' => $consultationStartTime->format('H:i:s'),
                'gap_required' => $gapRequired,
                'gap_value' => $gapValue ?? null,
                'gap_unit' => $gapUnit ?? null,
                'minimum_tattoo_session_datetime' => $minimumDateTime->format('Y-m-d H:i:s'),
                'minimum_tattoo_session_date' => $minimumDateTime->format('Y-m-d'),
            ];
        }

        return response()->json($data);
    }

    /**
     * Book both consultation and tattoo session (separate timing flow)
     * 
     * @param Request $request
     * @param int $tattooId
     * @return JsonResponse
     */
    public function bookSeparateConsultation(Request $request, int $tattooId)
    {
        try {
            $requestData = $request->all();

            // Validate request
            $validator = Validator::make($requestData, [
                'consultation_slot.date' => 'required|date',
                'consultation_slot.start_time_utc' => 'required|string',
                'consultation_slot.end_time_utc' => 'required|string',
                'tattoo_session_slot.date' => 'required|date',
                'tattoo_session_slot.start_time_utc' => 'required|string',
                'tattoo_session_slot.end_time_utc' => 'required|string',
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'amount' => 'required|numeric|min:0',
                'currency' => 'required|string|min:3|max:3',
                'full_amount_paid' => 'nullable',
                'payment_intent_id' => 'required|string',
                'questions' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Get tattoo and artist
            $tattoo = InkJinTattoo::find($tattooId);
            if (!$tattoo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tattoo not found',
                ], 404);
            }

            $artist = $tattoo->artist;
            if (!$artist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Artist not found',
                ], 404);
            }

            $artistUser = User::where('app_id', $artist->id)->first();
            if (!$artistUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Artist user account not found',
                ], 404);
            }

            // Get or create customer user
            $customerEmail = $requestData['customer_email'];
            $customerName = $requestData['customer_name'];
            
            $customerUser = Auth::check() ? Auth::user() : null;
            
            if (!$customerUser) {
                $customerUser = User::where('email', $customerEmail)->first();
                
                if (!$customerUser) {
                    $customerUser = User::create([
                        'name' => $customerName,
                        'email' => $customerEmail,
                        'password' => bcrypt(str()->random(32)),
                        'email_verified_at' => null,
                    ]);
                }
            }

            // Get user detail
            $userDetail = $artistUser->userDetail;
            $timezone = $userDetail->timezone ?? 'UTC';

            // Validate gap requirement
            $consultationSlot = $requestData['consultation_slot'];
            $tattooSessionSlot = $requestData['tattoo_session_slot'];
            
            $consultationEndTime = Carbon::parse(
                $consultationSlot['date'] . ' ' . $consultationSlot['end_time_utc'],
                'UTC'
            );
            
            $tattooSessionStartTime = Carbon::parse(
                $tattooSessionSlot['date'] . ' ' . $tattooSessionSlot['start_time_utc'],
                'UTC'
            );

            $gapRequired = $userDetail->require_gap_between_consultation_tattoo ?? false;
            if ($gapRequired) {
                $gapValue = $userDetail->consultation_tattoo_gap_value ?? 0;
                $gapUnit = $userDetail->consultation_tattoo_gap_unit ?? 'days';
                $gapMinutes = $this->convertGapToMinutes($gapValue, $gapUnit);
                $minimumDateTime = $consultationEndTime->copy()->addMinutes($gapMinutes);

                if ($tattooSessionStartTime->lt($minimumDateTime)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected tattoo session slot is before the minimum allowed time',
                        'minimum_datetime' => $minimumDateTime->format('Y-m-d H:i:s'),
                    ], 400);
                }
            } else {
                // No gap required, but still check that tattoo session is after consultation
                if ($tattooSessionStartTime->lt($consultationEndTime)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tattoo session must be after consultation end time',
                    ], 400);
                }
            }

            // Calculate amounts
            $amount = (float) $requestData['amount'];
            $platformFee = 10.00;
            $totalAmount = $amount + $platformFee;
            $fullAmountPaid = $requestData['full_amount_paid'] ?? false;

            // Process questions answers
            $questionsAnswers = $requestData['questions'] ?? [];

            // Get artist settings
            $cancellationWindowRaw = $userDetail->cancellation_window ?? null;
            
            $cancellationWindowHours = null;
            if ($cancellationWindowRaw) {
                if (is_numeric($cancellationWindowRaw)) {
                    $cancellationWindowHours = (int) $cancellationWindowRaw;
                } else {
                    preg_match('/(\d+)/', (string) $cancellationWindowRaw, $matches);
                    if (!empty($matches[1])) {
                        $cancellationWindowHours = (int) $matches[1];
                    }
                }
            }

            // Calculate cancellation deadlines
            $consultationCancellationDeadline = null;
            $tattooSessionCancellationDeadline = null;
            if ($cancellationWindowHours) {
                $consultationDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $consultationSlot['date'] . ' ' . $consultationSlot['start_time_utc'], 'UTC');
                $consultationCancellationDeadline = $consultationDateTime->copy()->subHours($cancellationWindowHours);
                
                $tattooSessionDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $tattooSessionSlot['date'] . ' ' . $tattooSessionSlot['start_time_utc'], 'UTC');
                $tattooSessionCancellationDeadline = $tattooSessionDateTime->copy()->subHours($cancellationWindowHours);
            }

            // Create action history
            $actionHistory = [
                [
                    'action' => 'created',
                    'user_id' => $customerUser->id,
                    'user_type' => 'customer',
                    'timestamp' => now()->toDateTimeString(),
                    'notes' => 'Booking created via website (separate consultation timing)',
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

            // Create consultation booking
            $consultationBooking = Booking::create([
                'user_id' => $customerUser->id,
                'artist_user_id' => $artistUser->id,
                'tattoo_id' => $tattoo->id,
                'booking_type' => 'flash',
                'booking_date' => $consultationSlot['date'],
                'start_time_utc' => $consultationSlot['start_time_utc'],
                'end_time_utc' => $consultationSlot['end_time_utc'],
                'timezone' => $timezone,
                'status' => 'confirmed',
                'payment_intent_id' => $requestData['payment_intent_id'],
                'payment_status' => 'paid',
                'deposit_amount' => 0, // Consultation is part of total payment
                'full_amount_paid' => false,
                'platform_fee' => 0, // Platform fee applied to tattoo session only
                'total_amount_paid' => 0, // No separate payment for consultation
                'currency' => strtoupper($requestData['currency']),
                'questions_answers' => null,
                'cancellation_deadline' => $consultationCancellationDeadline,
                'cancellation_window_hours' => $cancellationWindowHours,
                'action_history' => $actionHistory,
                'consultation_timing_type' => 'separate',
                'has_consultation' => true,
                'consultation_date' => $consultationSlot['date'],
                'consultation_start_time_utc' => $consultationSlot['start_time_utc'],
                'consultation_end_time_utc' => $consultationSlot['end_time_utc'],
                'consultation_completed' => false,
            ]);

            // Create tattoo session booking
            $tattooSessionBooking = Booking::create([
                'user_id' => $customerUser->id,
                'artist_user_id' => $artistUser->id,
                'tattoo_id' => $tattoo->id,
                'booking_type' => 'flash',
                'booking_date' => $tattooSessionSlot['date'],
                'start_time_utc' => $tattooSessionSlot['start_time_utc'],
                'end_time_utc' => $tattooSessionSlot['end_time_utc'],
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
                'cancellation_deadline' => $tattooSessionCancellationDeadline,
                'cancellation_window_hours' => $cancellationWindowHours,
                'action_history' => $actionHistory,
                'consultation_timing_type' => 'separate',
                'consultation_booking_id' => $consultationBooking->id,
            ]);

            // Create Google Calendar events
            $consultationCalendarEventId = null;
            $consultationMeetLink = null;
            $tattooSessionCalendarEventId = null;

            try {
                $artistUserDetail = $artistUser->userDetail;
                
                if ($artistUserDetail && $artistUserDetail->google_calendar_token && $artistUserDetail->google_calendar_id) {
                    // Create consultation calendar event with Meet link
                    $consultationCalendarResult = GoogleCalendarController::createCalendarEvent($artistUserDetail, $consultationBooking, true);
                    
                    if ($consultationCalendarResult && isset($consultationCalendarResult['event_id'])) {
                        $consultationCalendarEventId = $consultationCalendarResult['event_id'];
                        $consultationMeetLink = $consultationCalendarResult['meet_link'] ?? null;
                        
                        $consultationBooking->update([
                            'google_calendar_event_id' => $consultationCalendarEventId,
                            'google_meet_link' => $consultationMeetLink,
                        ]);
                    }

                    // Create tattoo session calendar event (no Meet link)
                    $tattooSessionCalendarResult = GoogleCalendarController::createCalendarEvent($artistUserDetail, $tattooSessionBooking, false);
                    
                    if ($tattooSessionCalendarResult && isset($tattooSessionCalendarResult['event_id'])) {
                        $tattooSessionCalendarEventId = $tattooSessionCalendarResult['event_id'];
                        
                        $tattooSessionBooking->update([
                            'google_calendar_event_id' => $tattooSessionCalendarEventId,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to create Google Calendar events (non-critical)', [
                    'consultation_booking_id' => $consultationBooking->id,
                    'tattoo_session_booking_id' => $tattooSessionBooking->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Send confirmation emails
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

            // Send consultation confirmation email
            try {
                Mail::to($customerUser->email)->send(
                    new BookingConfirmationMail($consultationBooking, false, $questionTexts)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send consultation confirmation email: ' . $e->getMessage());
            }

            sleep(2);

            // Send tattoo session confirmation email
            try {
                Mail::to($customerUser->email)->send(
                    new BookingConfirmationMail($tattooSessionBooking, false, $questionTexts)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send tattoo session confirmation email: ' . $e->getMessage());
            }

            // Refresh bookings to get accessor attributes
            $consultationBooking->refresh();
            $tattooSessionBooking->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Bookings confirmed successfully',
                'consultation_booking_id' => $consultationBooking->id,
                'tattoo_session_booking_id' => $tattooSessionBooking->id,
                'consultation_time' => $consultationBooking->consultation_time,
                'tattoo_session_time' => $tattooSessionBooking->booking_time,
                'consultation_meet_link' => $consultationMeetLink,
            ]);
        } catch (\Exception $e) {
            Log::error('Separate consultation booking error: ' . $e->getMessage(), [
                'tattoo_id' => $tattooId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bookings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper function to generate slots for a given date
     * 
     * @param InkJinTattoo $tattoo
     * @param User $user
     * @param UserDetail $userDetail
     * @param string $dateKey
     * @param string $timezone
     * @param float $sessionTimeHours
     * @param int $bufferPeriodMinutes
     * @param bool $isConsultation
     * @return JsonResponse
     */
    private function generateSlotsForDate($tattoo, $user, $userDetail, $dateKey, $timezone, $sessionTimeHours, $bufferPeriodMinutes, $isConsultation = false, $minimumStartTime = null)
    {
        $artist = $tattoo->artist;
        
        // Get day of week in artist's timezone
        $parsedDate = Carbon::parse($dateKey);
        $dateInArtistTimezone = Carbon::create(
            $parsedDate->year,
            $parsedDate->month,
            $parsedDate->day,
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

        // Check for date override
        $override = AvailabilityOverride::where('user_id', $user->id)
            ->where('override_date', $dateKey)
            ->first();

        $isUnavailable = false;
        $availabilityWindows = [];

        if ($override) {
            if ($override->is_unavailable) {
                $isUnavailable = true;
            } else {
                if ($override->start_time && $override->end_time) {
                    $availabilityWindows[] = [
                        'start_time' => $override->start_time,
                        'end_time' => $override->end_time,
                        'type' => 'override',
                    ];
                }
            }
        } else {
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

        // Get Google Calendar events
        $calendarEvents = [];
        if ($userDetail && $userDetail->google_calendar_token && $userDetail->google_calendar_id) {
            try {
                $calendarEvents = GoogleCalendarController::getEventsForDate($userDetail, $dateKey, $timezone);
            } catch (\Exception $e) {
                Log::warning('Failed to fetch Google Calendar events: ' . $e->getMessage());
            }
        }

        // Get existing bookings
        $existingBookings = Booking::where('artist_user_id', $user->id)
            ->where('booking_date', $dateKey)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        // Generate time slots
        $nowUTC = Carbon::now('UTC');
        $selectedDateUTC = Carbon::createFromFormat('Y-m-d', $dateKey, 'UTC')->startOfDay();
        $isToday = $selectedDateUTC->isSameDay($nowUTC);
        $timeSlots = [];
        
        if (!$isUnavailable && !empty($availabilityWindows)) {
            $slotDurationMinutes = $sessionTimeHours * 60;
            $slotInterval = 30;
            
            foreach ($availabilityWindows as $window) {
                $windowStartUTC = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $window['start_time'], 'UTC');
                $windowEndUTC = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $window['end_time'], 'UTC');
                
                // Determine starting time for slot generation
                if ($minimumStartTime && $minimumStartTime->gte($windowStartUTC) && $minimumStartTime->lt($windowEndUTC)) {
                    // Use minimum start time if provided and within window
                    $currentSlotStart = $minimumStartTime->copy();
                } else if ($isToday && $windowStartUTC->lt($nowUTC)) {
                    $currentSlotStart = $nowUTC->copy()->addMinutes($slotInterval - ($nowUTC->minute % $slotInterval));
                    if ($currentSlotStart->lt($windowStartUTC)) {
                        $currentSlotStart = $windowStartUTC->copy();
                    }
                    // If minimum start time is later, use that instead
                    if ($minimumStartTime && $minimumStartTime->gt($currentSlotStart)) {
                        $currentSlotStart = $minimumStartTime->copy();
                    }
                } else {
                    $currentSlotStart = $windowStartUTC->copy();
                    // If minimum start time is later, use that instead
                    if ($minimumStartTime && $minimumStartTime->gt($currentSlotStart) && $minimumStartTime->lt($windowEndUTC)) {
                        $currentSlotStart = $minimumStartTime->copy();
                    }
                }
                
                while ($currentSlotStart->copy()->addMinutes($slotDurationMinutes)->lte($windowEndUTC)) {
                    $slotEndTime = $currentSlotStart->copy()->addMinutes($slotDurationMinutes);
                    
                    if ($slotEndTime->lte($windowEndUTC)) {
                        if ($isToday && $currentSlotStart->lte($nowUTC)) {
                            $currentSlotStart->addMinutes($slotInterval);
                            continue;
                        }
                        
                        // Check calendar overlap
                        $hasCalendarOverlap = false;
                        $earliestNextStartAfterCalendar = null;
                        foreach ($calendarEvents as $event) {
                            $eventStart = $event['start_datetime_utc'];
                            $eventEnd = $event['end_datetime_utc'];
                            
                            if ($currentSlotStart->lt($eventEnd) && $slotEndTime->gt($eventStart)) {
                                $hasCalendarOverlap = true;
                                if ($earliestNextStartAfterCalendar === null || $eventEnd->gt($earliestNextStartAfterCalendar)) {
                                    $earliestNextStartAfterCalendar = $eventEnd->copy();
                                }
                                break;
                            }
                        }
                        
                        // Check booking overlap
                        $hasBookingOverlap = false;
                        $earliestNextStartAfterBooking = null;
                        foreach ($existingBookings as $existingBooking) {
                            $bookingStartUTC = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $existingBooking->start_time_utc, 'UTC');
                            $bookingEndUTC = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $existingBooking->end_time_utc, 'UTC');
                            $bookingEndWithBuffer = $bookingEndUTC->copy()->addMinutes($bufferPeriodMinutes);
                            
                            if ($currentSlotStart->lt($bookingEndWithBuffer) && $slotEndTime->gt($bookingStartUTC)) {
                                $hasBookingOverlap = true;
                                if ($earliestNextStartAfterBooking === null || $bookingEndWithBuffer->gt($earliestNextStartAfterBooking)) {
                                    $earliestNextStartAfterBooking = $bookingEndWithBuffer->copy();
                                }
                            }
                        }
                        
                        if (!$hasCalendarOverlap && !$hasBookingOverlap) {
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
                            
                            $currentSlotStart->addMinutes($slotInterval);
                        } else {
                            $nextValidStart = null;
                            
                            if ($hasBookingOverlap && $earliestNextStartAfterBooking) {
                                $nextValidStart = $earliestNextStartAfterBooking->copy();
                            }
                            
                            if ($hasCalendarOverlap && $earliestNextStartAfterCalendar) {
                                $calendarNextStart = $earliestNextStartAfterCalendar->copy();
                                if ($nextValidStart === null || $calendarNextStart->gt($nextValidStart)) {
                                    $nextValidStart = $calendarNextStart->copy();
                                }
                            }
                            
                            if ($nextValidStart && $nextValidStart->gt($currentSlotStart)) {
                                if ($nextValidStart->second > 0) {
                                    $nextValidStart->addMinute()->second(0);
                                } else {
                                    $nextValidStart->second(0);
                                }
                                $currentSlotStart = $nextValidStart->copy();
                            } else {
                                $currentSlotStart->addMinutes($slotInterval);
                            }
                        }
                    } else {
                        $currentSlotStart->addMinutes($slotInterval);
                    }
                    
                    if ($currentSlotStart->copy()->addMinutes($slotDurationMinutes)->gt($windowEndUTC)) {
                        break;
                    }
                }
            }
        }

        // Get questions
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
            ],
            'artist' => [
                'id' => $artist->id,
                'artist_handle' => $artist->artist_handle,
                'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
            ],
            'time_slots' => $timeSlots,
            'questions' => $questions,
        ]);
    }

    /**
     * Convert gap value and unit to minutes
     * 
     * @param int $value
     * @param string $unit
     * @return int
     */
    private function convertGapToMinutes($value, $unit)
    {
        switch ($unit) {
            case 'minutes':
                return $value;
            case 'hours':
                return $value * 60;
            case 'days':
                return $value * 24 * 60;
            default:
                return 0;
        }
    }
}

