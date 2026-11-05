<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserStudio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistRevenueSplitRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserStudio $userStudio,
        public int $currentArtistPercent,
        public int $proposedArtistPercent,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();

        return new Envelope(
            subject: ($data['artistName'] ?? 'An artist').' asked for a new split',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.artist-revenue-split-request',
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
        $detail = $artist?->userDetail;

        $studioName = trim((string) ($studio?->name ?? $link->studio_name ?? 'Studio'));
        if ($studioName === '') {
            $studioName = 'Studio';
        }

        $artistName = $this->resolveArtistName($artist, $detail);
        $artistShort = $this->artistShortName($artist, $artistName);

        $currentArtist = max(0, min(100, $this->currentArtistPercent));
        $proposedArtist = max(0, min(100, $this->proposedArtistPercent));

        $nowLabel = $artistShort.' '.$currentArtist.'% · Studio '.(100 - $currentArtist).'%';
        $askedLabel = $artistShort.' '.$proposedArtist.'% · Studio '.(100 - $proposedArtist).'%';

        return [
            'studioName' => $studioName,
            'artistName' => $artistName,
            'artistShortName' => $artistShort,
            'artistInitials' => $detail?->publicDisplayInitials() ?: $this->initials($artistName),
            'artistAvatarUrl' => $this->artistAvatarUrl($detail),
            'artistMeta' => $this->artistMeta($link),
            'introText' => $artistShort.' asked for a new split.',
            'nowLabel' => $nowLabel,
            'askedLabel' => $askedLabel,
            'appliesLabel' => 'New bookings, once you accept',
            'actionUrl' => route('studio.artists'),
            'ctaLabel' => 'Review',
        ];
    }

    private function resolveArtistName(?User $artist, ?UserDetail $detail): string
    {
        if ($detail) {
            $name = trim((string) $detail->publicDisplayName());
            if ($name !== '' && $name !== 'Artist') {
                return $name;
            }
        }

        $fallback = trim(($artist?->first_name ?? '').' '.($artist?->last_name ?? ''));
        if ($fallback !== '') {
            return $fallback;
        }

        return trim((string) ($artist?->user_name ?? $artist?->email ?? 'Artist')) ?: 'Artist';
    }

    private function artistShortName(?User $artist, string $artistName): string
    {
        $first = trim((string) ($artist?->first_name ?? ''));
        if ($first !== '') {
            return $first;
        }

        $token = trim((string) strtok($artistName, ' '));

        return $token !== '' ? $token : $artistName;
    }

    private function artistMeta(UserStudio $link): string
    {
        $labels = [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
        ];
        $key = (string) ($link->relationship ?? 'resident');
        $label = $labels[$key] ?? 'Resident';

        $since = $link->created_at;
        if ($since) {
            return $label.' since '.$since->format('d/m/Y');
        }

        return $label;
    }

    private function artistAvatarUrl(?UserDetail $detail): ?string
    {
        $path = trim((string) ($detail?->avatar ?? ''));
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

        return $initials !== '' ? $initials : 'AR';
    }
}
