<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;

class GoogleCalendarController extends Controller
{
    /**
     * Redirect user to Google OAuth consent screen
     */
    public function redirect(Request $request)
    {
        try {
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
            return redirect()->route('onboarding.calendar')
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
                return redirect()->route('onboarding.calendar')
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
                return redirect()->route('onboarding.calendar')
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
            $userDetail->update([
                'google_calendar_token' => $accessToken,
                'google_calendar_id' => $primaryCalendarId,
            ]);

            return redirect()->route('onboarding.calendar')
                ->with('success', 'Google Calendar connected successfully!');
        } catch (\Exception $e) {
            Log::error('Google Calendar callback error: ' . $e->getMessage());
            return redirect()->route('onboarding.calendar')
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
     * @param UserDetail $userDetail
     * @param string $date Date in Y-m-d format
     * @param string $timezone User's timezone
     * @return array Array of events with start_time and end_time in UTC
     */
    public static function getEventsForDate($userDetail, $date, $timezone = 'UTC')
    {
        try {
            if (!$userDetail || !$userDetail->google_calendar_token || !$userDetail->google_calendar_id) {
                Log::info('Google Calendar not connected for event fetching', [
                    'user_id' => $userDetail->user_id ?? null,
                    'date' => $date
                ]);
                return [];
            }

            $token = is_array($userDetail->google_calendar_token) 
                ? $userDetail->google_calendar_token 
                : json_decode($userDetail->google_calendar_token, true);

            if (!$token) {
                Log::warning('Invalid token format for event fetching', [
                    'user_id' => $userDetail->user_id ?? null,
                    'date' => $date
                ]);
                return [];
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
                    Log::info('Token refreshed successfully for event fetching', [
                        'user_id' => $userDetail->user_id,
                        'date' => $date
                    ]);
                } else {
                    // Check if refresh token is missing
                    if (!isset($token['refresh_token']) || empty($token['refresh_token'])) {
                        Log::error('Refresh token missing - user needs to reconnect Google Calendar for event fetching', [
                            'user_id' => $userDetail->user_id,
                            'date' => $date
                        ]);
                    } else {
                        Log::error('Token refresh failed - refresh token may be invalid or revoked', [
                            'user_id' => $userDetail->user_id,
                            'date' => $date
                        ]);
                    }
                    return [];
                }
            }

            $service = new Google_Service_Calendar($client);
            $calendarId = $userDetail->google_calendar_id ?? 'primary';

            // Create date range for the entire day in user's timezone
            $startOfDay = \Carbon\Carbon::createFromFormat('Y-m-d', $date, $timezone)->startOfDay();
            $endOfDay = $startOfDay->copy()->endOfDay();

            // Convert to UTC for Google Calendar API
            $timeMin = $startOfDay->setTimezone('UTC')->toRfc3339String();
            $timeMax = $endOfDay->setTimezone('UTC')->toRfc3339String();

            // Fetch events
            $optParams = [
                'timeMin' => $timeMin,
                'timeMax' => $timeMax,
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ];

            $events = $service->events->listEvents($calendarId, $optParams);
            $eventList = [];

            foreach ($events->getItems() as $event) {
                $start = $event->getStart();
                $end = $event->getEnd();

                if ($start && $end) {
                    $startTime = $start->getDateTime() ?: $start->getDate();
                    $endTime = $end->getDateTime() ?: $end->getDate();

                    if ($startTime && $endTime) {
                        // Parse and convert to UTC Carbon instances
                        $startUTC = \Carbon\Carbon::parse($startTime)->setTimezone('UTC');
                        $endUTC = \Carbon\Carbon::parse($endTime)->setTimezone('UTC');

                        $eventList[] = [
                            'start_time_utc' => $startUTC->format('H:i:s'),
                            'end_time_utc' => $endUTC->format('H:i:s'),
                            'start_datetime_utc' => $startUTC,
                            'end_datetime_utc' => $endUTC,
                            'summary' => $event->getSummary() ?? 'Busy',
                        ];
                    }
                }
            }

            return $eventList;
        } catch (\Exception $e) {
            Log::error('Google Calendar fetch events error: ' . $e->getMessage(), [
                'user_id' => $userDetail->user_id ?? null,
                'date' => $date,
            ]);
            return [];
        }
    }

    /**
     * Create a calendar event for a booking with Google Meet link
     * 
     * @param \App\Models\UserDetail $userDetail Artist's user detail
     * @param \App\Models\Booking $booking Booking instance
     * @param bool $requiresConsultation Whether to create Google Meet link
     * @return array|null Array with 'event_id' and 'meet_link' keys, or null on failure
     */
    public static function createCalendarEvent($userDetail, $booking, $requiresConsultation = false)
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
            $tattoo = $booking->tattoo;
            $customer = $booking->user;
            $artist = $booking->artist;

            // Format event title - include consultation info for combined mode
            $isCombinedConsultation = $booking->consultation_timing_type === 'combined' && $booking->has_consultation;
            if ($isCombinedConsultation) {
                $eventTitle = 'Tattoo Session + Consultation: ' . ($tattoo->title ?? 'Custom Tattoo');
            } else {
            $eventTitle = 'Tattoo Session: ' . ($tattoo->title ?? 'Custom Tattoo');
            }
            
            // Format event description
            $description = "Customer: {$customer->name} ({$customer->email})\n";
            $description .= "Tattoo: " . ($tattoo->title ?? 'Custom Tattoo') . "\n";
            $description .= "Booking ID: #{$booking->id}\n";
            
            // Add consultation timing info for combined mode
            if ($isCombinedConsultation) {
                $tattooDurationHours = $tattoo->session_time_h ?? 0;
                $consultationDurationMinutes = 0;
                if ($booking->consultation_start_time_utc && $booking->consultation_end_time_utc) {
                    $consultationStart = \Carbon\Carbon::createFromFormat('H:i:s', $booking->consultation_start_time_utc);
                    $consultationEnd = \Carbon\Carbon::createFromFormat('H:i:s', $booking->consultation_end_time_utc);
                    $consultationDurationMinutes = $consultationStart->diffInMinutes($consultationEnd);
                }
                $description .= "\nConsultation Timing: Combined\n";
                $description .= "Consultation Duration: {$consultationDurationMinutes} minutes\n";
                $description .= "Tattoo Session Duration: {$tattooDurationHours} hour(s)\n";
                $description .= "Total Duration: " . ($tattooDurationHours + ($consultationDurationMinutes / 60)) . " hour(s)\n";
            }
            
            $description .= "\n";
            
            // Add payment info
            $currencySymbol = self::getCurrencySymbol($booking->currency);
            $description .= "Payment: {$currencySymbol}" . number_format($booking->total_amount_paid, 2) . "\n";
            $description .= $booking->full_amount_paid ? "Full amount paid" : "Deposit: {$currencySymbol}" . number_format($booking->deposit_amount, 2);
            
            // Add questions/answers if available
            if (!empty($booking->questions_answers)) {
                $description .= "\n\nCustomer Responses:\n";
                // Get question texts
                $questionModels = \App\Models\UserQuestion::whereIn('id', array_keys($booking->questions_answers))
                    ->where('user_id', $artist->id)
                    ->get()
                    ->keyBy('id');
                
                foreach ($booking->questions_answers as $questionId => $answer) {
                    $question = $questionModels->get($questionId);
                    if ($question) {
                        if ($question->type === 'image' && is_array($answer)) {
                            $description .= "\n{$question->question}: " . count($answer) . " image(s) uploaded\n";
                        } else {
                            $answerText = is_array($answer) ? implode(', ', $answer) : $answer;
                            $description .= "\n{$question->question}: {$answerText}\n";
                        }
                    }
                }
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

            // Enable Google Meet ONLY if consultation is required
            if ($requiresConsultation) {
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
            if ($requiresConsultation) {
                $insertParams['conferenceDataVersion'] = 1; // Required to enable Google Meet
            }
            $createdEvent = $service->events->insert($calendarId, $event, $insertParams);
            $eventId = $createdEvent->getId();

            // Extract Google Meet link from the created event (only if consultation required)
            $meetLink = null;
            if ($requiresConsultation && $createdEvent->getConferenceData() && $createdEvent->getConferenceData()->getEntryPoints()) {
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
                'meet_link' => $meetLink,
            ]);

            // Return both event ID and Meet link (Meet link will be null if consultation not required)
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

