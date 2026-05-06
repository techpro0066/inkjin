<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\CancellationService;
use App\Http\Controllers\GoogleCalendarController;
use App\Mail\BookingCancellationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class BookingCancellationController extends Controller
{
    protected $cancellationService;

    public function __construct(CancellationService $cancellationService)
    {
        $this->cancellationService = $cancellationService;
    }

    /**
     * Get cancellation info for a booking
     */
    public function getCancellationInfo($id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $user = Auth::user();

            // Check authorization
            if ($booking->user_id !== $user->id && $booking->artist_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to booking',
                ], 403);
            }

            $info = $this->cancellationService->getCancellationInfo($booking);

            return response()->json([
                'success' => true,
                'data' => $info,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get cancellation info', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get cancellation information',
            ], 500);
        }
    }

    /**
     * Cancel a booking
     */
    public function cancel(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $user = Auth::user();

            // Check authorization
            if ($booking->user_id !== $user->id && $booking->artist_user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to booking',
                ], 403);
            }

            // Check if booking can be cancelled
            $isArtistRequestedPending = (
                $booking->status === 'pending'
                && $booking->reschedule_status === 'pending'
                && $booking->reschedule_requested_by === 'artist'
            );
            if ($booking->status !== 'confirmed' && ! $isArtistRequestedPending) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking cannot be cancelled. Current status: ' . $booking->status,
                ], 400);
            }

            $isClient = (int) $booking->user_id === (int) $user->id;
            $validator = Validator::make($request->all(), [
                'reason' => $isClient
                    ? 'required|string|min:3|max:1000'
                    : 'nullable|string|max:1000',
                'confirmed' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (!$request->boolean('confirmed')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation must be confirmed',
                ], 400);
            }

            // Determine cancellation type
            $cancellationType = $isClient ? 'client' : 'artist';
            $cancelledBy = $user->id;

            // Calculate refund
            $refundData = $this->cancellationService->calculateRefund($booking, $cancelledBy);

            // Update booking
            $actionHistory = $booking->action_history ?? [];
            $actionHistory[] = [
                'action' => 'cancelled',
                'user_id' => $cancelledBy,
                'user_type' => $cancellationType,
                'timestamp' => now()->toDateTimeString(),
                'reason' => $request->input('reason'),
                'refund_amount' => $refundData['refund_amount'],
                'deposit_forfeited' => $refundData['deposit_forfeited'],
            ];

            $booking->update([
                'status' => 'cancelled',
                'cancelled_by' => $cancelledBy,
                'cancelled_at' => now(),
                'cancellation_initiated_at' => now(),
                'cancellation_reason' => $request->input('reason'),
                'cancellation_type' => $cancellationType,
                'refund_amount' => $refundData['refund_amount'],
                'deposit_forfeited' => $refundData['deposit_forfeited'],
                'refund_reason' => $refundData['refund_reason'],
                'refund_status' => $refundData['refund_status'],
                'platform_fee_refunded' => $refundData['platform_fee_refunded'],
                'action_history' => $actionHistory,
            ]);

            // Process refund if needed
            if ($refundData['refund_amount'] > 0) {
                try {
                    $this->cancellationService->processStripeRefund(
                        $booking,
                        $refundData['refund_amount'],
                        $refundData['refund_reason']
                    );
                } catch (\Exception $e) {
                    Log::error('Refund processing failed (non-critical)', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with cancellation even if refund fails
                }
            }

            $booking->refresh();

            // Cancel Google Calendar event
            if ($booking->google_calendar_event_id) {
                try {
                    $artistUserDetail = $booking->artist->userDetail;
                    if ($artistUserDetail && $artistUserDetail->google_calendar_token) {
                        GoogleCalendarController::deleteCalendarEvent(
                            $artistUserDetail,
                            $booking->google_calendar_event_id
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to delete Google Calendar event (non-critical)', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Send email notifications
            try {
                // Email to client
                if ($cancellationType === 'artist') {
                    Mail::to($booking->user->email)->send(
                        new BookingCancellationMail($booking, false, $cancellationType)
                    );
                } else {
                    Mail::to($booking->user->email)->send(
                        new BookingCancellationMail($booking, false, $cancellationType)
                    );
                }

                // Email to artist
                Mail::to($booking->artist->email)->send(
                    new BookingCancellationMail($booking, true, $cancellationType)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send cancellation emails', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Booking cancelled successfully', [
                'booking_id' => $booking->id,
                'cancelled_by' => $cancelledBy,
                'cancellation_type' => $cancellationType,
                'refund_amount' => $refundData['refund_amount'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'booking' => [
                    'id' => $booking->id,
                    'status' => $booking->status,
                    'refund_amount' => $booking->refund_amount,
                    'refund_status' => $booking->refund_status,
                    'deposit_forfeited' => $booking->deposit_forfeited,
                    'cancelled_at' => $booking->cancelled_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel booking', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark booking as no-show
     */
    public function markNoShow(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'confirmed' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            if (!$request->confirmed) {
                return response()->json([
                    'success' => false,
                    'message' => 'No-show must be confirmed',
                ], 400);
            }

            $booking = Booking::findOrFail($id);
            $user = Auth::user();

            // Only artist or admin can mark no-show
            if ($booking->artist_user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only artist can mark no-show',
                ], 403);
            }

            // Check if booking can be marked as no-show
            if ($booking->status !== 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking cannot be marked as no-show. Current status: ' . $booking->status,
                ], 400);
            }

            // Calculate no-show refund
            $refundData = $this->cancellationService->handleNoShow($booking);

            // Update booking
            $actionHistory = $booking->action_history ?? [];
            $actionHistory[] = [
                'action' => 'marked_no_show',
                'user_id' => $user->id,
                'user_type' => 'artist',
                'timestamp' => now()->toDateTimeString(),
                'refund_amount' => $refundData['refund_amount'],
                'deposit_forfeited' => $refundData['deposit_forfeited'],
            ];

            $booking->update([
                'status' => 'no_show',
                'cancelled_by' => $booking->user_id, // Client is responsible
                'cancelled_at' => now(),
                'cancellation_initiated_at' => now(),
                'cancellation_type' => 'client',
                'no_show_marked_at' => now(),
                'refund_amount' => $refundData['refund_amount'],
                'deposit_forfeited' => $refundData['deposit_forfeited'],
                'refund_reason' => $refundData['refund_reason'],
                'refund_status' => $refundData['refund_status'],
                'platform_fee_refunded' => $refundData['platform_fee_refunded'],
                'action_history' => $actionHistory,
            ]);

            // Process refund if needed
            if ($refundData['refund_amount'] > 0) {
                try {
                    $this->cancellationService->processStripeRefund(
                        $booking,
                        $refundData['refund_amount'],
                        $refundData['refund_reason']
                    );
                } catch (\Exception $e) {
                    Log::error('Refund processing failed for no-show (non-critical)', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Send email notifications
            try {
                Mail::to($booking->user->email)->send(
                    new BookingCancellationMail($booking, false, 'client', true)
                );
                Mail::to($booking->artist->email)->send(
                    new BookingCancellationMail($booking, true, 'client', true)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send no-show emails', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Booking marked as no-show', [
                'booking_id' => $booking->id,
                'marked_by' => $user->id,
                'deposit_forfeited' => $refundData['deposit_forfeited'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking marked as no-show',
                'booking' => [
                    'id' => $booking->id,
                    'status' => $booking->status,
                    'deposit_forfeited' => $booking->deposit_forfeited,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark booking as no-show', [
                'booking_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark booking as no-show',
            ], 500);
        }
    }
}
