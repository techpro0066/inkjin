<?php

namespace App\Mail;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagedBookingSlotsOfferedUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BookingRequest $bookingRequest,
        public string $chooseTimesUrl,
        public string $requestsUrl,
    ) {}

    public function envelope(): Envelope
    {
        $artist = $this->bookingRequest->artistDisplayName();

        return new Envelope(
            subject: $artist.' shared times for your booking request',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.managed-booking-slots-offered-user');
    }
}
