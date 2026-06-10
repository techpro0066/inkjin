<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CountryNotAvailableMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $countryName
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We\'re not in your country yet'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.country-not-available'
        );
    }
}
