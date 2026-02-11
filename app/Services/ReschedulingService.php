<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReschedulingService
{
    /**
     * Check if client can reschedule a booking
     */
    public function canReschedule(Booking $booking, int $userId): array
    {
        // Get artist's preferences
        $artistDetail = $booking->artist->userDetail;
        $reschedulePolicy = $artistDetail->reschedule_times ?? 'never';
        $cancellationWindow = $this->parseCancellationWindow($artistDetail->cancellation_window ?? '24');
        
        // Check if policy allows rescheduling
        if ($reschedulePolicy === 'never') {
            return [
                'can_reschedule' => false,
                'reason' => 'policy_never',
                'message' => 'Rescheduling is not allowed for this artist.',
            ];
        }
        
        // Calculate cancellation deadline
        $bookingDateTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time_utc);
        $cancellationDeadline = $bookingDateTime->copy()->subHours($cancellationWindow);
        $now = now();
        
        // Check if deadline has passed
        if ($now->gte($cancellationDeadline)) {
            return [
                'can_reschedule' => false,
                'reason' => 'deadline_passed',
                'message' => 'Cannot reschedule. The cancellation deadline has passed. This will be treated as cancellation.',
                'deadline' => $cancellationDeadline->toDateTimeString(),
                'deadline_passed' => true,
            ];
        }
        
        // Check reschedule limit
        $rescheduleLimit = $this->convertLimitToInteger($reschedulePolicy);
        $currentCount = $booking->reschedule_count ?? 0;
        
        if ($rescheduleLimit !== null && $currentCount >= $rescheduleLimit) {
            return [
                'can_reschedule' => false,
                'reason' => 'limit_exceeded',
                'message' => "Cannot reschedule. You have already rescheduled {$currentCount} time(s). Maximum allowed: {$rescheduleLimit}.",
                'reschedule_count' => $currentCount,
                'reschedule_limit' => $rescheduleLimit,
            ];
        }
        
        // All checks passed
        $remainingReschedules = $rescheduleLimit === null ? 'unlimited' : ($rescheduleLimit - $currentCount);
        
        return [
            'can_reschedule' => true,
            'reschedule_count' => $currentCount,
            'reschedule_limit' => $rescheduleLimit,
            'limit_type' => $reschedulePolicy,
            'deadline' => $cancellationDeadline->toDateTimeString(),
            'deadline_passed' => false,
            'hours_until_deadline' => $now->diffInHours($cancellationDeadline),
            'remaining_reschedules' => $remainingReschedules,
            'message' => $rescheduleLimit === null 
                ? 'You can reschedule this booking unlimited times.'
                : "You can reschedule this booking {$remainingReschedules} more time(s).",
        ];
    }
    
    /**
     * Process client-initiated reschedule
     */
    public function processClientReschedule(
        Booking $booking,
        string $newDate,
        string $newStartTimeUtc,
        string $newEndTimeUtc,
        ?string $reason = null
    ): array {
        // Validate eligibility
        $eligibility = $this->canReschedule($booking, $booking->user_id);
        
        if (!$eligibility['can_reschedule']) {
            throw new \Exception($eligibility['message']);
        }
        
        // Store old values
        $oldDate = $booking->booking_date->format('Y-m-d');
        $oldStartTime = $booking->start_time_utc;
        $oldEndTime = $booking->end_time_utc;
        
        // Update booking
        $booking->update([
            'booking_date' => $newDate,
            'start_time_utc' => $newStartTimeUtc,
            'end_time_utc' => $newEndTimeUtc,
            'reschedule_count' => ($booking->reschedule_count ?? 0) + 1,
            'rescheduled_by' => $booking->user_id,
            'rescheduled_at' => now(),
            'reschedule_status' => 'completed',
            'reschedule_requested_by' => 'client',
            'reschedule_reason' => $reason,
            'action_history' => array_merge(
                $booking->action_history ?? [],
                [[
                    'action' => 'reschedule_completed',
                    'user_id' => $booking->user_id,
                    'user_type' => 'client',
                    'timestamp' => now()->toDateTimeString(),
                    'old_date' => $oldDate,
                    'old_time' => $oldStartTime,
                    'new_date' => $newDate,
                    'new_time' => $newStartTimeUtc,
                    'reschedule_count' => ($booking->reschedule_count ?? 0) + 1,
                    'reason' => $reason,
                ]]
            ),
        ]);
        
        return [
            'booking_id' => $booking->id,
            'old_date' => $oldDate,
            'old_time' => $oldStartTime,
            'new_date' => $newDate,
            'new_time' => $newStartTimeUtc,
            'reschedule_count' => $booking->fresh()->reschedule_count,
            'reschedule_status' => 'completed',
        ];
    }
    
    /**
     * Process artist-initiated reschedule request
     */
    public function processArtistRescheduleRequest(Booking $booking, ?string $reason = null): array
    {
        // Update booking status
        $booking->update([
            'reschedule_status' => 'pending',
            'reschedule_requested_by' => 'artist',
            'reschedule_reason' => $reason,
            'action_history' => array_merge(
                $booking->action_history ?? [],
                [[
                    'action' => 'reschedule_requested',
                    'user_id' => $booking->artist_user_id,
                    'user_type' => 'artist',
                    'timestamp' => now()->toDateTimeString(),
                    'reason' => $reason,
                    'old_date' => $booking->booking_date->format('Y-m-d'),
                    'old_time' => $booking->start_time_utc,
                    'status' => 'pending',
                ]]
            ),
        ]);
        
        return [
            'booking_id' => $booking->id,
            'reschedule_status' => 'pending',
            'reschedule_requested_by' => 'artist',
        ];
    }
    
    /**
     * Process client's response to artist's reschedule request
     */
    public function processArtistRescheduleResponse(
        Booking $booking,
        string $newDate,
        string $newStartTimeUtc,
        string $newEndTimeUtc
    ): array {
        // Check if this is an artist-requested reschedule
        if ($booking->reschedule_status !== 'pending' || $booking->reschedule_requested_by !== 'artist') {
            throw new \Exception('This booking does not have a pending artist reschedule request.');
        }
        
        // Store old values
        $oldDate = $booking->booking_date->format('Y-m-d');
        $oldStartTime = $booking->start_time_utc;
        $oldEndTime = $booking->end_time_utc;
        
        // Update booking (do NOT increment reschedule_count)
        $booking->update([
            'booking_date' => $newDate,
            'start_time_utc' => $newStartTimeUtc,
            'end_time_utc' => $newEndTimeUtc,
            'reschedule_status' => 'completed',
            'rescheduled_by' => $booking->user_id, // Client selects, but artist requested
            'rescheduled_at' => now(),
            'reschedule_count' => $booking->reschedule_count, // Keep same count
            'action_history' => array_merge(
                $booking->action_history ?? [],
                [[
                    'action' => 'reschedule_completed',
                    'user_id' => $booking->user_id,
                    'user_type' => 'client',
                    'timestamp' => now()->toDateTimeString(),
                    'old_date' => $oldDate,
                    'old_time' => $oldStartTime,
                    'new_date' => $newDate,
                    'new_time' => $newStartTimeUtc,
                    'reschedule_count' => $booking->reschedule_count, // Not incremented
                    'requested_by' => 'artist',
                ]]
            ),
        ]);
        
        return [
            'booking_id' => $booking->id,
            'old_date' => $oldDate,
            'old_time' => $oldStartTime,
            'new_date' => $newDate,
            'new_time' => $newStartTimeUtc,
            'reschedule_count' => $booking->reschedule_count,
            'reschedule_status' => 'completed',
        ];
    }
    
    /**
     * Process client declining artist's reschedule request
     */
    public function processDeclineReschedule(Booking $booking, ?string $reason = null): array
    {
        // Update booking
        $booking->update([
            'reschedule_status' => 'declined',
            'action_history' => array_merge(
                $booking->action_history ?? [],
                [[
                    'action' => 'reschedule_declined',
                    'user_id' => $booking->user_id,
                    'user_type' => 'client',
                    'timestamp' => now()->toDateTimeString(),
                    'reason' => $reason,
                ]]
            ),
        ]);
        
        return [
            'booking_id' => $booking->id,
            'reschedule_status' => 'declined',
            'cancellation_initiated' => true,
        ];
    }
    
    /**
     * Convert reschedule policy to integer limit
     */
    private function convertLimitToInteger(string $policy): ?int
    {
        return match($policy) {
            'never' => 0,
            'once' => 1,
            'twice' => 2,
            'unlimited' => null,
            default => 0,
        };
    }
    
    /**
     * Parse cancellation window string to hours
     */
    private function parseCancellationWindow(string $window): int
    {
        // Handle formats like "24", "48h", "72 hours", "3 days"
        $window = strtolower(trim($window));
        
        // Extract number
        preg_match('/(\d+)/', $window, $matches);
        $number = (int)($matches[1] ?? 24);
        
        // Check for days
        if (strpos($window, 'day') !== false) {
            return $number * 24;
        }
        
        // Default to hours
        return $number;
    }
}
