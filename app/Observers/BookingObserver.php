<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\ArtistReferralFeeWaiverService;
use App\Services\ArtistReferralRewardService;
use App\Services\MailcoachSubscriberService;
use App\Services\StreamChatService;

class BookingObserver
{
    public function __construct(
        private StreamChatService $streamChat,
        private MailcoachSubscriberService $mailcoach,
        private ArtistReferralFeeWaiverService $referralFeeWaiver,
        private ArtistReferralRewardService $referralRewards,
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
            }
        }
    }
}
