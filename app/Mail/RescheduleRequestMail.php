<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class RescheduleRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();
        $artist = (string) ($data['artistName'] ?? 'your artist');

        return new Envelope(subject: $artist.' asked to move your appointment');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reschedule-request',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $booking = $this->booking;
        $booking->loadMissing(['artist.userDetail', 'user', 'studio', 'tattoo']);

        $userDetail = $booking->artist?->userDetail ?? new UserDetail;
        if ($booking->artist) {
            $userDetail->setRelation('user', $booking->artist);
        }

        $artistName = trim((string) $userDetail->publicDisplayName()) ?: 'your artist';
        $artistFirst = trim((string) ($booking->artist?->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $client = $booking->user;
        $clientFirst = trim((string) ($client?->first_name ?? ''));
        if ($clientFirst === '') {
            $clientFirst = 'there';
        }

        $timezone = trim((string) ($booking->timezone ?: $userDetail->timezone ?: 'UTC')) ?: 'UTC';
        $startLocal = $booking->sessionStartUtc()?->copy()->timezone($timezone);

        $sessionLabel = $startLocal
            ? $startLocal->format('D d/m/Y').', '.$startLocal->format('H:i')
            : $this->fallbackSessionLabel($booking);

        $sessionSpoken = $startLocal
            ? $startLocal->format('D j M').' at '.$startLocal->format('H:i')
            : ($sessionLabel !== '' ? $sessionLabel : 'your current time');

        $design = trim((string) $booking->displayTitle());
        if ($design === '') {
            $design = 'your session';
        }

        $quoteMessage = trim((string) ($this->reason ?? $booking->reschedule_reason ?? ''));

        $pickTimeUrl = $client?->id
            ? URL::temporarySignedRoute(
                'user.post-booking.access',
                now()->addDays(14),
                ['user' => $client->id, 'booking' => $booking->id]
            )
            : route('bookings.reschedule-flow', $booking->id);

        $messageUrl = route('user.chat.index', [
            'artist' => (int) $booking->artist_user_id,
            'booking' => (int) $booking->id,
        ]);

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistInitials' => $this->initials($artistName),
            'artistAvatarUrl' => $this->avatarUrl($userDetail),
            'artistMeta' => trim($design.($sessionLabel !== '' ? ' · '.$sessionLabel : '')),
            'introText' => $artistFirst.' can’t make '.$sessionSpoken.' and asked to move your appointment.',
            'quoteMessage' => $quoteMessage,
            'depositNote' => 'Your deposit stays on the booking. Pick a new time that works for you.',
            'pickTimeUrl' => $pickTimeUrl,
            'messageUrl' => $messageUrl,
        ];
    }

    private function avatarUrl(UserDetail $userDetail): ?string
    {
        $avatarPath = trim((string) ($userDetail->avatar ?? ''));
        if ($avatarPath === '') {
            return null;
        }

        return str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')
            ? $avatarPath
            : asset(ltrim($avatarPath, '/'));
    }

    private function fallbackSessionLabel(Booking $booking): string
    {
        if (! $booking->booking_date) {
            return '';
        }

        try {
            $date = $booking->booking_date instanceof Carbon
                ? $booking->booking_date
                : Carbon::parse((string) $booking->booking_date);

            return $date->format('D d/m/Y');
        } catch (\Throwable) {
            return (string) $booking->booking_date;
        }
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : '?';
    }
}
