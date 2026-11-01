<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudioArtistInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studioName,
        public string $actionUrl,
        public ?string $relationshipLabel = null,
        public ?int $artistPercent = null,
        public bool $hasBookpayAccount = false,
        public bool $isResend = false,
    ) {
    }

    public function envelope(): Envelope
    {
        $prefix = $this->isResend ? 'Reminder: ' : '';

        return new Envelope(
            subject: $prefix.$this->studioName.' invited you to join on Bookpay',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.studio-artist-invite',
        );
    }
}
