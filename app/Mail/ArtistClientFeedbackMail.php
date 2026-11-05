<?php

namespace App\Mail;

use App\Models\ArtistClientFeedback;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ArtistClientFeedbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ArtistClientFeedback $feedback,
        public User $artist,
        public User $client,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'How was your session with '.$this->clientDisplayName().'?',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.artist-client-feedback',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $booking = $this->booking;
        $booking->loadMissing(['studio', 'tattoo', 'user']);

        $clientDisplay = $this->clientDisplayName();
        $clientFirst = $this->clientFirstName();
        $artistFirst = trim((string) ($this->artist->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = 'there';
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

        return [
            'artistFirstName' => $artistFirst,
            'clientDisplayName' => $clientDisplay,
            'clientFirstName' => $clientFirst,
            'clientInitials' => $this->initials($clientDisplay),
            'sessionMeta' => implode(' · ', $metaParts),
            'feedbackUrl' => URL::temporarySignedRoute(
                'public.artist-client-feedback',
                now()->addDays(30),
                ['feedback' => $this->feedback->id]
            ),
        ];
    }

    private function clientDisplayName(): string
    {
        $first = trim((string) ($this->client->first_name ?? ''));
        $last = trim((string) ($this->client->last_name ?? ''));
        if ($first === '') {
            return 'your client';
        }

        $display = ucfirst($first);
        if ($last !== '') {
            $display .= ' '.strtoupper(mb_substr($last, 0, 1)).'.';
        }

        return $display;
    }

    private function clientFirstName(): string
    {
        $first = trim((string) ($this->client->first_name ?? ''));

        return $first !== '' ? ucfirst($first) : 'your client';
    }

    private function sessionDateLabel(Booking $booking): string
    {
        $timezone = trim((string) ($booking->timezone ?: 'UTC')) ?: 'UTC';
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

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : 'CL';
    }
}
