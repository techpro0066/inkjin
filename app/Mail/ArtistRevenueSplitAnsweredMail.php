<?php

namespace App\Mail;

use App\Models\Studio;
use App\Models\User;
use App\Models\UserStudio;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistRevenueSplitAnsweredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserStudio $userStudio,
        public bool $accepted,
        public int $beforeArtistPercent,
        public int $proposedArtistPercent,
        public ?Carbon $appliesFrom = null,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();
        $studio = (string) ($data['studioName'] ?? 'The studio');

        return new Envelope(
            subject: $this->accepted
                ? $studio.' accepted your new split'
                : $studio.' declined your new split',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.artist-revenue-split-answered',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $link = $this->userStudio;
        $link->loadMissing(['studio', 'user.userDetail']);

        $studio = $link->studio;
        $artist = $link->user;

        $studioName = trim((string) ($studio?->name ?? $link->studio_name ?? 'Studio'));
        if ($studioName === '') {
            $studioName = 'Studio';
        }

        $beforeArtist = max(0, min(100, $this->beforeArtistPercent));
        $proposedArtist = max(0, min(100, $this->proposedArtistPercent));
        $nowArtist = $this->accepted ? $proposedArtist : $beforeArtist;

        $beforeLabel = 'You '.$beforeArtist.'% · Studio '.(100 - $beforeArtist).'%';
        $nowLabel = 'You '.$nowArtist.'% · Studio '.(100 - $nowArtist).'%';

        $appliesFrom = $this->appliesFrom?->copy() ?? now();
        $appliesLabel = 'Bookings made from '.$appliesFrom->format('d/m/Y');

        if ($this->accepted) {
            $introText = $studioName.' accepted the split you asked for.';
        } else {
            $introText = 'Your split stays '.$beforeLabel.'.';
        }

        return [
            'accepted' => $this->accepted,
            'studioName' => $studioName,
            'studioInitials' => $this->initials($studioName),
            'studioAvatarUrl' => $this->studioAvatarUrl($studio),
            'studioMeta' => $this->studioMeta($studio),
            'artistFirstName' => $this->artistFirstName($artist),
            'introText' => $introText,
            'beforeLabel' => $beforeLabel,
            'nowLabel' => $nowLabel,
            'appliesLabel' => $appliesLabel,
            'actionUrl' => route('settings.payment'),
            'ctaLabel' => 'Open Money > Payouts',
        ];
    }

    private function artistFirstName(?User $artist): string
    {
        $first = trim((string) ($artist?->first_name ?? ''));
        if ($first !== '') {
            return $first;
        }

        $detailName = trim((string) ($artist?->userDetail?->publicDisplayName() ?? ''));
        if ($detailName !== '') {
            return trim((string) strtok($detailName, ' ')) ?: $detailName;
        }

        return 'there';
    }

    private function studioMeta(?Studio $studio): string
    {
        if (! $studio) {
            return '';
        }

        $street = trim(trim((string) ($studio->street_number ?? '')).' '.trim((string) ($studio->street_name ?? '')));
        if ($street === '') {
            $street = trim((string) ($studio->address ?? ''));
        }
        $city = trim((string) ($studio->city ?? ''));

        if ($street !== '' && $city !== '') {
            return $street.', '.$city;
        }

        return $street !== '' ? $street : $city;
    }

    private function studioAvatarUrl(?Studio $studio): ?string
    {
        if (! $studio) {
            return null;
        }

        $path = trim((string) ($studio->logo_url ?: $studio->image_url ?: ''));
        if ($path === '') {
            return null;
        }

        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : asset(ltrim($path, '/'));
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : 'ST';
    }
}
