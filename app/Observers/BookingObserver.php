<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\ArtistReferralFeeWaiverService;
use App\Services\ArtistReferralRewardService;
use App\Services\BookingAftercareService;
use App\Services\BookingConsentService;
use App\Services\MailcoachSubscriberService;
use App\Services\StreamChatService;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    public function __construct(
        private StreamChatService $streamChat,
        private MailcoachSubscriberService $mailcoach,
        private ArtistReferralFeeWaiverService $referralFeeWaiver,
        private ArtistReferralRewardService $referralRewards,
        private BookingConsentService $bookingConsents,
        private BookingAftercareService $bookingAftercare,
    ) {}

    public function created(Booking $booking): void
    {
        if ($booking->isOpenForChat()) {
            $this->streamChat->ensureChannelForBooking($booking);
        }

        $client = $booking->user;
        if ($client) {
            $this->mailcoach->queueSubscribeUser($client, MailcoachSubscriberService::TAG_USER);
        }

        $this->referralFeeWaiver->consumeForPaidBooking($booking);
        $this->referralRewards->evaluateBooking($booking);
        $this->syncConsent($booking);
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('payment_status') && (string) $booking->payment_status === 'paid') {
            $this->referralFeeWaiver->consumeForPaidBooking($booking);
            $this->referralRewards->evaluateBooking($booking);
        }

        if ($booking->wasChanged('status')) {
            $this->streamChat->syncChannelForBooking($booking);

            if ((string) $booking->status === 'completed') {
                $this->referralRewards->evaluateBooking($booking);
                $this->sendAftercare($booking);
            }
        }

        if ($booking->wasChanged([
            'booking_date',
            'start_time_utc',
            'timezone',
        ])) {
            // Allow 72h/24h reminders to fire again for the new session time.
            $booking->forceFill([
                'reminder_72h_sent_at' => null,
                'reminder_24h_sent_at' => null,
            ])->saveQuietly();
        }

        if ($booking->wasChanged([
            'status',
            'booking_date',
            'start_time_utc',
            'timezone',
            'artist_user_id',
            'user_id',
        ])) {
            $this->syncConsent($booking);
        }
    }

    private function sendAftercare(Booking $booking): void
    {
        try {
            $this->bookingAftercare->sendForCompletedBooking($booking);
        } catch (\Throwable $e) {
            Log::error('Failed to send booking aftercare email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncConsent(Booking $booking): void
    {
        try {
            $this->bookingConsents->syncForBooking($booking);
        } catch (\Throwable $e) {
            Log::error('Failed to sync booking consent row', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
