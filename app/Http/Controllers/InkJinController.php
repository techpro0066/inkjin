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
use Carbon\Carbon;

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

        // Get user detail for timezone
        $userDetail = $user->userDetail;
        $timezone = $userDetail->timezone ?? 'UTC';

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

        // Generate time slots based on availability windows and session_time_h
        // Logic: Generate slots that fit within the availability window
        // Example: Available 6:00-13:00, slot duration 3 hours
        // Last valid slot: 10:00-13:00 (ends exactly at 13:00)
        $timeSlots = [];
        
        if (!$isUnavailable && !empty($availabilityWindows)) {
            $slotDurationMinutes = $sessionTimeHours * 60;
            $slotInterval = 30; // 30-minute intervals between slots for flexibility
            
            foreach ($availabilityWindows as $window) {
                // Parse start and end times (already in UTC format H:i:s)
                $windowStartUTC = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $window['start_time'], 'UTC');
                $windowEndUTC = Carbon::createFromFormat('Y-m-d H:i:s', $dateKey . ' ' . $window['end_time'], 'UTC');
                
                // Start generating slots from the beginning of the availability window
                $currentSlotStart = $windowStartUTC->copy();
                
                // Continue while we can fit a full slot duration before the window ends
                // The last slot will end exactly at windowEndUTC (or earlier)
                while ($currentSlotStart->copy()->addMinutes($slotDurationMinutes)->lte($windowEndUTC)) {
                    $slotEndTime = $currentSlotStart->copy()->addMinutes($slotDurationMinutes);
                    
                    // Ensure slot doesn't exceed the availability window
                    // This check ensures the slot ends at or before the window end time
                    if ($slotEndTime->lte($windowEndUTC)) {
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
                    }
                    
                    // Move to next slot start time (30-minute intervals)
                    $currentSlotStart->addMinutes($slotInterval);
                    
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

        // Build validation rules
        $rules = [];
        $customMessages = [];
        
        foreach ($questions as $question) {
            $fieldName = "questions.{$question->id}";
            $rules[$fieldName] = 'required';
            
            if ($question->type === 'image') {
                $rules[$fieldName] = 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120'; // 5MB max
            } else if ($question->type === 'free') {
                $rules[$fieldName] = 'required|string|max:5000';
            } else if (in_array($question->type, ['select', 'radio'])) {
                $rules[$fieldName] = 'required|string';
            }
            
            $customMessages["{$fieldName}.required"] = "Please answer: {$question->question}";
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

        // TODO: Save booking to database
        // For now, return success
        return response()->json([
            'success' => true,
            'message' => 'Booking submitted successfully!',
            'data' => [
                'tattoo_id' => $tattoo->id,
                'slot' => $request->input('slot'),
                'answers' => $request->input('questions'),
            ],
        ]);
    }
}

