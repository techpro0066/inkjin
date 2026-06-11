<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudioPayoutDeclinedArtistMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $artistName,
        public string $studioName,
        public string $paymentSettingsUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Studio payout request declined — '.$this->studioName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.studio-payout-declined-artist',
        );
    }
}
