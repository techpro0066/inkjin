<?php

namespace App\Mail;

use App\Models\UserDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestSpotOpenMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $artistName,
        public string $artistHandle,
        public string $artistInitials,
        public string $city,
        public string $dateRangeLabel,
        public string $locationLabel,
        public string $guestSpotUrl,
        public string $subjectLine,
        public ?UserDetail $userDetail = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.guest-spot-open',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $recipientName = trim($this->recipientName);
        $clientFirst = $recipientName !== ''
            ? (trim((string) preg_replace('/\s+.*/u', '', $recipientName)) ?: $recipientName)
            : 'there';

        $handle = trim($this->artistHandle);
        if ($handle !== '' && ! str_starts_with($handle, '@')) {
            $handle = '@'.$handle;
        }

        $city = trim($this->city) !== '' ? trim($this->city) : 'your city';
        $whoMeta = trim(($handle !== '' ? $handle.' · ' : '').'Guest spot in '.$city);

        $dateRange = trim($this->dateRangeLabel);
        $location = trim($this->locationLabel);

        $introText = 'You asked to hear when '.$this->artistName.' guests in '.$city.'.';
        if ($dateRange !== '' && $location !== '') {
            $introText .= ' Books are open for '.$dateRange.' at '.$location.'.';
        } elseif ($dateRange !== '') {
            $introText .= ' Books are open for '.$dateRange.'.';
        } elseif ($location !== '') {
            $introText .= ' Books are open at '.$location.'.';
        } else {
            $introText .= ' Books are open for this guest spot.';
        }

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $this->artistName,
            'artistInitials' => $this->artistInitials !== '' ? $this->artistInitials : '?',
            'artistAvatarUrl' => $this->avatarUrl(),
            'artistMeta' => $whoMeta,
            'introText' => $introText,
            'stayOnListText' => 'Spots are limited. If these dates don’t work, you stay on the list for the next '.$city.' guest spot.',
            'guestSpotUrl' => $this->guestSpotUrl,
        ];
    }

    private function avatarUrl(): ?string
    {
        if (! $this->userDetail) {
            return null;
        }

        $avatarPath = trim((string) ($this->userDetail->avatar ?? ''));
        if ($avatarPath === '') {
            return null;
        }

        return str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')
            ? $avatarPath
            : asset(ltrim($avatarPath, '/'));
    }
}
