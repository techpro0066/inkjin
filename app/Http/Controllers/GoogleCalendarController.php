<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
}

