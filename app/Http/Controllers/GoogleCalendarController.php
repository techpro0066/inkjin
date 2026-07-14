<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;
use Google\Service\Calendar\FreeBusyRequest;
use Google\Service\Calendar\FreeBusyRequestItem;
use GuzzleHttp\Client as GuzzleClient;

class GoogleCalendarController extends Controller
{
    private const RETURN_SESSION_KEY = 'google_calendar_return_to';

    private function storeCalendarReturnTo(Request $request): void
    {
        $returnTo = $request->query('return_to') === 'settings' ? 'settings' : 'onboarding';

        session([self::RETURN_SESSION_KEY => $returnTo]);
    }

    private function calendarReturnUrl(?string $returnTo = null): string
    {
        $returnTo ??= session(self::RETURN_SESSION_KEY, 'onboarding');

        return $returnTo === 'settings'
            ? route('settings.calendar')
            : route('onboarding.calendar');
    }

    private function pullCalendarReturnUrl(): string
    {
        $returnTo = session()->pull(self::RETURN_SESSION_KEY, 'onboarding');

        return $this->calendarReturnUrl($returnTo);
    }

    /**
     * Redirect user to Google OAuth consent screen
     */
    public function redirect(Request $request)
    {
        try {
            $this->storeCalendarReturnTo($request);

            $client = new Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setRedirectUri(config('services.google.redirect'));
            $client->addScope(Google_Service_Calendar::CALENDAR);
            $client->setAccessType('offline');
            $client->setPrompt('consent'); // Force consent screen to get refresh token

            $authUrl = $client->createAuthUrl();

            // Store state to verify on callback (optional but recommended)
            session(['google_oauth_state' => uniqid()]);

            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Google Calendar redirect error: ' . $e->getMessage());
            return redirect()->to($this->calendarReturnUrl())
                ->with('error', 'Failed to connect to Google Calendar. Please try again.');
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            $code = $request->get('code');

            if (!$code) {
                return redirect()->to($this->pullCalendarReturnUrl())
                    ->with('error', 'Google Calendar authorization was cancelled.');
            }

            $client = new Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setRedirectUri(config('services.google.redirect'));

            // Exchange authorization code for tokens
            $accessToken = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($accessToken['error'])) {
                Log::error('Google OAuth error: ' . $accessToken['error']);
                return redirect()->to($this->pullCalendarReturnUrl())
                    ->with('error', 'Failed to connect Google Calendar: ' . $accessToken['error']);
            }

            // Store tokens
            $client->setAccessToken($accessToken);

            // Get user's primary calendar ID
            $service = new Google_Service_Calendar($client);
            $calendarList = $service->calendarList->listCalendarList();
            
            $primaryCalendarId = 'primary'; // Default to primary calendar
            
            // Try to find the primary calendar
            foreach ($calendarList->getItems() as $calendar) {
                if ($calendar->getPrimary()) {
                    $primaryCalendarId = $calendar->getId();
                    break;
                }
            }

            // Get authenticated user
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login')
                    ->with('error', 'Please log in to continue.');
            }

            // Get or create user detail
            $userDetail = $user->userDetail;
            if (!$userDetail) {
                $userDetail = UserDetail::create(['user_id' => $user->id]);
            }

            // Verify refresh_token exists - critical for long-term connection
            if (!isset($accessToken['refresh_token'])) {
                Log::warning('No refresh token provided by Google - user may need to reconnect later', [
                    'user_id' => $user->id,
                    'access_token_keys' => array_keys($accessToken),
                    'has_refresh_token' => false
                ]);
                
                // Check if we have an existing refresh token to preserve
                $existingToken = $userDetail->google_calendar_token;
                if ($existingToken) {
                    $existingTokenArray = is_array($existingToken) 
                        ? $existingToken 
                        : json_decode($existingToken, true);
                    
                    if (isset($existingTokenArray['refresh_token'])) {
                        // Preserve existing refresh token
                        $accessToken['refresh_token'] = $existingTokenArray['refresh_token'];
                        Log::info('Preserved existing refresh token', ['user_id' => $user->id]);
                    }
                }
            } else {
                Log::info('Refresh token received from Google', ['user_id' => $user->id]);
            }

            // Store tokens and calendar ID
            $returnTo = session(self::RETURN_SESSION_KEY, 'onboarding');

            $updateData = [
                'google_calendar_token' => $accessToken,
                'google_calendar_id' => $primaryCalendarId,
            ];

            if ($returnTo === 'settings') {
                $updateData['scheduling_type'] = 'auto';
            }

            $userDetail->update($updateData);

            session()->forget(self::RETURN_SESSION_KEY);

            $successMessage = $returnTo === 'settings'
                ? 'Google Calendar connected successfully! Auto scheduling is now selected.'
                : 'Google Calendar connected successfully!';

