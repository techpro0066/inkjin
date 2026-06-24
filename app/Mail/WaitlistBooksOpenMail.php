<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaitlistBooksOpenMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $artistName,
        public string $profileUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->artistName.' is now accepting bookings',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.waitlist-books-open',
        );
    }
}
