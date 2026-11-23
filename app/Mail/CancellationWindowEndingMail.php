<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CancellationWindowEndingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public User $client,
        public UserDetail $artistDetail,
        public array $reminderData,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Free cancellation ends '.$this->reminderData['deadlineLabel']
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cancellation-window-ending',
            with: $this->reminderData,
        );
    }
}
