<?php

namespace App\Mail;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagedBookingDeclinedArtistMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BookingRequest $bookingRequest,
        public string $requestsUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Decline confirmation — '.$this->bookingRequest->referenceLabel(),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.managed-booking-declined-artist');
    }
}
