<?php

namespace App\Mail;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagedBookingDeclinedUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BookingRequest $bookingRequest,
        public string $requestsUrl,
    ) {}

    public function envelope(): Envelope
    {
        $artist = $this->bookingRequest->artistDisplayName();

        return new Envelope(
            subject: 'Update on your booking request — '.$artist,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.managed-booking-declined-user');
    }
}
