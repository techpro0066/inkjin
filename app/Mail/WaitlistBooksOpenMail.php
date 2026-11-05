<?php

namespace App\Mail;

use App\Models\UserDetail;
use App\Models\Waitlist;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WaitlistBooksOpenMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Waitlist $waitlist,
        public UserDetail $userDetail,
        public string $profileUrl,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();

        return new Envelope(
            subject: ($data['artistName'] ?? 'Your artist').' is taking bookings again',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.waitlist-books-open',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $userDetail = $this->userDetail;
        $userDetail->loadMissing('user');

        $artistName = trim((string) $userDetail->publicDisplayName()) ?: 'your artist';
        $artistFirst = trim((string) ($userDetail->user?->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $recipientName = trim((string) ($this->waitlist->name ?? ''));
        $clientFirst = $recipientName !== ''
            ? (trim((string) strtok($recipientName, ' ')) ?: $recipientName)
            : 'there';

        $handle = trim((string) ($userDetail->user_name ?? ''));
        if ($handle !== '' && ! str_starts_with($handle, '@')) {
            $handle = '@'.$handle;
        }

        $studio = trim((string) $userDetail->studioNameWithCityCountry());
        if ($studio === '') {
            $studio = trim((string) $userDetail->resolvedStudioName());
        }
        $artistMeta = trim(($handle !== '' ? $handle : '').($handle !== '' && $studio !== '' ? ' · ' : '').$studio);

        $joinedAt = $this->waitlist->created_at
            ? Carbon::parse($this->waitlist->created_at)->timezone(config('app.timezone'))->format('d/m/Y')
            : '';

        $possessive = $this->possessive($artistFirst);
        $introText = $joinedAt !== ''
            ? 'You joined '.$possessive.' waitlist on '.$joinedAt.'. '.$artistFirst.'’s books are open again, so you can book now.'
            : 'You joined '.$possessive.' waitlist. '.$artistFirst.'’s books are open again, so you can book now.';

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistInitials' => $this->initials($artistName),
            'artistAvatarUrl' => $this->avatarUrl($userDetail),
            'artistMeta' => $artistMeta,
            'introText' => $introText,
            'urgencyText' => 'Spots go quickly, so don’t wait too long.',
            'bookUrl' => $this->profileUrl,
            'waitlistNote' => 'You got this because you joined '.$possessive.' waitlist. You won’t get more emails about it.',
        ];
    }

    private function possessive(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'your artist’s';
        }

        return str_ends_with(strtolower($name), 's') ? $name.'’' : $name.'’s';
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

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : '?';
    }
}
