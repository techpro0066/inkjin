<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $dashboardUrl
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Inkjin Book & Pay!'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.artist-welcome'
        );
    }
}

