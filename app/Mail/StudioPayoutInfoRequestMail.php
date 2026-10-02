<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudioPayoutInfoRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studioName,
        public string $artistName,
        public string $formUrl,
        public bool $showApproveDecline = false,
        public ?string $approveUrl = null,
        public ?string $declineUrl = null,
        public bool $requirementsReminder = false,
        public string $relationshipPhrase = 'studio',
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = match (true) {
            $this->requirementsReminder => 'Complete Stripe requirements — '.$this->artistName.' on Bookpay',
            default => $this->artistName.' invited you to Bookpay',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.studio-payout-info-request'
        );
    }
}
