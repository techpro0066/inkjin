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
        return new Envelope(
            subject: $this->clientName().' submitted their consent form',
        );
    }

    public function content(): Content
    {
        $booking = $this->consent->booking;
        $local = $booking?->sessionStartLocal();

        return new Content(
            view: 'emails.consent-form-submitted-artist',
            with: [
                'artistName' => $this->artistDisplayName(),
                'clientName' => $this->clientName(),
                'appointmentDate' => $local ? $local->format('l, F j, Y') : '',
                'appointmentTime' => $local ? $local->format('g:i A') : '',
                'signedAt' => $this->consent->completed_at
                    ? $this->consent->completed_at->timezone($booking?->timezone ?: config('app.timezone'))->format('M j, Y · g:i A')
                    : '',
                'bookingsUrl' => route('artist.bookings.index'),
            ],
        );
    }

    private function artistDisplayName(): string
    {
        $detail = $this->userDetail;
        if ($detail) {
            $name = trim((string) $detail->publicDisplayName());
            if ($name !== '') {
                return $name;
            }
        }

        return trim((string) ($this->artist->first_name ?? '')) ?: 'there';
    }

    private function clientName(): string
    {
        $fromForm = trim((string) ($this->consent->full_name ?? ''));
        if ($fromForm !== '') {
            return $fromForm;
        }

        $fromSig = trim((string) ($this->consent->typed_signature ?? ''));
        if ($fromSig !== '') {
            return $fromSig;
        }

        $client = $this->consent->booking?->user;
        $name = trim(implode(' ', array_filter([
            (string) ($client?->first_name ?? ''),
            (string) ($client?->last_name ?? ''),
        ])));

        return $name !== '' ? $name : 'Your client';
    }
}
