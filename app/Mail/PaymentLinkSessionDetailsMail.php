<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\PaymentLink;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PaymentLinkSessionDetailsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public User $client,
        public UserDetail $userDetail,
        public PaymentLink $paymentLink,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A few quick questions before your session with '.$this->artistName(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-link-session-details',
            with: [
                'clientFirst' => $this->clientFirstName(),
                'artistName' => $this->artistName(),
                'sessionDate' => $this->sessionDateLabel(),
                'sessionDetailsUrl' => $this->sessionDetailsUrl(),
            ],
        );
    }

    private function sessionDetailsUrl(): string
    {
        return URL::temporarySignedRoute(
            'public.payment-link.session-details',
            $this->paymentLink->sessionDetailsExpiresAt(),
            [
                'code' => $this->paymentLink->code,
                'booking' => $this->booking->id,
            ]
        );
    }

    private function artistName(): string
    {
        $name = trim((string) $this->userDetail->publicDisplayName());

        return $name !== '' ? $name : 'your artist';
    }

    private function clientFirstName(): string
    {
        $fromPayer = trim((string) strtok((string) ($this->paymentLink->payer_name ?? ''), ' '));
        if ($fromPayer !== '') {
            return $fromPayer;
        }

        return trim((string) ($this->client->first_name ?? '')) ?: 'there';
    }

    private function sessionDateLabel(): string
    {
        $timezone = $this->booking->timezone ?: ($this->userDetail->timezone ?: 'UTC');
        $bookingDate = $this->booking->booking_date instanceof Carbon
            ? $this->booking->booking_date->format('Y-m-d')
            : (string) $this->booking->booking_date;

        try {
            $startLocal = Carbon::parse($bookingDate.' '.$this->booking->start_time_utc, 'UTC')
                ->timezone($timezone);

            return $startLocal->format('D j M').' at '.$startLocal->format('H:i');
        } catch (\Throwable) {
            return Carbon::parse($bookingDate)->format('D j M');
        }
    }
}
