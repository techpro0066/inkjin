<?php

namespace App\Mail;

use App\Models\Studio;
use App\Models\StudioInvite;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudioArtistInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StudioInvite $invite,
        public bool $isResend = false,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();
        $studio = (string) ($data['studioName'] ?? 'A studio');
        $prefix = $this->isResend ? 'Reminder: ' : '';

        return new Envelope(
            subject: $prefix.$studio.' invited you to join',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.studio-artist-invite',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $invite = $this->invite;
        $invite->loadMissing(['studio', 'user']);

        $studio = $invite->studio;
        $studioName = trim((string) ($studio?->name ?? 'a studio'));
        if ($studioName === '') {
            $studioName = 'a studio';
        }

        $relationshipLabels = [
            'co_owner' => 'co-owner',
            'resident' => 'resident',
            'collective_member' => 'collective member',
            'apprentice' => 'apprentice',
            'other' => 'team member',
        ];
        $relationshipKey = (string) ($invite->relationship ?? 'resident');
        $relationshipLabel = $relationshipLabels[$relationshipKey] ?? 'resident';

        $offersSplit = (bool) $invite->payout && $invite->revenue_split !== null;
        $hasBookpayAccount = (bool) $invite->user_id;

        return [
            'studioName' => $studioName,
            'studioInitials' => $this->initials($studioName),
            'studioAvatarUrl' => $this->studioAvatarUrl($studio),
            'studioMeta' => $this->studioMeta($studio),
            'artistFirstName' => $this->artistFirstName($invite),
            'relationshipLabel' => $relationshipLabel,
            'offersSplit' => $offersSplit,
            'hasBookpayAccount' => $hasBookpayAccount,
            'actionUrl' => $invite->acceptUrl(),
            'ctaLabel' => 'See the offer',
        ];
    }

    private function artistFirstName(StudioInvite $invite): string
    {
        /** @var User|null $user */
        $user = $invite->user;
        $first = trim((string) ($user?->first_name ?? ''));
        if ($first !== '') {
            return $first;
        }

        $email = strtolower(trim((string) ($invite->email ?? '')));
        if ($email !== '' && str_contains($email, '@')) {
            $local = trim((string) strtok($email, '@'));
            $local = preg_replace('/[._+\-]+/', ' ', $local) ?: $local;
            $token = trim((string) strtok($local, ' '));
            if ($token !== '') {
                return ucfirst(strtolower($token));
            }
        }

        return 'there';
    }

    private function studioMeta(?Studio $studio): string
    {
        $city = trim((string) ($studio?->city ?? ''));

        return $city !== '' ? 'Tattoo studio · '.$city : 'Tattoo studio';
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
