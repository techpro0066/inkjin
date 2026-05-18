<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagedBookingRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $accessUrl,
        public string $recipientName,
        public string $artistName,
        public string $designTitle,
        public string $bookingReference,
        public bool $isNewUser = false,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->isNewUser
            ? 'Welcome to Inkjin — your booking request was submitted'
            : 'Your booking request was submitted — '.$this->artistName;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.managed-booking-request-user');
    }
}
