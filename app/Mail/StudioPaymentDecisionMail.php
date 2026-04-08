<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudioPaymentDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studioName,
        public string $artistName,
        public string $allowUrl,
        public string $declineUrl,
        public bool $existingStudio = false
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Studio payment approval request'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.studio-payment-decision'
        );
    }
}

