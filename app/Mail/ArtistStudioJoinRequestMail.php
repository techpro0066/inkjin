<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistStudioJoinRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studioName,
        public string $artistName,
        public string $actionUrl,
        public bool $hasStudioAccount = true,
        public ?string $relationshipLabel = null,
        public ?int $artistPercent = null,
        public bool $isResend = false,
    ) {
    }

    public function envelope(): Envelope
    {
        $prefix = $this->isResend ? 'Reminder: ' : '';

        $subject = $this->hasStudioAccount
            ? $this->artistName.' wants to join '.$this->studioName.' on Bookpay'
            : $this->artistName.' invited you to Bookpay';

        return new Envelope(
            subject: $prefix.$subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.artist-studio-join-request',
        );
    }
}
