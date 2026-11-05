<?php

namespace App\Mail;

use App\Models\UserDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudioPayoutInfoRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studioName,
        public string $artistName,
        public string $formUrl,
        public bool $showApproveDecline = false,
        public ?string $approveUrl = null,
        public ?string $declineUrl = null,
        public bool $requirementsReminder = false,
        public string $relationshipPhrase = 'studio',
        public ?UserDetail $artistDetail = null,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();
        $artist = (string) ($data['artistName'] ?? $this->artistName);

        $subject = match (true) {
            $this->requirementsReminder => 'Complete Stripe requirements — '.$artist.' on Bookpay',
            default => $artist.' invited you to Bookpay',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.studio-payout-info-request',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $detail = $this->artistDetail;
        if ($detail) {
            $detail->loadMissing('user');
        }

        $artistName = $this->resolveArtistName($detail);
        $handle = trim((string) ($detail?->user_name ?? ''));
        if ($handle !== '' && ! str_starts_with($handle, '@')) {
            $handle = '@'.$handle;
        }

        $city = trim((string) ($detail?->city ?? ''));
        if ($city === '') {
            $studioLine = trim((string) ($detail?->studioNameWithCityCountry() ?? ''));
            if ($studioLine !== '' && str_contains($studioLine, ',')) {
                $parts = array_map('trim', explode(',', $studioLine));
                $city = (string) end($parts);
            }
        }

        $meta = trim(($handle !== '' ? $handle : '').($handle !== '' && $city !== '' ? ' · ' : '').$city);

        $avatarUrl = null;
        $avatarPath = trim((string) ($detail?->avatar ?? ''));
        if ($avatarPath !== '') {
            $avatarUrl = str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')
                ? $avatarPath
                : asset(ltrim($avatarPath, '/'));
        }

        $studioName = trim($this->studioName) !== '' ? trim($this->studioName) : 'Studio';
        $ctaUrl = $this->requirementsReminder
            ? $this->formUrl
            : ($this->approveUrl ?: $this->formUrl);

        return [
            'studioName' => $studioName,
            'artistName' => $artistName,
            'artistInitials' => $detail?->publicDisplayInitials() ?: $this->initials($artistName),
            'artistAvatarUrl' => $avatarUrl,
            'artistMeta' => $meta,
            'relationshipPhrase' => $this->relationshipPhrase !== '' ? $this->relationshipPhrase : 'studio',
            'requirementsReminder' => $this->requirementsReminder,
            'formUrl' => $this->formUrl,
            'approveUrl' => $this->approveUrl,
            'declineUrl' => $this->declineUrl,
            'ctaUrl' => $ctaUrl,
            'ctaLabel' => $this->requirementsReminder
                ? 'Complete required information'
                : 'See the invitation',
            'introText' => $this->requirementsReminder
                ? $artistName.' needs your studio to complete additional Stripe information for payouts on Bookpay.'
                : $artistName.' invited your studio to Bookpay as their '.$this->relationshipPhrase.'.',
            'supportText' => $this->requirementsReminder
                ? 'Please open the secure link below and submit the required details.'
                : 'Your share of each booking is paid to you automatically. Free for studios.',
        ];
    }

    private function resolveArtistName(?UserDetail $detail): string
    {
        if ($detail) {
            $name = trim((string) $detail->publicDisplayName());
            if ($name !== '' && $name !== 'Artist') {
                return $name;
            }
        }

        $fallback = trim($this->artistName);

        return $fallback !== '' ? $fallback : 'An artist';
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : 'AR';
    }
}
