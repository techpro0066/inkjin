<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Google_Client;
use Google_Service_Calendar;
use Carbon\Carbon;

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
            return redirect()->route('onboarding.index')
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
                return redirect()->route('onboarding.index')
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
                return redirect()->route('onboarding.index')
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

            // Store tokens and calendar ID
            $userDetail->update([
                'google_calendar_token' => $accessToken,
                'google_calendar_id' => $primaryCalendarId,
            ]);

            return redirect()->route('onboarding.index')
                ->with('success', 'Google Calendar connected successfully!');
        } catch (\Exception $e) {
            Log::error('Google Calendar callback error: ' . $e->getMessage());
            return redirect()->route('onboarding.index')
                ->with('error', 'Failed to connect Google Calendar. Please try again.');
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
     */
    public function refreshToken($userDetail)
    {
        try {
            if (!$userDetail->google_calendar_token) {
                return null;
            }

            $token = is_array($userDetail->google_calendar_token) 
                ? $userDetail->google_calendar_token 
                : json_decode($userDetail->google_calendar_token, true);

            $client = new Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->refreshToken($token['refresh_token'] ?? null);

            $newToken = $client->getAccessToken();
            
            if ($newToken) {
                $userDetail->update([
                    'google_calendar_token' => $newToken,
                ]);
                return $newToken;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Google Calendar token refresh error: ' . $e->getMessage());
            return null;
        }
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
                return [];
            }

            $token = is_array($userDetail->google_calendar_token) 
                ? $userDetail->google_calendar_token 
                : json_decode($userDetail->google_calendar_token, true);

            if (!$token) {
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
                } else {
                    Log::warning('Failed to refresh Google Calendar token for user: ' . $userDetail->user_id);
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
     * Create a calendar event for a booking
     * 
     * @param \App\Models\UserDetail $userDetail Artist's user detail
     * @param \App\Models\Booking $booking Booking instance
     * @return string|null Google Calendar event ID or null on failure
     */
    public static function createCalendarEvent($userDetail, $booking)
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
                } else {
                    Log::warning('Failed to refresh Google Calendar token for event creation', [
                        'user_id' => $userDetail->user_id,
                        'booking_id' => $booking->id,
                    ]);
                    return null;
                }
            }

            $service = new Google_Service_Calendar($client);
            $calendarId = $userDetail->google_calendar_id ?? 'primary';

            // Get booking details
            $tattoo = $booking->tattoo;
            $customer = $booking->user;
            $artist = $booking->artist;

            // Format event title
            $eventTitle = 'Tattoo Session: ' . ($tattoo->title ?? 'Custom Tattoo');
            
            // Format event description
            $description = "Customer: {$customer->name} ({$customer->email})\n";
            $description .= "Tattoo: " . ($tattoo->title ?? 'Custom Tattoo') . "\n";
            $description .= "Booking ID: #{$booking->id}\n\n";
            
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

            // Insert event
            $createdEvent = $service->events->insert($calendarId, $event);
            $eventId = $createdEvent->getId();

            Log::info('Google Calendar event created successfully', [
                'booking_id' => $booking->id,
                'event_id' => $eventId,
                'artist_user_id' => $artist->id,
            ]);

            return $eventId;
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

