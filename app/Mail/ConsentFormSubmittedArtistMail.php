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

class ConsentFormSubmittedArtistMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ConsentAnswer $consent,
        public User $artist,
        public ?UserDetail $userDetail = null,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();
        $client = (string) ($data['clientDisplayName'] ?? 'Client');

        return new Envelope(
            subject: ! empty($data['guardianSigned'])
                ? $client.': guardian signed the consent form'
                : $client.' signed the consent form',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.consent-form-submitted-artist',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $this->consent->loadMissing(['booking.user', 'booking.tattoo']);

        $booking = $this->consent->booking;
        $timezone = $booking?->timezone ?: config('app.timezone');

        $signedAt = $this->consent->completed_at
            ? $this->consent->completed_at->timezone($timezone)
            : null;

        $sessionLocal = $booking?->sessionStartLocal();
        $whenShort = $sessionLocal
            ? $sessionLocal->format('D d/m/Y, H:i')
            : '';

        $designTitle = trim((string) ($booking?->displayTitle() ?? ''));
        $whoMeta = trim(($designTitle !== '' ? $designTitle : '').($designTitle !== '' && $whenShort !== '' ? ' · ' : '').$whenShort);

        $guardianSigned = trim((string) ($this->consent->guardian_signature ?? '')) !== '';
        $signerFirst = $guardianSigned
            ? $this->firstNameFromFull(trim((string) ($this->consent->guardian_name ?? '')) ?: 'The guardian')
            : $this->clientFirstName();

        $signedLabel = $signedAt
            ? $signedAt->format('d/m/Y').' at '.$signedAt->format('H:i')
            : '';

        $okText = $signerFirst.' signed the consent form'
            .($signedLabel !== '' ? ' on '.$signedLabel : '').'.';

        $bookingId = (int) ($booking?->id ?? 0);

        return [
            'artistFirstName' => $this->artistFirstName(),
            'clientDisplayName' => $this->clientDisplayName(),
            'clientInitials' => $this->initials($this->clientDisplayName()),
            'whoMeta' => $whoMeta,
            'okText' => $okText,
            'guardianSigned' => $guardianSigned,
            'viewConsentUrl' => $bookingId > 0
                ? route('artist.bookings.index', ['booking' => $bookingId])
                : route('artist.bookings.index'),
        ];
    }

    private function artistFirstName(): string
    {
        $first = trim((string) ($this->artist->first_name ?? ''));
        if ($first !== '') {
            return $first;
        }

        $detail = $this->userDetail;
        if ($detail) {
            $name = trim((string) $detail->publicDisplayName());
            if ($name !== '') {
                return trim((string) strtok($name, ' ')) ?: $name;
            }
        }

        return 'there';
    }

    private function clientDisplayName(): string
    {
        $client = $this->consent->booking?->user;
        $first = trim((string) ($client?->first_name ?? ''));
        $last = trim((string) ($client?->last_name ?? ''));

        if ($first === '') {
            $fromForm = trim((string) ($this->consent->full_name ?? ''));
            if ($fromForm !== '') {
                return $this->shortDisplayName($fromForm);
            }

            return 'Client';
        }

        $display = ucfirst($first);
        if ($last !== '') {
            $display .= ' '.strtoupper(mb_substr($last, 0, 1)).'.';
        }

        return $display;
    }

    private function clientFirstName(): string
    {
        $client = $this->consent->booking?->user;
        $first = trim((string) ($client?->first_name ?? ''));
        if ($first !== '') {
            return ucfirst($first);
        }

        $fromForm = trim((string) ($this->consent->full_name ?? ''));
        if ($fromForm !== '') {
            return $this->firstNameFromFull($fromForm);
        }

        return 'Your client';
    }

    private function shortDisplayName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = trim((string) ($parts[0] ?? ''));
        if ($first === '') {
            return 'Client';
        }

        $display = ucfirst($first);
        $last = trim((string) ($parts[1] ?? ''));
        if ($last !== '') {
            $display .= ' '.strtoupper(mb_substr($last, 0, 1)).'.';
        }

        return $display;
    }

    private function firstNameFromFull(string $fullName): string
    {
        $first = trim((string) strtok(trim($fullName), ' '));

        return $first !== '' ? ucfirst($first) : 'Client';
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : 'CL';
    }
}
