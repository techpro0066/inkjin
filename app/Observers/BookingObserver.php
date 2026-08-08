<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\MailcoachSubscriberService;
use App\Services\StreamChatService;

class BookingObserver
{
    public function __construct(
        private StreamChatService $streamChat,
        private MailcoachSubscriberService $mailcoach,
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
    }

    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        $this->streamChat->syncChannelForBooking($booking);
    }
}
