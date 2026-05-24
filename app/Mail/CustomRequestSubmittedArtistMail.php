<?php

namespace App\Mail;

use App\Models\CustomRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomRequestSubmittedArtistMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomRequest $customRequest,
        public string $dashboardUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New custom tattoo request from ' . $this->customRequest->clientDisplayName(),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.custom-request-submitted-artist');
    }
}
