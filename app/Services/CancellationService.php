<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Refund;
use Stripe\Exception\ApiErrorException;

class CancellationService
{
    /**
     * Initialize Stripe API key
     */
    private function initializeStripe()
    {
        $stripeSecret = env('STRIPE_SECRET');
        if (!$stripeSecret) {
            throw new \Exception('Stripe secret key is not configured.');
        }
        Stripe::setApiKey($stripeSecret);
    }

    /**
     * Calculate refund amount based on cancellation rules
     */
    public function calculateRefund(Booking $booking, int $cancelledBy): array
    {
        $now = now();
        $bookingDateTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time_utc);
        $cancellationDeadline = $bookingDateTime->copy()->subHours($booking->cancellation_window_hours ?? 24);
        
        // Artist cancellation = full refund always
        if ($cancelledBy === $booking->artist_user_id) {
            return [
                'refund_amount' => $booking->total_amount_paid,
                'deposit_forfeited' => 0,
                'platform_fee_refunded' => true,
                'refund_reason' => 'Cancelled by artist',
                'refund_status' => 'pending',
                'is_before_deadline' => true, // Always true for artist cancellation
            ];
        }
        
        // Client cancellation before deadline = full refund
        if ($now->lt($cancellationDeadline)) {
            return [
                'refund_amount' => $booking->total_amount_paid,
                'deposit_forfeited' => 0,
                'platform_fee_refunded' => true,
                'refund_reason' => 'Cancelled before deadline',
                'refund_status' => 'pending',
                'is_before_deadline' => true,
            ];
        }
        
        // Client cancellation after deadline
        $depositAmount = $booking->deposit_amount ?? 0;
        
        // If only deposit paid, artist keeps it all
        if (!$booking->full_amount_paid) {
            return [
                'refund_amount' => 0,
                'deposit_forfeited' => $depositAmount,
                'platform_fee_refunded' => false,
                'refund_reason' => 'Cancelled after deadline - deposit forfeited',
                'refund_status' => 'completed', // No refund to process
                'is_before_deadline' => false,
            ];
        }
        
        // If full payment made, refund remaining balance
        $remainingBalance = $booking->total_amount_paid - $depositAmount;
        $platformFeeRefund = 0;
        if ($booking->platform_fee > 0 && $booking->total_amount_paid > 0) {
            $platformFeeRefund = ($booking->platform_fee / $booking->total_amount_paid) * $remainingBalance;
        }
        
        return [
            'refund_amount' => $remainingBalance,
            'deposit_forfeited' => $depositAmount,
            'platform_fee_refunded' => true,
            'platform_fee_refund_amount' => $platformFeeRefund,
            'refund_reason' => 'Cancelled after deadline - partial refund',
            'refund_status' => 'pending',
            'is_before_deadline' => false,
        ];
    }

    /**
     * Handle no-show logic
     */
    public function handleNoShow(Booking $booking): array
    {
        $depositAmount = $booking->deposit_amount ?? 0;
        
        if (!$booking->full_amount_paid) {
            return [
                'refund_amount' => 0,
                'deposit_forfeited' => $depositAmount,
                'platform_fee_refunded' => false,
                'refund_reason' => 'No-show - deposit forfeited',
                'refund_status' => 'completed',
            ];
        }
        
        // If full payment made, refund remaining balance
        $remainingBalance = $booking->total_amount_paid - $depositAmount;
        
        return [
            'refund_amount' => $remainingBalance,
            'deposit_forfeited' => $depositAmount,
            'platform_fee_refunded' => true,
            'refund_reason' => 'No-show - partial refund',
            'refund_status' => 'pending',
        ];
    }

    /**
     * Process Stripe refund
     */
    public function processStripeRefund(Booking $booking, float $refundAmount, string $reason): ?\Stripe\Refund
    {
        if ($refundAmount <= 0) {
            return null;
        }

        try {
            $this->initializeStripe();
            
            // Get payment intent
            $paymentIntentId = $booking->payment_intent_id;
            
            if (!$paymentIntentId) {
                Log::warning('Cannot process refund: No payment intent ID', [
                    'booking_id' => $booking->id,
                ]);
                return null;
            }
            
            // Create refund via Stripe
            $refund = Refund::create([
                'payment_intent' => $paymentIntentId,
                'amount' => (int)($refundAmount * 100), // Convert to cents
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'booking_id' => $booking->id,
                    'refund_reason' => $reason,
                ]
            ]);
            
            // Update booking
            $booking->update([
                'refund_intent_id' => $refund->id,
                'refunded_at' => now(),
                'refund_status' => $refund->status === 'succeeded' ? 'completed' : 'processing',
            ]);
            
            Log::info('Stripe refund processed successfully', [
                'booking_id' => $booking->id,
                'refund_id' => $refund->id,
                'amount' => $refundAmount,
            ]);
            
            return $refund;
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            
            $booking->update([
                'refund_status' => 'failed',
            ]);
            
            throw $e;
        } catch (\Exception $e) {
            Log::error('Refund processing error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            
            $booking->update([
                'refund_status' => 'failed',
            ]);
            
            throw $e;
        }
    }

    /**
     * Get cancellation info for a booking
     */
    public function getCancellationInfo(Booking $booking): array
    {
        $bookingDateTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time_utc);
        $cancellationDeadline = $bookingDateTime->copy()->subHours($booking->cancellation_window_hours ?? 24);
        $now = now();
        $isBeforeDeadline = $now->lt($cancellationDeadline);
        
        // Calculate estimated refund
        $estimatedRefund = $this->calculateRefund($booking, auth()->id());
        
        // Determine refund eligibility
        $refundEligibility = 'no_refund';
        if ($estimatedRefund['refund_amount'] > 0) {
            if ($estimatedRefund['refund_amount'] == $booking->total_amount_paid) {
                $refundEligibility = 'full_refund';
            } else {
                $refundEligibility = 'partial_refund';
            }
        }
        
        return [
            'booking_id' => $booking->id,
            'booking_date' => $bookingDateTime->toDateTimeString(),
            'cancellation_window_hours' => $booking->cancellation_window_hours ?? 24,
            'cancellation_deadline' => $cancellationDeadline->toDateTimeString(),
            'can_cancel' => $booking->status === 'confirmed',
            'is_before_deadline' => $isBeforeDeadline,
            'estimated_refund' => [
                'amount' => $estimatedRefund['refund_amount'],
                'deposit_forfeited' => $estimatedRefund['deposit_forfeited'],
                'platform_fee_refunded' => $estimatedRefund['platform_fee_refunded'],
            ],
            'refund_eligibility' => $refundEligibility,
        ];
    }
}

