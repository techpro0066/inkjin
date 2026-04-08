<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudioFirstTimeConnectMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studioName,
        public string $artistName,
        public string $connectUrl
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Please connect Stripe for studio payouts'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.studio-first-time-connect'
        );
    }
}

