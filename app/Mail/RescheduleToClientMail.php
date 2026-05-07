<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RescheduleToClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public bool $isArtistRequested = false
    ) {
    }

    public function build()
    {
        return $this->subject('Your Booking Has Been Rescheduled')
            ->view('emails.reschedule-to-client');
    }
}
