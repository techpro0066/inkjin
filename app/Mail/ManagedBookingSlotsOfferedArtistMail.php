<?php

namespace App\Mail;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagedBookingSlotsOfferedArtistMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BookingRequest $bookingRequest,
        public string $requestsUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Copy: times sent to '.$this->bookingRequest->clientDisplayName(),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.managed-booking-slots-offered-artist');
    }
}
