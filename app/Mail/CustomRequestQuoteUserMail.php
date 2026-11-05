<?php

namespace App\Mail;

use App\Models\CustomRequest;
use App\Support\RequestReceivedEmailPresenter;
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
        public string $askQuestionUrl = '',
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();
        $artist = (string) ($data['artistName'] ?? 'your artist');
        $price = (string) ($data['priceLabel'] ?? '');
        $subject = $price !== '' && $price !== '—'
            ? 'Your quote from '.$artist.': '.$price
            : 'Your quote from '.$artist;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.custom-request-quote-user',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        return RequestReceivedEmailPresenter::forQuote(
            $this->customRequest,
            $this->accessUrl,
            $this->askQuestionUrl !== ''
                ? $this->askQuestionUrl
                : $this->accessUrl,
        );
    }
}
