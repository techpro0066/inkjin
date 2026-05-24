<?php

namespace App\Mail;

use App\Models\CustomRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomRequestQuoteUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomRequest $customRequest,
        public string $accessUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your custom tattoo quote from '.$this->customRequest->artistDisplayName(),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.custom-request-quote-user');
    }
}
