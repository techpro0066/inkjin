<?php

namespace App\Services;

use App\Exceptions\GoogleCalendarEventRequiredException;
use App\Http\Controllers\GoogleCalendarController;
use App\Models\Booking;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Log;

class GoogleCalendarBookingSyncService
{
    public function __construct(
        private readonly CancellationService $cancellationService,
    ) {}

    public function isAutoScheduling(?UserDetail $userDetail): bool
    {
        return $userDetail !== null && ($userDetail->scheduling_type ?? '') === 'auto';
    }

    /**
     * Block starting payment when auto-scheduling artists do not have a usable calendar connection.
     */
    public function assertReadyForPayment(?UserDetail $userDetail): void
    {
        if (! $this->isAutoScheduling($userDetail)) {
            return;
        }

        if (empty($userDetail->google_calendar_token) || empty($userDetail->google_calendar_id)) {
            throw GoogleCalendarEventRequiredException::notConnected();
        }
    }

    /**
     * Create the Google Calendar event for a booking.
     * For auto-scheduling artists this is required — failure throws and the caller must abort confirmation.
     */
    public function syncForBooking(Booking $booking, ?string $consultationType = null): void
    {
        $booking->loadMissing(['artist.userDetail', 'tattoo', 'user']);
        $userDetail = $booking->artist?->userDetail;
        $required = $this->isAutoScheduling($userDetail);

        if (! $userDetail || empty($userDetail->google_calendar_token)) {
            if ($required) {
                throw GoogleCalendarEventRequiredException::notConnected();
            }

            return;
        }

        if ($required && empty($userDetail->google_calendar_id)) {
            throw GoogleCalendarEventRequiredException::notConnected();
        }

        try {
            $calendarResult = GoogleCalendarController::createCalendarEvent(
                $userDetail,
                $booking,
                (bool) $booking->has_consultation,
                $consultationType
            );
        } catch (\Throwable $e) {
            Log::error('Google Calendar event create threw', [
                'booking_id' => $booking->id,
                'required' => $required,
                'error' => $e->getMessage(),
            ]);

            if ($required) {
                throw GoogleCalendarEventRequiredException::createFailed();
            }

            return;
        }

        if (is_array($calendarResult) && ! empty($calendarResult['event_id'])) {
            $booking->update([
                'google_calendar_event_id' => $calendarResult['event_id'],
                'google_meet_link' => $calendarResult['meet_link'] ?? null,
            ]);
            $booking->refresh();

            return;
        }

        if ($required) {
            Log::error('Google Calendar event required but create returned empty result', [
                'booking_id' => $booking->id,
                'artist_user_id' => $booking->artist_user_id,
            ]);

            throw GoogleCalendarEventRequiredException::createFailed();
        }
    }

    /**
     * Cancel an unconfirmed booking and refund Stripe when calendar sync fails after payment.
     */
    public function abortFailedCalendarBooking(Booking $booking, string $reason): void
    {
        $booking->refresh();

        $actionHistory = is_array($booking->action_history) ? $booking->action_history : [];
        $actionHistory[] = [
            'action' => 'calendar_sync_failed',
            'reason' => $reason,
            'at' => now()->toIso8601String(),
        ];

        $refundAmount = (float) ($booking->total_amount_paid ?? 0);

        try {
            if ($booking->payment_intent_id && $refundAmount > 0) {
                $this->cancellationService->processStripeRefund(
                    $booking,
                    $refundAmount,
                    'google_calendar_event_failed'
                );
            }
        } catch (\Throwable $e) {
            Log::error('Failed to refund booking after calendar sync failure', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        $booking->update([
            'status' => 'cancelled',
            'payment_status' => $booking->payment_intent_id ? 'refunded' : ($booking->payment_status ?? 'failed'),
            'cancelled_at' => now(),
            'cancellation_reason' => 'Google Calendar event could not be created (auto scheduling).',
            'refund_amount' => $refundAmount > 0 ? $refundAmount : ($booking->refund_amount ?? null),
            'google_calendar_event_id' => null,
            'google_meet_link' => null,
            'action_history' => $actionHistory,
        ]);

        Log::warning('Booking aborted because Google Calendar event was required and failed', [
            'booking_id' => $booking->id,
            'reason' => $reason,
            'refund_amount' => $refundAmount,
        ]);
    }
}