            return redirect()->to($this->calendarReturnUrl($returnTo))
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            Log::error('Google Calendar callback error: ' . $e->getMessage());
            return redirect()->to($this->pullCalendarReturnUrl())
                ->with('error', 'Failed to connect Google Calendar. Please try again.');
        }
    }

    /**
     * Check Google Calendar connection status
     */
    public function checkStatus(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $userDetail = $user->userDetail;
            if (!$userDetail) {
                return response()->json([
                    'success' => true,
                    'connected' => false,
                    'needs_reconnection' => true,
                    'status' => 'not_connected'
                ]);
            }

            $status = self::getConnectionStatus($userDetail);

            return response()->json([
                'success' => true,
                'connected' => $status['connected'],
                'needs_reconnection' => $status['needs_reconnection'],
                'has_refresh_token' => $status['has_refresh_token'] ?? false,
                'has_access_token' => $status['has_access_token'] ?? false,
                'is_expired' => $status['is_expired'] ?? false,
                'reason' => $status['reason'],
                'status' => $status['connected'] ? 'connected' : ($status['needs_reconnection'] ? 'needs_reconnection' : 'not_connected')
            ]);
        } catch (\Exception $e) {
            Log::error('Google Calendar status check error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check connection status',
            ], 500);
        }
    }

    /**
     * Disconnect Google Calendar
     */
    public function disconnect(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $userDetail = $user->userDetail;
            if ($userDetail) {
                $userDetail->update([
                    'google_calendar_token' => null,
                    'google_calendar_id' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Google Calendar disconnected successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Google Calendar disconnect error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect Google Calendar',
            ], 500);
        }
    }

    /**
     * Get refresh token if expired
     * CRITICAL: This method preserves the refresh_token so users never need to reconnect
     */
    public function refreshToken($userDetail)
    {
        try {
            if (!$userDetail->google_calendar_token) {
                Log::warning('No Google Calendar token found for refresh', [
                    'user_id' => $userDetail->user_id ?? null
                ]);
                return null;
            }

            $token = is_array($userDetail->google_calendar_token) 
                ? $userDetail->google_calendar_token 
                : json_decode($userDetail->google_calendar_token, true);

            if (!$token) {
                Log::warning('Invalid token format for refresh', [
                    'user_id' => $userDetail->user_id ?? null
                ]);
                return null;
            }

            // CRITICAL: Check if refresh_token exists
            if (!isset($token['refresh_token']) || empty($token['refresh_token'])) {
                Log::error('Refresh token missing - user needs to reconnect Google Calendar', [
                    'user_id' => $userDetail->user_id ?? null,
                    'token_keys' => array_keys($token)
                ]);
                return null;
            }

            // Store refresh_token before refresh (Google doesn't return it in new token)
            $refreshToken = $token['refresh_token'];

            $client = new Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));

            // Attempt to refresh the token
            $client->refreshToken($refreshToken);
            $newToken = $client->getAccessToken();
            
            if ($newToken) {
                // CRITICAL: Preserve refresh_token in new token (Google doesn't return it)
                // This ensures we can refresh again in the future
                if (!isset($newToken['refresh_token'])) {
                    $newToken['refresh_token'] = $refreshToken;
                }

                // Update database with new token (including preserved refresh_token)
                $userDetail->update([
                    'google_calendar_token' => $newToken,
                ]);

                Log::info('Google Calendar token refreshed successfully', [
                    'user_id' => $userDetail->user_id,
                    'has_refresh_token' => isset($newToken['refresh_token'])
                ]);

                return $newToken;
            }

            Log::warning('Token refresh returned null', [
                'user_id' => $userDetail->user_id ?? null
            ]);
            return null;
        } catch (\Google_Service_Exception $e) {
            // Handle Google API specific errors
            $errorMessage = $e->getMessage();
            Log::error('Google Calendar token refresh API error', [
                'user_id' => $userDetail->user_id ?? null,
                'error' => $errorMessage,
                'code' => $e->getCode()
            ]);
            
            // If refresh token is invalid/revoked, user needs to reconnect
            if (strpos($errorMessage, 'invalid_grant') !== false || 
                strpos($errorMessage, 'invalid_request') !== false) {
                Log::error('Refresh token invalid or revoked - user must reconnect', [
                    'user_id' => $userDetail->user_id ?? null
                ]);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Google Calendar token refresh error', [
                'user_id' => $userDetail->user_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Check if Google Calendar needs reconnection
     * 
     * @param UserDetail $userDetail
     * @return bool True if reconnection is needed, false otherwise
     */
    public static function needsReconnection($userDetail): bool
    {
        if (!$userDetail || !$userDetail->google_calendar_token) {
            return true;
        }

        $token = is_array($userDetail->google_calendar_token) 
            ? $userDetail->google_calendar_token 
            : json_decode($userDetail->google_calendar_token, true);

        if (!$token) {
            return true;
        }

        // Check if refresh_token exists
        if (!isset($token['refresh_token']) || empty($token['refresh_token'])) {
            return true;
        }

        return false;
    }

    /**
     * Get connection status for Google Calendar
     * 
     * @param UserDetail $userDetail
     * @return array Status information
     */
    public static function getConnectionStatus($userDetail): array
    {
        if (!$userDetail || !$userDetail->google_calendar_token) {
            return [
                'connected' => false,
                'needs_reconnection' => true,
                'reason' => 'No token found'
            ];
        }

        $token = is_array($userDetail->google_calendar_token) 
            ? $userDetail->google_calendar_token 
            : json_decode($userDetail->google_calendar_token, true);

        if (!$token) {
            return [
                'connected' => false,
                'needs_reconnection' => true,
                'reason' => 'Invalid token format'
            ];
        }

        $hasRefreshToken = isset($token['refresh_token']) && !empty($token['refresh_token']);
        $hasAccessToken = isset($token['access_token']) && !empty($token['access_token']);

        // Check if access token is expired
        $isExpired = false;
        if ($hasAccessToken) {
            try {
                $client = new Google_Client();
                $client->setAccessToken($token);
                $isExpired = $client->isAccessTokenExpired();
            } catch (\Exception $e) {
                $isExpired = true;
            }
        }

        return [
            'connected' => $hasAccessToken && ($hasRefreshToken || !$isExpired),
            'needs_reconnection' => !$hasRefreshToken,
            'has_refresh_token' => $hasRefreshToken,
            'has_access_token' => $hasAccessToken,
            'is_expired' => $isExpired,
            'reason' => !$hasRefreshToken ? 'Refresh token missing' : ($isExpired ? 'Access token expired (will auto-refresh)' : 'Connected')
        ];
    }

    /**
     * Get Google Calendar events for a specific date
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getEventsForDate($userDetail, $date, $timezone = 'UTC')
    {
        return self::getEventsForDateRange($userDetail, $date, $date, $timezone);
    }

    /**
     * Get Google Calendar events for a date range (single API request with pagination).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getEventsForDateRange($userDetail, string $startDate, string $endDate, string $timezone = 'UTC'): array
    {
        try {
            $service = self::calendarServiceForUserDetail($userDetail);
            if (! $service) {
                return [];
            }

            $calendarId = $userDetail->google_calendar_id ?? 'primary';
            $startOfRange = Carbon::createFromFormat('Y-m-d', $startDate, $timezone)->startOfDay();
            $endOfRange = Carbon::createFromFormat('Y-m-d', $endDate, $timezone)->endOfDay();
            $timeMin = $startOfRange->copy()->setTimezone('UTC')->toRfc3339String();
            $timeMax = $endOfRange->copy()->setTimezone('UTC')->toRfc3339String();

            $eventList = [];
            $pageToken = null;

            do {
                $optParams = [
                    'timeMin' => $timeMin,
                    'timeMax' => $timeMax,
                    'singleEvents' => true,
                    'orderBy' => 'startTime',
                    'maxResults' => 250,
                    'timeZone' => $timezone,
                ];

                if ($pageToken) {
                    $optParams['pageToken'] = $pageToken;
                }

                $events = $service->events->listEvents($calendarId, $optParams);
                $eventList = array_merge($eventList, self::mapCalendarEventItems($events->getItems(), $timezone));
                $pageToken = $events->getNextPageToken();
            } while ($pageToken);

            return $eventList;
        } catch (\Exception $e) {
            Log::error('Google Calendar fetch events error: '.$e->getMessage(), [
                'user_id' => $userDetail->user_id ?? null,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return [];
        }
    }

    /**
     * Fetch busy blocks from all selected Google calendars (FreeBusy API).
     * Queries are chunked to stay within Google's ~90-day FreeBusy window.
     *
     * @return array<int, array{start_datetime_utc: Carbon, end_datetime_utc: Carbon}>
     */
    public static function getBusyBlocksForDateRange($userDetail, string $startDate, string $endDate, string $timezone = 'UTC'): array
    {
        try {
            $service = self::calendarServiceForUserDetail($userDetail);
            if (! $service) {
                return [];
            }

            $calendarIds = self::selectedCalendarIds($service, $userDetail);
            $rangeStart = Carbon::createFromFormat('Y-m-d', $startDate, $timezone)->startOfDay();
            $rangeEnd = Carbon::createFromFormat('Y-m-d', $endDate, $timezone)->endOfDay();
            $blocks = [];
            $chunkStart = $rangeStart->copy();
            $maxChunkDays = 90;

            while ($chunkStart->lte($rangeEnd)) {
                $chunkEnd = $chunkStart->copy()->addDays($maxChunkDays - 1)->endOfDay();
                if ($chunkEnd->gt($rangeEnd)) {
                    $chunkEnd = $rangeEnd->copy();
                }

                $blocks = array_merge(
                    $blocks,
                    self::queryFreeBusyBlocks($service, $calendarIds, $chunkStart, $chunkEnd, $timezone)
                );

                if ($chunkEnd->gte($rangeEnd)) {
                    break;
                }

                $chunkStart = $chunkEnd->copy()->addSecond()->startOfDay();
            }

            if ($blocks === []) {
                $events = self::getEventsForDateRange($userDetail, $startDate, $endDate, $timezone);
                foreach ($events as $event) {
                    $startUtc = $event['start_datetime_utc'] ?? null;
                    $endUtc = $event['end_datetime_utc'] ?? null;
                    if (! $startUtc || ! $endUtc) {
                        continue;
                    }

                    $blocks[] = [
                        'start_datetime_utc' => $startUtc instanceof Carbon ? $startUtc->copy() : Carbon::parse((string) $startUtc, 'UTC'),
                        'end_datetime_utc' => $endUtc instanceof Carbon ? $endUtc->copy() : Carbon::parse((string) $endUtc, 'UTC'),
                    ];
                }
            }

            return $blocks;
        } catch (\Exception $e) {
            Log::error('Google Calendar freebusy error: '.$e->getMessage(), [
                'user_id' => $userDetail->user_id ?? null,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return [];
        }
    }

    /**
     * @param  list<string>  $calendarIds
     * @return array<int, array{start_datetime_utc: Carbon, end_datetime_utc: Carbon}>
     */
    private static function queryFreeBusyBlocks(
        Google_Service_Calendar $service,
        array $calendarIds,
        Carbon $startOfRange,
        Carbon $endOfRange,
        string $timezone
    ): array {
        $request = new FreeBusyRequest();
        $request->setTimeMin($startOfRange->copy()->setTimezone('UTC')->toRfc3339String());
        $request->setTimeMax($endOfRange->copy()->setTimezone('UTC')->toRfc3339String());
        $request->setTimeZone($timezone);
        $request->setItems(array_map(static function (string $calendarId) {
            $item = new FreeBusyRequestItem();
            $item->setId($calendarId);

            return $item;
        }, $calendarIds));

        $response = $service->freebusy->query($request);
        $blocks = [];

        foreach ($response->getCalendars() ?? [] as $calendarBusy) {
            foreach ($calendarBusy->getBusy() ?? [] as $period) {
                $start = $period->getStart();
                $end = $period->getEnd();
                if (! $start || ! $end) {
                    continue;
                }

                try {
                    $blocks[] = [
                        'start_datetime_utc' => Carbon::parse($start)->utc(),
                        'end_datetime_utc' => Carbon::parse($end)->utc(),
                    ];
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return $blocks;
    }

    /**
     * @return list<string>
     */
    private static function selectedCalendarIds(Google_Service_Calendar $service, UserDetail $userDetail): array
    {
        $ids = [];

        try {
            $calendarList = $service->calendarList->listCalendarList([
                'minAccessRole' => 'reader',
                'showHidden' => false,
            ]);

            foreach ($calendarList->getItems() ?? [] as $calendar) {
                if ($calendar->getSelected()) {
                    $id = $calendar->getId();
                    if (is_string($id) && $id !== '') {
                        $ids[] = $id;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Google Calendar list failed while resolving busy calendars: '.$e->getMessage(), [
                'user_id' => $userDetail->user_id ?? null,
            ]);
        }

        if ($ids === []) {
            $fallback = $userDetail->google_calendar_id ?: 'primary';
            $ids[] = $fallback;
        }

        return array_values(array_unique($ids));
    }

    private static function calendarServiceForUserDetail(?UserDetail $userDetail): ?Google_Service_Calendar
    {
        if (! $userDetail || ! $userDetail->google_calendar_token) {
            return null;
        }

        $token = is_array($userDetail->google_calendar_token)
            ? $userDetail->google_calendar_token
            : json_decode($userDetail->google_calendar_token, true);

        if (! $token) {
            return null;
        }

        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setHttpClient(new GuzzleClient([
            'timeout' => 15,
            'connect_timeout' => 5,
        ]));
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $calendarController = new self();
            $newToken = $calendarController->refreshToken($userDetail);
            if (! $newToken) {
                return null;
            }

            $client->setAccessToken($newToken);
        }

        return new Google_Service_Calendar($client);
    }

    /**
     * @param  array<int, mixed>|null  $items
     * @return array<int, array<string, mixed>>
     */
    private static function mapCalendarEventItems(?array $items, string $timezone = 'UTC'): array
    {
        $eventList = [];

        foreach ($items ?? [] as $event) {
            if (method_exists($event, 'getStatus') && $event->getStatus() === 'cancelled') {
                continue;
            }

            if (method_exists($event, 'getTransparency') && $event->getTransparency() === 'transparent') {
                continue;
            }

            $start = $event->getStart();
            $end = $event->getEnd();

            if (! $start || ! $end) {
                continue;
            }

            $startDateTime = $start->getDateTime();
            $endDateTime = $end->getDateTime();

            if ($startDateTime && $endDateTime) {
                $startUTC = Carbon::parse($startDateTime)->utc();
                $endUTC = Carbon::parse($endDateTime)->utc();
            } else {
                $startDate = $start->getDate();
                $endDate = $end->getDate();
                if (! $startDate || ! $endDate) {
                    continue;
                }

                try {
                    $startUTC = Carbon::createFromFormat('Y-m-d', $startDate, $timezone)->startOfDay()->utc();
                    $endUTC = Carbon::createFromFormat('Y-m-d', $endDate, $timezone)->startOfDay()->utc();
                } catch (\Throwable) {
                    continue;
                }
            }

            if ($endUTC <= $startUTC) {
                continue;
            }

            $eventList[] = [
                'start_time_utc' => $startUTC->format('H:i:s'),
                'end_time_utc' => $endUTC->format('H:i:s'),
                'start_datetime_utc' => $startUTC,
                'end_datetime_utc' => $endUTC,
                'summary' => $event->getSummary() ?? 'Busy',
            ];
        }

        return $eventList;
    }

    /**
     * Whether a Google Meet link should be attached for this consultation.
     * Uses the artist's onboarding/preferences session_type (online / physical / both).
     */
    public static function shouldAttachGoogleMeet(
        UserDetail $userDetail,
        bool $hasConsultation,
        ?string $consultationType = null
    ): bool {
        if (! $hasConsultation) {
            return false;
        }

        $sessionType = strtolower(trim((string) ($userDetail->session_type ?? '')));

        if ($sessionType === 'physical') {
            return false;
        }

        if ($sessionType === 'online') {
            return true;
        }

        // both (or unset but consultation required): respect client-selected type when present
        $type = strtolower(trim((string) ($consultationType ?? '')));
        if (in_array($type, ['studio', 'physical'], true)) {
            return false;
        }
        if (in_array($type, ['video', 'online', 'phone'], true)) {
            return true;
        }

        // Artist allows online sessions (both) and type was not captured — attach Meet
        return $sessionType === 'both';
    }

    /**
     * Create a calendar event for a booking with Google Meet link
     * 
     * @param \App\Models\UserDetail $userDetail Artist's user detail
     * @param \App\Models\Booking $booking Booking instance
     * @param bool $requiresConsultation Whether booking includes a consultation
     * @param string|null $consultationType Client-selected type: video|phone|studio
     * @return array|null Array with 'event_id' and 'meet_link' keys, or null on failure
     */
    public static function createCalendarEvent($userDetail, $booking, $requiresConsultation = false, ?string $consultationType = null)
    {
        try {
            if (!$userDetail || !$userDetail->google_calendar_token || !$userDetail->google_calendar_id) {
                Log::info('Google Calendar not connected for artist', [
                    'user_id' => $userDetail->user_id ?? null,
                    'booking_id' => $booking->id ?? null,
                ]);
                return null;
            }

            $token = is_array($userDetail->google_calendar_token) 
                ? $userDetail->google_calendar_token 
                : json_decode($userDetail->google_calendar_token, true);

            if (!$token) {
                Log::warning('Invalid Google Calendar token', [
                    'user_id' => $userDetail->user_id ?? null,
                    'booking_id' => $booking->id ?? null,
                ]);
                return null;
            }

            $client = new Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken($token);

            // Check if token is expired and refresh if needed
            if ($client->isAccessTokenExpired()) {
                $calendarController = new self();
                $newToken = $calendarController->refreshToken($userDetail);
                if ($newToken) {
                    $client->setAccessToken($newToken);
                    Log::info('Token refreshed successfully for event creation', [
                        'user_id' => $userDetail->user_id,
                        'booking_id' => $booking->id,
                    ]);
                } else {
                    // Check if refresh token is missing
                    if (!isset($token['refresh_token']) || empty($token['refresh_token'])) {
                        Log::error('Refresh token missing - user needs to reconnect Google Calendar for event creation', [
                            'user_id' => $userDetail->user_id,
                            'booking_id' => $booking->id,
                        ]);
                    } else {
                        Log::error('Token refresh failed - refresh token may be invalid or revoked', [
                            'user_id' => $userDetail->user_id,
                            'booking_id' => $booking->id,
                        ]);
                    }
                    return null;
                }
            }

            $service = new Google_Service_Calendar($client);
            $calendarId = $userDetail->google_calendar_id ?? 'primary';

            // Get booking details
            $tattoo = $booking->relationLoaded('tattoo') || method_exists($booking, 'tattoo')
                ? $booking->tattoo
                : null;
            $customer = $booking->user;
            $artist = $booking->artist;
            $tattooTitle = $tattoo?->title ?: (method_exists($booking, 'displayTitle') ? $booking->displayTitle() : 'Custom Tattoo');
            if ($tattooTitle === '') {
                $tattooTitle = 'Custom Tattoo';
            }

            // Format event title - include consultation info for combined mode
            $isCombinedConsultation = $booking->consultation_timing_type === 'combined' && $booking->has_consultation;
            if ($isCombinedConsultation) {
                $eventTitle = 'Tattoo Session + Consultation: '.$tattooTitle;
            } else {
                $eventTitle = 'Tattoo Session: '.$tattooTitle;
            }

            // Format event description
            $customerName = trim((string) ($customer->name ?? ($customer->first_name ?? '').' '.($customer->last_name ?? '')));
            $description = "Customer: {$customerName} ({$customer->email})\n";
            $description .= "Tattoo: {$tattooTitle}\n";
            $description .= "Booking ID: #{$booking->id}\n";

            // Add consultation timing info for combined mode
            if ($isCombinedConsultation) {
                $tattooDurationHours = $tattoo?->session_time_h ?? $tattoo?->session_duration ?? 0;
                $consultationDurationMinutes = 0;
                if ($booking->consultation_start_time_utc && $booking->consultation_end_time_utc) {
                    $consultationStart = \Carbon\Carbon::createFromFormat('H:i:s', $booking->consultation_start_time_utc);
                    $consultationEnd = \Carbon\Carbon::createFromFormat('H:i:s', $booking->consultation_end_time_utc);
                    $consultationDurationMinutes = $consultationStart->diffInMinutes($consultationEnd);
                }
                $description .= "\nConsultation Timing: Combined\n";
                $description .= "Consultation Duration: {$consultationDurationMinutes} minutes\n";
                $description .= "Tattoo Session Duration: {$tattooDurationHours} hour(s)\n";
                $description .= 'Total Duration: '.($tattooDurationHours + ($consultationDurationMinutes / 60))." hour(s)\n";
            }
            
            $description .= "\n";
            
            // Add payment info
            $currencySymbol = self::getCurrencySymbol($booking->currency);
            $description .= "Payment: {$currencySymbol}" . number_format($booking->total_amount_paid, 2) . "\n";
            $description .= $booking->full_amount_paid ? "Full amount paid" : "Deposit: {$currencySymbol}" . number_format($booking->deposit_amount, 2);
            
            // Add questions/answers if available (answers already include question text).
            // Never block calendar creation if Q&A formatting fails.
            try {
                if (! empty($booking->questions_answers) && is_array($booking->questions_answers)) {
                    $description .= "\n\nCustomer Responses:\n";
                    foreach ($booking->questions_answers as $questionId => $answer) {
                        if ($questionId === '_contact') {
                            continue;
                        }

                        $answerPayload = is_array($answer) ? $answer : ['answer' => $answer];
                        $questionText = trim((string) ($answerPayload['question'] ?? ''));
                        if ($questionText === '') {
                            $questionText = 'Question #'.$questionId;
                        }
                        $answerType = (string) ($answerPayload['type'] ?? '');
                        $answerValue = $answerPayload['answer'] ?? '';

                        $imageCount = 0;
                        if ($answerType === 'image' || (is_array($answerValue) && $answerValue !== [] && preg_match('#^(https?://|/uploads/|uploads/)#i', (string) reset($answerValue)))) {
                            $imageCount = is_array($answerValue) ? count($answerValue) : 1;
                        } elseif (is_string($answerValue) && preg_match('#^(https?://|/uploads/|uploads/)#i', trim($answerValue))) {
                            $imageCount = 1;
                        }

                        if ($imageCount > 0) {
                            $photoLabels = [];
                            for ($photoIndex = 1; $photoIndex <= $imageCount; $photoIndex++) {
                                $photoLabels[] = 'Photo '.$photoIndex;
                            }
                            $description .= "\n{$questionText}: ".implode(', ', $photoLabels)."\n";
                        } else {
                            $answerText = is_array($answerValue) ? implode(', ', $answerValue) : (string) $answerValue;
                            $answerText = trim($answerText);
                            if ($answerText === '') {
                                continue;
                            }
                            $description .= "\n{$questionText}: {$answerText}\n";
                        }
                    }
                }
            } catch (\Throwable $qaError) {
                Log::warning('Skipped questions_answers in Google Calendar description', [
                    'booking_id' => $booking->id ?? null,
                    'error' => $qaError->getMessage(),
                ]);
            }

            // Create datetime objects for the booking
            // Get date as string (Y-m-d format)
            $bookingDateStr = $booking->booking_date instanceof \Carbon\Carbon 
                ? $booking->booking_date->format('Y-m-d')
                : Carbon::parse($booking->booking_date)->format('Y-m-d');
            
            // Format time strings - TIME fields may include microseconds, so we extract just H:i:s
            $startTimeStr = is_string($booking->start_time_utc) 
                ? explode('.', $booking->start_time_utc)[0] // Remove microseconds if present
                : (string)$booking->start_time_utc;
            $endTimeStr = is_string($booking->end_time_utc) 
                ? explode('.', $booking->end_time_utc)[0] // Remove microseconds if present
                : (string)$booking->end_time_utc;
            
            // Create datetime objects - Carbon::parse handles various formats and is more forgiving
            $startDateTime = Carbon::parse($bookingDateStr . ' ' . $startTimeStr, 'UTC');
            $endDateTime = Carbon::parse($bookingDateStr . ' ' . $endTimeStr, 'UTC');

            // Convert to artist's timezone for display
            $timezone = $booking->timezone ?? 'UTC';
            $startDateTimeLocal = $startDateTime->copy()->setTimezone($timezone);
            $endDateTimeLocal = $endDateTime->copy()->setTimezone($timezone);

            // Create Google Calendar event
            $event = new \Google_Service_Calendar_Event();
            $event->setSummary($eventTitle);
            $event->setDescription($description);
            
            // Set start time (in UTC, but specify timezone)
            $start = new \Google_Service_Calendar_EventDateTime();
            $start->setDateTime($startDateTime->toRfc3339String());
            $start->setTimeZone('UTC');
            $event->setStart($start);
            
            // Set end time (in UTC, but specify timezone)
            $end = new \Google_Service_Calendar_EventDateTime();
            $end->setDateTime($endDateTime->toRfc3339String());
            $end->setTimeZone('UTC');
            $event->setEnd($end);
            
            // Set location (artist's studio address if available)
            if ($userDetail->studio_address) {
                $event->setLocation($userDetail->studio_address);
            }
            
            // Add customer email as attendee
            $attendee = new \Google_Service_Calendar_EventAttendee();
            $attendee->setEmail($customer->email);
            $attendee->setDisplayName($customer->name);
            $event->setAttendees([$attendee]);
            
            // Set reminders (15 minutes before)
            $reminder = new \Google_Service_Calendar_EventReminder();
            $reminder->setMethod('email');
            $reminder->setMinutes(15);
            $eventReminders = new \Google_Service_Calendar_EventReminders();
            $eventReminders->setUseDefault(false);
            $eventReminders->setOverrides([$reminder]);
            $event->setReminders($eventReminders);

            // Enable Google Meet when artist offers online consultation (session_type preference)
            $attachGoogleMeet = self::shouldAttachGoogleMeet(
                $userDetail,
                (bool) $requiresConsultation,
                $consultationType
            );

            if ($attachGoogleMeet) {
                $conferenceData = new \Google_Service_Calendar_ConferenceData();
                $createRequest = new \Google_Service_Calendar_CreateConferenceRequest();
                $createRequest->setRequestId(uniqid()); // Unique request ID required
                $conferenceSolutionKey = new \Google_Service_Calendar_ConferenceSolutionKey();
                $conferenceSolutionKey->setType('hangoutsMeet');
                $createRequest->setConferenceSolutionKey($conferenceSolutionKey);
                $conferenceData->setCreateRequest($createRequest);
                $event->setConferenceData($conferenceData);
            }

            // Insert event (with conferenceDataVersion only if Meet is enabled)
            $insertParams = [];
            if ($attachGoogleMeet) {
                $insertParams['conferenceDataVersion'] = 1; // Required to enable Google Meet
            }
            $createdEvent = $service->events->insert($calendarId, $event, $insertParams);
            $eventId = $createdEvent->getId();

            // Extract Google Meet link from the created event (only if Meet was requested)
            $meetLink = null;
            if ($attachGoogleMeet && $createdEvent->getConferenceData() && $createdEvent->getConferenceData()->getEntryPoints()) {
                $entryPoints = $createdEvent->getConferenceData()->getEntryPoints();
                if (!empty($entryPoints) && isset($entryPoints[0])) {
                    $meetLink = $entryPoints[0]->getUri();
                }
            }

            Log::info('Google Calendar event created successfully', [
                'booking_id' => $booking->id,
                'event_id' => $eventId,
                'artist_user_id' => $artist->id,
                'requires_consultation' => $requiresConsultation,
                'attach_google_meet' => $attachGoogleMeet,
                'session_type' => $userDetail->session_type,
                'consultation_type' => $consultationType,
                'meet_link' => $meetLink,
            ]);

            // Return both event ID and Meet link (Meet link will be null if not online)
            return [
                'event_id' => $eventId,
                'meet_link' => $meetLink
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create Google Calendar event', [
                'booking_id' => $booking->id ?? null,
                'user_id' => $userDetail->user_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Delete a calendar event
     * 
     * @param \App\Models\UserDetail $userDetail Artist's user detail
     * @param string $eventId Google Calendar event ID
     * @return bool Success status
     */
    public static function deleteCalendarEvent($userDetail, $eventId)
    {
        try {
            if (!$userDetail || !$userDetail->google_calendar_token || !$userDetail->google_calendar_id) {
                Log::info('Google Calendar not connected for event deletion', [
                    'user_id' => $userDetail->user_id ?? null,
                    'event_id' => $eventId,
                ]);
                return false;
            }

            $token = is_array($userDetail->google_calendar_token) 
                ? $userDetail->google_calendar_token 
                : json_decode($userDetail->google_calendar_token, true);

            if (!$token) {
                Log::warning('Invalid Google Calendar token for event deletion', [
                    'user_id' => $userDetail->user_id ?? null,
                    'event_id' => $eventId,
                ]);
                return false;
            }

            $client = new Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken($token);

            // Check if token is expired and refresh if needed
            if ($client->isAccessTokenExpired()) {
                $calendarController = new self();
                $newToken = $calendarController->refreshToken($userDetail);
                if ($newToken) {
                    $client->setAccessToken($newToken);
                    Log::info('Token refreshed successfully for event deletion', [
                        'user_id' => $userDetail->user_id,
                        'event_id' => $eventId,
                    ]);
                } else {
                    // Check if refresh token is missing
                    if (!isset($token['refresh_token']) || empty($token['refresh_token'])) {
                        Log::error('Refresh token missing - user needs to reconnect Google Calendar for event deletion', [
                            'user_id' => $userDetail->user_id,
                            'event_id' => $eventId,
                        ]);
                    } else {
                        Log::error('Token refresh failed - refresh token may be invalid or revoked', [
                            'user_id' => $userDetail->user_id,
                            'event_id' => $eventId,
                        ]);
                    }
                    return false;
                }
            }

            $service = new Google_Service_Calendar($client);
            $calendarId = $userDetail->google_calendar_id ?? 'primary';

            // Delete event
            $service->events->delete($calendarId, $eventId);

            Log::info('Google Calendar event deleted successfully', [
                'event_id' => $eventId,
                'artist_user_id' => $userDetail->user_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete Google Calendar event', [
                'event_id' => $eventId,
                'user_id' => $userDetail->user_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Update Google Calendar event for rescheduled booking
     * 
     * @param UserDetail $userDetail Artist's user detail
     * @param string $eventId Google Calendar event ID
     * @param string $newDate New booking date (Y-m-d format)
     * @param string $newStartTimeUtc New start time (H:i:s format in UTC)
     * @param string $newEndTimeUtc New end time (H:i:s format in UTC)
     * @return bool Success status
     */
    public static function updateCalendarEvent($userDetail, $eventId, $newDate, $newStartTimeUtc, $newEndTimeUtc)
    {
        try {
            if (!$userDetail || !$userDetail->google_calendar_token || !$userDetail->google_calendar_id) {
                Log::info('Google Calendar not connected for event update', [
                    'user_id' => $userDetail->user_id ?? null,
                    'event_id' => $eventId,
                ]);
                return false;
            }

            $token = is_array($userDetail->google_calendar_token) 
                ? $userDetail->google_calendar_token 
                : json_decode($userDetail->google_calendar_token, true);

            if (!$token) {
                Log::warning('Invalid Google Calendar token for event update', [
                    'user_id' => $userDetail->user_id ?? null,
                    'event_id' => $eventId,
                ]);
                return false;
            }

            $client = new Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken($token);

            // Check if token is expired and refresh if needed
            if ($client->isAccessTokenExpired()) {
                $calendarController = new self();
                $newToken = $calendarController->refreshToken($userDetail);
                if ($newToken) {
                    $client->setAccessToken($newToken);
                    Log::info('Token refreshed successfully for event update', [
                        'user_id' => $userDetail->user_id,
                        'event_id' => $eventId,
                    ]);
                } else {
                    if (!isset($token['refresh_token']) || empty($token['refresh_token'])) {
                        Log::error('Refresh token missing - user needs to reconnect Google Calendar for event update', [
                            'user_id' => $userDetail->user_id,
                            'event_id' => $eventId,
                        ]);
                    } else {
                        Log::error('Token refresh failed - refresh token may be invalid or revoked', [
                            'user_id' => $userDetail->user_id,
                            'event_id' => $eventId,
                        ]);
                    }
                    return false;
                }
            }

            $service = new Google_Service_Calendar($client);
            $calendarId = $userDetail->google_calendar_id ?? 'primary';

            // Get existing event
            $event = $service->events->get($calendarId, $eventId);

            // Update date/time
            $timezone = $userDetail->timezone ?? 'UTC';
            
            $startDateTime = new \Google_Service_Calendar_EventDateTime();
            $startDateTime->setDateTime(
                Carbon::parse($newDate . ' ' . $newStartTimeUtc, 'UTC')
                    ->setTimezone($timezone)
                    ->toRfc3339String()
            );
            $startDateTime->setTimeZone($timezone);

            $endDateTime = new \Google_Service_Calendar_EventDateTime();
            $endDateTime->setDateTime(
                Carbon::parse($newDate . ' ' . $newEndTimeUtc, 'UTC')
                    ->setTimezone($timezone)
                    ->toRfc3339String()
            );
            $endDateTime->setTimeZone($timezone);

            $event->setStart($startDateTime);
            $event->setEnd($endDateTime);

            // Update event description to reflect reschedule
            $description = $event->getDescription() ?? '';
            $description .= "\n\n[Rescheduled on " . now()->format('F d, Y g:i A') . "]";
            $event->setDescription($description);

            // Update event
            $updatedEvent = $service->events->update($calendarId, $eventId, $event);

            Log::info('Google Calendar event updated successfully', [
                'user_id' => $userDetail->user_id,
                'event_id' => $eventId,
                'new_date' => $newDate,
                'new_start_time' => $newStartTimeUtc,
            ]);

            return true;
        } catch (\Google_Service_Exception $e) {
            Log::error('Google Calendar API error during event update', [
                'user_id' => $userDetail->user_id ?? null,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to update Google Calendar event', [
                'user_id' => $userDetail->user_id ?? null,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Get currency symbol helper
     */
    private static function getCurrencySymbol($currency)
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'AED ',
            'SAR' => 'SAR ',
            'INR' => '₹',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];
        return $symbols[strtoupper($currency)] ?? strtoupper($currency) . ' ';
    }
}

