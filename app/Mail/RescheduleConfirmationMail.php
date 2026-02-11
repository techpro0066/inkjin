<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RescheduleConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $isArtist;

    public function __construct(Booking $booking, bool $isArtist = false)
    {
        $this->booking = $booking;
        $this->isArtist = $isArtist;
    }

    public function build()
    {
        $subject = $this->isArtist 
            ? 'Booking Rescheduled - New Date Confirmed'
            : 'Your Booking Has Been Rescheduled';
            
        return $this->subject($subject)
            ->view('emails.reschedule-confirmation');
    }
}
