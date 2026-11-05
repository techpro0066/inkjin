<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TattooAftercareMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{key: string, title: string, items: list<string>}>  $sections
     * @param  array<string, array{title: string, body: string}>  $textBlocks
     */
    public function __construct(
        public Booking $booking,
        public User $client,
        public ?UserDetail $artistDetail,
        public array $sections,
        public array $textBlocks,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Caring for your new tattoo');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tattoo-aftercare',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $booking = $this->booking;
        $booking->loadMissing(['artist.userDetail', 'tattoo']);

        $userDetail = $this->artistDetail ?? $booking->artist?->userDetail ?? new UserDetail;
        if ($this->artistDetail && $booking->artist) {
            $userDetail->setRelation('user', $booking->artist);
        }

        $artistName = $this->artistName($userDetail);
        $artistFirst = trim((string) ($booking->artist?->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $clientFirst = trim((string) ($this->client->first_name ?? ''));
        if ($clientFirst === '') {
            $clientFirst = 'there';
        }

        $design = trim((string) $booking->displayTitle());
        if ($design === '') {
            $design = 'your tattoo';
        }

        $timezone = trim((string) ($booking->timezone ?: $userDetail->timezone ?: 'UTC')) ?: 'UTC';
        $startLocal = $booking->sessionStartUtc()?->copy()->timezone($timezone);
        $dateLabel = $startLocal
            ? $startLocal->format('d/m')
            : $this->fallbackDateLabel($booking);

        $bannerMeta = 'From '.$artistName.' · '.$design.($dateLabel !== '' ? ', '.$dateLabel : '');

        $helpBlock = null;
        $otherBlocks = [];
        foreach ($this->textBlocks as $key => $block) {
            $body = trim((string) ($block['body'] ?? ''));
            if ($body === '') {
                continue;
            }
            if ((string) $key === 'when_to_get_help') {
                $helpBlock = [
                    'title' => trim((string) ($block['title'] ?? 'When to get help')),
                    'body' => $body,
                ];
                continue;
            }
            $otherBlocks[] = [
                'title' => trim((string) ($block['title'] ?? '')),
                'body' => $body,
            ];
        }

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'introText' => 'Thanks for coming in. Here’s how to look after your new tattoo.',
            'bannerTitle' => 'Caring for your new tattoo',
            'bannerMeta' => $bannerMeta,
            'sections' => $this->sections,
            'otherBlocks' => $otherBlocks,
            'helpBlock' => $helpBlock,
            'messageUrl' => route('user.chat.index', [
                'artist' => (int) $booking->artist_user_id,
                'booking' => (int) $booking->id,
            ]),
        ];
    }

    private function artistName(UserDetail $userDetail): string
    {
        $name = trim((string) $userDetail->publicDisplayName());
        if ($name !== '') {
            return $name;
        }

        $user = $userDetail->user ?? $this->booking->artist;
        if ($user) {
            $fallback = trim(trim((string) ($user->first_name ?? '')).' '.trim((string) ($user->last_name ?? '')));
            if ($fallback !== '') {
                return $fallback;
            }
        }

        return 'your artist';
    }

    private function fallbackDateLabel(Booking $booking): string
    {
        if (! $booking->booking_date) {
            return '';
        }

        try {
            $date = $booking->booking_date instanceof Carbon
                ? $booking->booking_date
                : Carbon::parse((string) $booking->booking_date);

            return $date->format('d/m');
        } catch (\Throwable) {
            return '';
        }
    }
}
