<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminAccountDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName,
        public string $supportEmail,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Inkjin account has been deleted'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-account-deleted'
        );
    }
}
