<?php

namespace App\Mail;

use App\Models\ConsentAnswer;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConsentFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ConsentAnswer $consent,
        public User $client,
        public ?UserDetail $userDetail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your appointment with '.$this->artistName().' is tomorrow',
        );
    }

    public function content(): Content
    {
        $booking = $this->consent->booking;
        $local = $booking?->sessionStartLocal();

        return new Content(
            view: 'emails.consent-form',
            with: [
                'artistName' => $this->artistName(),
                'clientFirst' => trim((string) ($this->client->first_name ?? '')) ?: 'there',
                'appointmentDate' => $local ? $local->format('l, F j, Y') : '',
                'appointmentTime' => $local ? $local->format('g:i A') : '',
                'studioName' => trim((string) ($this->userDetail?->studio_name ?? '')),
                'studioAddress' => $this->studioAddress(),
                'consentUrl' => $this->consent->publicUrl(),
            ],
        );
    }

    private function artistName(): string
    {
        if ($this->userDetail) {
            $name = trim((string) $this->userDetail->publicDisplayName());
            if ($name !== '') {
                return $name;
            }
        }

        return 'your artist';
    }

    private function studioAddress(): string
    {
        if (! $this->userDetail) {
            return '';
        }

        $street = trim(trim((string) ($this->userDetail->street_number ?? '')).' '.trim((string) ($this->userDetail->street_name ?? '')));
        if ($street === '') {
            $street = trim((string) ($this->userDetail->studio_address ?? ''));
        }

        $cityLine = implode(', ', array_values(array_filter([
            trim((string) ($this->userDetail->city ?? '')),
            trim((string) ($this->userDetail->postal_code ?? '')),
            trim((string) ($this->userDetail->country ?? '')),
        ], fn (string $part) => $part !== '')));

        return implode(', ', array_values(array_filter([$street, $cityLine], fn (string $part) => $part !== '')));
    }
}
