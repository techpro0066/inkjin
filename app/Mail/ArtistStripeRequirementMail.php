<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistStripeRequirementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $actionUrl,
        public bool $isStudioPayout = false,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action needed: Stripe needs more information for your payouts'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.artist-stripe-requirement'
        );
    }
}
