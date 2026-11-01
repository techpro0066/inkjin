<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestSpotStudioInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $artistName,
        public string $artistInitials,
        public string $artistMeta,
        public string $studioGreetingName,
        public string $dateRangeLabel,
        public string $actionUrl,
        public ?int $artistPercent = null,
        public bool $isResend = false,
    ) {
    }

    public function envelope(): Envelope
    {
        $prefix = $this->isResend ? 'Reminder: ' : '';

        return new Envelope(
            subject: $prefix.$this->artistName.' wants a guest spot at your studio',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.guest-spot-studio-invite',
        );
    }
}
