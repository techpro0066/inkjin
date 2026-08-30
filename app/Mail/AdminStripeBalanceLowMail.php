<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminStripeBalanceLowMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $source,
        public float $requestedAmount,
        public float $availableAmount,
        public string $currency,
        public ?string $artistName = null,
        public ?int $bookingId = null,
        public ?string $bookingReference = null,
        public ?string $dashboardUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action required: Stripe balance too low for artist payout',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-stripe-balance-low',
        );
    }
}
