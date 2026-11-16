<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminAccountStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $supportEmail,
        public bool $deactivated,
        public string $loginUrl = '',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->deactivated
                ? 'Your Bookpay account was deactivated'
                : 'Your Bookpay account was activated'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-account-status'
        );
    }
}
