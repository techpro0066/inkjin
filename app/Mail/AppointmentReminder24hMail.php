<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminder24hMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public User $client,
        public ?UserDetail $artistDetail,
        public ?string $consentUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your appointment with '.$this->artistName().' is tomorrow',
        );
    }

    public function content(): Content
    {
        $local = $this->booking->sessionStartLocal();

        return new Content(
            view: 'emails.appointment-reminder-24h',
            with: [
                'artistName' => $this->artistName(),
                'clientFirst' => trim((string) ($this->client->first_name ?? '')) ?: 'there',
                'appointmentDate' => $local ? $local->format('l, F j, Y') : '',
                'appointmentTime' => $local ? $local->format('g:i A') : '',
                'studioName' => trim((string) ($this->artistDetail?->studio_name ?? '')),
                'studioAddress' => $this->studioAddress(),
                'consentUrl' => $this->consentUrl,
                'showConsentCta' => filled($this->consentUrl),
            ],
        );
    }

    private function artistName(): string
    {
        if ($this->artistDetail) {
            $name = trim((string) $this->artistDetail->publicDisplayName());
            if ($name !== '') {
                return $name;
            }
        }

        return 'your artist';
    }

    private function studioAddress(): string
    {
        if (! $this->artistDetail) {
            return '';
        }

        $street = trim(trim((string) ($this->artistDetail->street_number ?? '')).' '.trim((string) ($this->artistDetail->street_name ?? '')));
        if ($street === '') {
            $street = trim((string) ($this->artistDetail->studio_address ?? ''));
        }

        $cityLine = implode(', ', array_values(array_filter([
            trim((string) ($this->artistDetail->city ?? '')),
            trim((string) ($this->artistDetail->postal_code ?? '')),
            trim((string) ($this->artistDetail->country ?? '')),
        ], fn (string $part) => $part !== '')));

        return implode(', ', array_values(array_filter([$street, $cityLine], fn (string $part) => $part !== '')));
    }
}
