<?php

namespace App\Mail;

use App\Models\CustomRequest;
use App\Support\RequestReceivedEmailPresenter;
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
        $data = $this->viewData();
        $client = (string) ($data['clientDisplayName'] ?? 'Client');
        $idea = trim((string) ($data['ideaLabel'] ?? ''));
        $subject = $idea !== '' && $idea !== 'your custom tattoo request'
            ? "New request from {$client}: {$idea}"
            : "New request from {$client}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.custom-request-submitted-artist',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        return RequestReceivedEmailPresenter::forCustomRequestArtist(
            $this->customRequest,
            $this->dashboardUrl,
        );
    }
}
