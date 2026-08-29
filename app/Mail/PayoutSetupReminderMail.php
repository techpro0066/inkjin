<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayoutSetupReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $payoutsUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Don't miss your next booking, set up payouts."
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payout-setup-reminder'
        );
    }
}
