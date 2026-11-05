<?php

namespace App\Mail;

use App\Models\Studio;
use App\Models\User;
use App\Models\UserStudio;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistStudioJoinRequestAnsweredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserStudio $userStudio,
        public bool $accepted,
        public ?string $declineMessage = null,
        public ?string $currentStudioName = null,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();
        $studio = (string) ($data['studioName'] ?? 'The studio');

        return new Envelope(
            subject: $this->accepted
                ? $studio.' accepted your join request'
                : $studio.' declined your join request',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.artist-studio-join-request-answered',
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

        $relationshipLabels = [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
        ];
        $relationshipKey = (string) ($link->relationship ?? 'resident');
        $relationshipLabel = $relationshipLabels[$relationshipKey] ?? 'Resident';

        $offersSplit = (bool) $link->payout && $link->revenue_split !== null;
        $artistPercent = $offersSplit ? max(0, min(100, (int) $link->revenue_split)) : null;
        $studioPercent = $artistPercent !== null ? 100 - $artistPercent : null;

        $declineMessage = trim((string) ($this->declineMessage ?? ''));
        $currentStudio = trim((string) ($this->currentStudioName ?? ''));

        if ($this->accepted) {
            $introText = $studioName.' accepted you as a '.$relationshipLabel.'.';
        } elseif ($currentStudio !== '') {
            $introText = $studioName.' declined your request. You’re still at '.$currentStudio.', and nothing changes.';
        } else {
            $introText = $studioName.' declined your request. Nothing changes on your page.';
        }

        return [
            'accepted' => $this->accepted,
            'studioName' => $studioName,
            'studioInitials' => $this->initials($studioName),
            'studioAvatarUrl' => $this->studioAvatarUrl($studio),
            'studioMeta' => $this->studioMeta($studio),
            'artistFirstName' => $this->artistFirstName($artist),
            'introText' => $introText,
            'relationshipLabel' => $relationshipLabel,
            'offersSplit' => $offersSplit,
            'splitLabel' => $artistPercent !== null
                ? 'You '.$artistPercent.'% · Studio '.$studioPercent.'%'
                : null,
            'startsLabel' => 'New bookings from today',
            'okText' => 'Your page now shows '.$studioName.'.',
            'declineMessage' => $declineMessage !== '' ? $declineMessage : null,
            'actionUrl' => route('settings.studio'),
            'ctaLabel' => 'Open Account > Studio',
            'notificationsUrl' => null,
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
