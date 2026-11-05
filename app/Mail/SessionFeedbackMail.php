<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class SessionFeedbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public Review $review,
        public User $client,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'How was your session with '.$this->artistDisplayName().'?',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.session-feedback',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $booking = $this->booking;
        $booking->loadMissing(['artist.userDetail', 'studio', 'tattoo']);

        $artistName = $this->artistDisplayName();
        $artistFirst = trim((string) ($booking->artist?->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $clientFirst = trim((string) ($this->client->first_name ?? ''));
        if ($clientFirst === '') {
            $clientFirst = 'there';
        }

        $design = trim((string) $booking->displayTitle());
        if ($design === '') {
            $design = 'Tattoo session';
        }

        $studioName = trim((string) ($booking->studio?->name ?? ''));
        $dateLabel = $this->sessionDateLabel($booking);
        $metaParts = array_values(array_filter([
            $design,
            $dateLabel !== '' ? $dateLabel : null,
            $studioName !== '' ? $studioName : null,
        ]));

        $avatar = trim((string) ($booking->artist?->userDetail?->avatar ?? ''));
        $avatarUrl = $avatar !== '' ? asset(ltrim($avatar, '/')) : null;
        $initials = $booking->artist?->userDetail?->publicDisplayInitials()
            ?: strtoupper(substr(preg_replace('/\s+/', '', $artistName) ?: 'AR', 0, 2));

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistAvatarUrl' => $avatarUrl,
            'artistInitials' => $initials,
            'sessionMeta' => implode(' · ', $metaParts),
            'feedbackUrl' => URL::temporarySignedRoute(
                'public.session-feedback',
                now()->addDays(30),
                ['review' => $this->review->id]
            ),
        ];
    }

    private function artistDisplayName(): string
    {
        $booking = $this->booking;
        $detail = $booking->artist?->userDetail;
        $name = $detail ? trim((string) $detail->publicDisplayName()) : '';
        if ($name !== '' && $name !== 'Artist') {
            return $name;
        }

        $fallback = trim(trim((string) ($booking->artist?->first_name ?? '')).' '.trim((string) ($booking->artist?->last_name ?? '')));

        return $fallback !== '' ? $fallback : 'your artist';
    }

    private function sessionDateLabel(Booking $booking): string
    {
        $timezone = trim((string) ($booking->timezone ?: $booking->artist?->userDetail?->timezone ?: 'UTC')) ?: 'UTC';
        $startLocal = $booking->sessionStartUtc()?->copy()->timezone($timezone);
        if ($startLocal) {
            return $startLocal->format('D j/m/Y');
        }

        if (! $booking->booking_date) {
            return '';
        }

        try {
            $date = $booking->booking_date instanceof Carbon
                ? $booking->booking_date
                : Carbon::parse((string) $booking->booking_date);

            return $date->format('D j/m/Y');
        } catch (\Throwable) {
            return '';
        }
    }
}
