<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\StreamChatService;

class BookingObserver
{
    public function __construct(private StreamChatService $streamChat) {}

    public function created(Booking $booking): void
    {
        if ($booking->isOpenForChat()) {
            $this->streamChat->ensureChannelForBooking($booking);
        }
    }

    public function updated(Booking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        $this->streamChat->syncChannelForPair(
            (int) $booking->user_id,
            (int) $booking->artist_user_id
        );
    }
}
