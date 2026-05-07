<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RescheduleToArtistMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?string $rescheduleNote = null
    ) {
    }

    public function build()
    {
        return $this->subject('Client Rescheduled Their Booking')
            ->view('emails.reschedule-to-artist');
    }
}
