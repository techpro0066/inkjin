<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Availability;
use App\Models\AvailabilityOverride;
use App\Models\User;
use App\Services\ReschedulingService;
use App\Http\Controllers\GoogleCalendarController;
use App\Mail\RescheduleRequestMail;
use App\Mail\RescheduleConfirmationMail;
use App\Mail\RescheduleDeclinedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReschedulingController extends Controller
{
    protected $reschedulingService;
    
    public function __construct(ReschedulingService $reschedulingService)
    {
        $this->reschedulingService = $reschedulingService;
    }
    
    /**
     * Check if booking can be rescheduled
     */
    public function checkCanReschedule($id)
    {
        try {
            $booking = Booking::with(['artist.userDetail'])->findOrFail($id);
            $user = Auth::user();
            
            // Check authorization
            if ($booking->user_id !== $user->id && $booking->artist_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to booking',
                ], 403);
            }
            
            // Only clients can check their own reschedule eligibility
            if ($booking->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only clients can check reschedule eligibility',
                ], 403);
            }
            
            // Check booking status
            if ($booking->status !== 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only confirmed bookings can be rescheduled',
                    'data' => [
                        'can_reschedule' => false,
                        'reason' => 'invalid_status',
                    ],
                ]);
            }
            
            $eligibility = $this->reschedulingService->canReschedule($booking, $user->id);
            
            return response()->json([
                'success' => true,
                'data' => $eligibility,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check reschedule eligibility', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check reschedule eligibility',
            ], 500);
        }
    }
    
    /**
     * Artist requests reschedule
     */
    public function artistRequestReschedule(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:1000',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $booking = Booking::with(['user', 'artist'])->findOrFail($id);
            $user = Auth::user();
            
            // Check authorization (artist only)
            if ($booking->artist_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the artist can request reschedule',
                ], 403);
            }
            
            // Check booking status
            if ($booking->status !== 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only confirmed bookings can be rescheduled',
                ], 400);
            }
            
            // Process artist reschedule request
            $result = $this->reschedulingService->processArtistRescheduleRequest(
                $booking,
                $request->reason
            );
            
            // Send email to client
            try {
                Mail::to($booking->user->email)->send(
                    new RescheduleRequestMail($booking, $request->reason)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send reschedule request email', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            Log::info('Artist reschedule request created', [
                'booking_id' => $booking->id,
                'artist_id' => $user->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Reschedule request sent to client',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create artist reschedule request', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create reschedule request: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Client reschedules booking (selects new date/time)
     */
    public function reschedule(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'new_date' => 'required|date',
                'new_start_time_utc' => 'required|date_format:H:i:s',
                'new_end_time_utc' => 'required|date_format:H:i:s',
                'reason' => 'nullable|string|max:1000',
                // Optional fields for separate consultation rescheduling
                'consultation_date' => 'nullable|date',
                'consultation_start_time_utc' => 'nullable|date_format:H:i:s',
                'consultation_end_time_utc' => 'nullable|date_format:H:i:s',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $booking = Booking::with(['user', 'artist.userDetail'])->findOrFail($id);
            $user = Auth::user();
            
            // Check authorization
            if ($booking->user_id !== $user->id && $booking->artist_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to booking',
                ], 403);
            }
            
            // Check booking status
            if ($booking->status !== 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only confirmed bookings can be rescheduled',
                ], 400);
            }
            
            // Check if this is artist-requested reschedule
            $isArtistRequested = $booking->reschedule_status === 'pending' 
                && $booking->reschedule_requested_by === 'artist';
            
            if ($isArtistRequested) {
                // Process artist-requested reschedule (doesn't count against limit)
                $result = $this->reschedulingService->processArtistRescheduleResponse(
                    $booking,
                    $request->new_date,
                    $request->new_start_time_utc,
                    $request->new_end_time_utc
                );
            } else {
                // Process client-initiated reschedule (counts against limit)
                $result = $this->reschedulingService->processClientReschedule(
                    $booking,
                    $request->new_date,
                    $request->new_start_time_utc,
                    $request->new_end_time_utc,
                    $request->reason
                );
            }
            
            // Refresh booking to get updated values
            $booking->refresh();
            
            // If separate consultation, also reschedule the consultation booking
            $consultationBooking = null;
            if ($request->has('consultation_date') && 
                $request->has('consultation_start_time_utc') && 
                $request->has('consultation_end_time_utc') &&
                $booking->consultation_timing_type === 'separate' &&
                $booking->consultation_booking_id) {
                
                $consultationBooking = Booking::find($booking->consultation_booking_id);
                if ($consultationBooking) {
                    // Check if consultation booking can be rescheduled
                    $consultationEligibility = $this->reschedulingService->canReschedule($consultationBooking, $user->id);
                    if (!$consultationEligibility['can_reschedule'] && !$isArtistRequested) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Consultation booking cannot be rescheduled: ' . ($consultationEligibility['message'] ?? 'Rescheduling limit reached or deadline passed'),
                        ], 400);
                    }
                    
                    // Reschedule consultation booking
                    if ($isArtistRequested) {
                        $this->reschedulingService->processArtistRescheduleResponse(
                            $consultationBooking,
                            $request->consultation_date,
                            $request->consultation_start_time_utc,
                            $request->consultation_end_time_utc
                        );
                    } else {
                        // For client-initiated, don't increment reschedule_count since it's part of tattoo session reschedule
                        // We'll manually update the consultation booking without incrementing count
                        $consultationBooking->booking_date = $request->consultation_date;
                        $consultationBooking->start_time_utc = $request->consultation_start_time_utc;
                        $consultationBooking->end_time_utc = $request->consultation_end_time_utc;
                        $consultationBooking->rescheduled_at = now();
                        $consultationBooking->rescheduled_by = $user->id; // Set to user ID, not string 'client'
                        $consultationBooking->reschedule_status = 'completed';
                        $consultationBooking->reschedule_requested_by = 'client';
                        $consultationBooking->reschedule_reason = $request->reason;
                        $consultationBooking->save();
                    }
                    $consultationBooking->refresh();
                }
            }
            
            // Update Google Calendar event for tattoo session
            if ($booking->google_calendar_event_id) {
                try {
                    $artistUserDetail = $booking->artist->userDetail;
                    if ($artistUserDetail && $artistUserDetail->google_calendar_token) {
                        GoogleCalendarController::updateCalendarEvent(
                            $artistUserDetail,
                            $booking->google_calendar_event_id,
                            $request->new_date,
                            $request->new_start_time_utc,
                            $request->new_end_time_utc
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to update Google Calendar event (non-critical)', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Update Google Calendar event for consultation if rescheduled
            if ($consultationBooking && $consultationBooking->google_calendar_event_id) {
                try {
                    $artistUserDetail = $booking->artist->userDetail;
                    if ($artistUserDetail && $artistUserDetail->google_calendar_token) {
                        GoogleCalendarController::updateCalendarEvent(
                            $artistUserDetail,
                            $consultationBooking->google_calendar_event_id,
                            $request->consultation_date,
                            $request->consultation_start_time_utc,
                            $request->consultation_end_time_utc
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to update consultation Google Calendar event (non-critical)', [
                        'booking_id' => $consultationBooking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Send confirmation emails
            try {
                Mail::to($booking->user->email)->send(
                    new RescheduleConfirmationMail($booking, false)
                );
                Mail::to($booking->artist->email)->send(
                    new RescheduleConfirmationMail($booking, true)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send reschedule confirmation emails', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            Log::info('Booking rescheduled successfully', [
                'booking_id' => $booking->id,
                'old_date' => $result['old_date'],
                'new_date' => $result['new_date'],
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Booking rescheduled successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reschedule booking', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reschedule booking: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Client declines artist's reschedule request
     */
    public function declineReschedule(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:1000',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            $booking = Booking::with(['user', 'artist'])->findOrFail($id);
            $user = Auth::user();
            
            // Check authorization (client only)
            if ($booking->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the client can decline reschedule request',
                ], 403);
            }
            
            // Check if there's a pending artist reschedule request
            if ($booking->reschedule_status !== 'pending' || $booking->reschedule_requested_by !== 'artist') {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending artist reschedule request found',
                ], 400);
            }
            
            // Process decline
            $result = $this->reschedulingService->processDeclineReschedule(
                $booking,
                $request->reason
            );
            
            // Send notification to artist
            try {
                Mail::to($booking->artist->email)->send(
                    new RescheduleDeclinedMail($booking, $request->reason)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send reschedule declined email', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            Log::info('Reschedule request declined', [
                'booking_id' => $booking->id,
                'client_id' => $user->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Reschedule request declined. Booking will be cancelled.',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to decline reschedule request', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to decline reschedule request',
            ], 500);
        }
    }
    
    /**
     * Show reschedule page
     */
    public function showReschedulePage($id)
    {
        $booking = Booking::with(['user', 'artist.userDetail', 'tattoo'])->findOrFail($id);
        $user = Auth::user();
        
        // Check authorization
        if ($booking->user_id !== $user->id && $booking->artist_user_id !== $user->id) {
            abort(403, 'Unauthorized access to booking');
        }
        
        // Get eligibility (for client-initiated)
        $eligibility = null;
        if ($booking->user_id === $user->id) {
            try {
                $eligibility = $this->reschedulingService->canReschedule($booking, $user->id);
            } catch (\Exception $e) {
                Log::error('Failed to get reschedule eligibility', [
                    'booking_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Check if artist-requested reschedule
        $isArtistRequested = $booking->reschedule_status === 'pending' 
            && $booking->reschedule_requested_by === 'artist';
        
        return view('bookings.reschedule', [
            'booking' => $booking,
            'eligibility' => $eligibility,
            'isArtistRequested' => $isArtistRequested,
        ]);
    }
    
    /**
     * Show reschedule flow page (similar to booking flow but without payment)
     * URL: /bookings/{id}/reschedule-flow
     */
    public function showRescheduleFlow($id)
    {
        $booking = Booking::with(['user', 'artist.userDetail', 'tattoo.artist'])->findOrFail($id);
        $user = Auth::user();
        
        // Check authorization
        if ($booking->user_id !== $user->id && $booking->artist_user_id !== $user->id) {
            abort(403, 'Unauthorized access to booking');
        }
        
        // Check if this is artist-requested reschedule
        $isArtistRequested = $booking->reschedule_status === 'pending' 
            && $booking->reschedule_requested_by === 'artist';
        
        // For client-initiated reschedule, check eligibility
        if ($booking->user_id === $user->id && !$isArtistRequested) {
            $eligibility = $this->reschedulingService->canReschedule($booking, $user->id);
            
            // If not eligible, show 404
            if (!$eligibility['can_reschedule']) {
                abort(404, 'Rescheduling is not available for this booking.');
            }
        }
        
        // Get artist user
        $artistUser = $booking->artist;
        $artistUserDetail = $artistUser->userDetail;
        
        // Get availability data (similar to bookTattoo method)
        $availabilityData = [
            'availabilities' => collect(),
            'overrides' => collect(),
            'availableDates' => [],
            'unavailableDates' => [],
            'userTimezone' => 'UTC',
        ];
        
        if ($artistUserDetail) {
            $timezone = $artistUserDetail->timezone ?? 'UTC';
            $availabilityData['userTimezone'] = $timezone;
            
            // Get all availabilities
            $availabilities = Availability::where('user_id', $artistUser->id)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
            
            // Get all availability overrides
            $overrides = AvailabilityOverride::where('user_id', $artistUser->id)
                ->where('override_date', '>=', Carbon::today())
                ->orderBy('override_date')
                ->get();
            
            $availabilityData['availabilities'] = $availabilities;
            $availabilityData['overrides'] = $overrides->map(function ($override) {
                return [
                    'override_date' => $override->override_date->format('Y-m-d'),
                    'is_unavailable' => $override->is_unavailable,
                    'start_time' => $override->start_time,
                    'end_time' => $override->end_time,
                ];
            });
            
            // Calculate available/unavailable dates
            $availableDates = [];
            $unavailableDates = [];
            $startDate = Carbon::today();
            $endDate = Carbon::today()->addYears(2);
            
            // Group weekly availabilities by day of week
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
            
            $dayNameMap = [
                0 => 'sunday',
                1 => 'monday',
                2 => 'tuesday',
                3 => 'wednesday',
                4 => 'thursday',
                5 => 'friday',
                6 => 'saturday',
            ];
            
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $dateKey = $currentDate->format('Y-m-d');
                
                $dateInArtistTimezone = Carbon::create(
                    $currentDate->year,
                    $currentDate->month,
                    $currentDate->day,
                    0, 0, 0, $timezone
                );
                $carbonDayOfWeek = $dateInArtistTimezone->dayOfWeek;
                $dayName = $dayNameMap[$carbonDayOfWeek];
                
                $override = $overrides->firstWhere('override_date', $dateKey);
                
                if ($override) {
                    if ($override->is_unavailable) {
                        $unavailableDates[] = $dateKey;
                    } else {
                        $availableDates[] = $dateKey;
                    }
                } else {
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
        
        if ($artistUserDetail) {
            $consultationInfo['requires_consultation'] = $artistUserDetail->require_consultation ?? false;
            $consultationInfo['consultation_timing'] = $artistUserDetail->consultation_timing ?? null;
            $consultationInfo['is_separate'] = ($artistUserDetail->require_consultation ?? false) && ($artistUserDetail->consultation_timing === 'separate');
            $consultationInfo['is_combined'] = ($artistUserDetail->require_consultation ?? false) && ($artistUserDetail->consultation_timing === 'combined');
            $consultationInfo['session_duration_minutes'] = $artistUserDetail->session_duration_minutes ?? null;
            $consultationInfo['gap_required'] = $artistUserDetail->require_gap_between_consultation_tattoo ?? false;
            $consultationInfo['gap_value'] = $artistUserDetail->consultation_tattoo_gap_value ?? null;
            $consultationInfo['gap_unit'] = $artistUserDetail->consultation_tattoo_gap_unit ?? null;
        }
        
        // Convert tattoo to array format
        $tattoo = $booking->tattoo;
        $tattooData = [
            'tattoo_id' => $tattoo->id,
            'title' => $tattoo->title,
            'field_tattoo_image_preview' => $tattoo->filename,
            'session_time_h' => $tattoo->session_time_h ?? 2,
        ];
        
        // Convert artist to array format
        $artist = $tattoo->artist;
        $artistData = [
            'uid' => $artist->id,
            'username' => $artist->artist_handle,
            'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
        ];
        
        // Get consultation booking if this is a tattoo_session with separate consultation
        $consultationBooking = null;
        if ($booking->booking_type === 'tattoo_session' && $booking->consultation_timing_type === 'separate' && $booking->consultation_booking_id) {
            $consultationBooking = Booking::find($booking->consultation_booking_id);
        }
        
        return view('bookings.reschedule-flow', [
            'booking' => $booking,
            'tattoo' => $tattooData,
            'artist' => $artistData,
            'availabilityData' => $availabilityData,
            'consultationInfo' => $consultationInfo,
            'isArtistRequested' => $isArtistRequested,
            'consultationBooking' => $consultationBooking,
        ]);
    }
}
