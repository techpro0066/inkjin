<?php

namespace App\Mail;

use App\Models\ConsentAnswer;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
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
        $data = $this->viewData();
        $before = (string) ($data['subjectBeforeLabel'] ?? '');
        $subject = $before !== ''
            ? 'Fill in your consent form before '.$before
            : 'Fill in your consent form';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.consent-form',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $booking = $this->consent->booking;
        $booking?->loadMissing(['artist.userDetail', 'studio', 'tattoo']);

        $userDetail = $this->userDetail ?? $booking?->artist?->userDetail ?? new UserDetail;
        $artistName = $this->artistName($userDetail);
        $artistFirst = trim((string) ($booking?->artist?->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $clientFirst = trim((string) ($this->client->first_name ?? ''));
        if ($clientFirst === '') {
            $clientFirst = 'there';
        }

        $timezone = trim((string) ($booking?->timezone ?: $userDetail->timezone ?: 'UTC')) ?: 'UTC';
        $startLocal = $booking?->sessionStartUtc()?->copy()->timezone($timezone);

        $sessionLabel = $startLocal
            ? $startLocal->format('D d/m/Y').', '.$startLocal->format('H:i')
            : $this->fallbackSessionLabel($booking);

        $subjectBeforeLabel = $startLocal
            ? $startLocal->format('D j M')
            : '';

        $design = trim((string) ($booking?->displayTitle() ?? ''));
        if ($design === '') {
            $design = 'your session';
        }

        $whoMeta = trim($design.($sessionLabel !== '' ? ' · '.$sessionLabel : ''));

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistInitials' => $this->initials($artistName),
            'artistAvatarUrl' => $this->avatarUrl($userDetail),
            'artistMeta' => $whoMeta,
            'introText' => $artistFirst.' needs your consent form before your session. It takes about 3 minutes, and you can do it on your phone.',
            'consentUrl' => $this->consent->publicUrl(),
            'subjectBeforeLabel' => $subjectBeforeLabel,
            'under18Note' => 'Under 18? A parent or guardian must sign and come to the session.',
        ];
    }

    private function artistName(UserDetail $userDetail): string
    {
        $name = trim((string) $userDetail->publicDisplayName());

        return $name !== '' ? $name : 'your artist';
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

    private function fallbackSessionLabel($booking): string
    {
        if (! $booking?->booking_date) {
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
