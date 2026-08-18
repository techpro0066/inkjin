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

class PaymentLinkArtistBookedMail extends Mailable
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
            subject: $this->clientFirstName().' just booked — '.$this->paidAmount().' received',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-link-artist-booked',
            with: [
                'artistName' => $this->artistName(),
                'clientFirst' => $this->clientFirstName(),
                'title' => $this->sessionTitle(),
                'paidAmount' => $this->paidAmount(),
                'isDeposit' => $this->paymentLink->payment_type === 'deposit',
                'sessionDate' => $this->sessionDate(),
                'sessionTime' => $this->sessionTime(),
                'duration' => $this->durationLabel(),
                'dueAmount' => $this->dueAmount(),
                'viewBookingUrl' => route('artist.bookings.index', ['booking' => $this->booking->id]),
            ],
        );
    }

    private function artistName(): string
    {
        $name = trim((string) $this->userDetail->publicDisplayName());

        return $name !== '' ? $name : 'there';
    }

    private function clientFirstName(): string
    {
        $fromPayer = trim((string) strtok((string) ($this->paymentLink->payer_name ?? ''), ' '));
        if ($fromPayer !== '') {
            return $fromPayer;
        }

        return trim((string) ($this->client->first_name ?? '')) ?: 'A client';
    }

    private function sessionTitle(): string
    {
        $title = trim((string) ($this->paymentLink->title ?? ''));

        return $title !== '' ? $title : $this->booking->displayTitle();
    }

    private function paidAmount(): string
    {
        return $this->formatEuro((float) $this->paymentLink->amount);
    }

    private function dueAmount(): string
    {
        if ($this->paymentLink->payment_type !== 'deposit') {
            return $this->formatEuro(0);
        }

        return $this->formatEuro((float) $this->paymentLink->due_amount);
    }

    private function durationLabel(): string
    {
        $duration = (string) $this->paymentLink->session_duration;

        return match ($duration) {
            'half-day' => 'Half day',
            'full-day' => 'Full day',
            default => $duration !== '' ? $duration : '3h',
        };
    }

    private function sessionStartLocal(): ?Carbon
    {
        $timezone = $this->booking->timezone ?: ($this->userDetail->timezone ?: 'UTC');
        $bookingDate = $this->booking->booking_date instanceof Carbon
            ? $this->booking->booking_date->format('Y-m-d')
            : (string) $this->booking->booking_date;

        try {
            return Carbon::parse($bookingDate.' '.$this->booking->start_time_utc, 'UTC')
                ->timezone($timezone);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sessionDate(): string
    {
        $start = $this->sessionStartLocal();
        if ($start) {
            return $start->format('D j M');
        }

        try {
            return Carbon::parse((string) $this->booking->booking_date)->format('D j M');
        } catch (\Throwable) {
            return (string) $this->booking->booking_date;
        }
    }

    private function sessionTime(): string
    {
        $start = $this->sessionStartLocal();

        return $start ? $start->format('H:i') : '';
    }

    private function formatEuro(float $value): string
    {
        $rounded = round($value, 2);
        if (fmod($rounded, 1.0) === 0.0) {
            return '€'.(string) (int) $rounded;
        }

        return '€'.number_format($rounded, 2, '.', '');
    }
}
