<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\ChatChannel;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessageFromArtistMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ChatChannel $channel,
        public User $client,
        public User $artist,
        public string $messageText,
        public Carbon $messageAt,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();

        return new Envelope(
            subject: 'New message from '.($data['artistName'] ?? 'your artist'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-message-from-artist',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $this->channel->loadMissing(['booking.tattoo', 'bookingRequest.tattoo', 'customRequest', 'artist.userDetail']);
        $this->artist->loadMissing('userDetail');

        $userDetail = $this->artist->userDetail ?? new UserDetail;
        $artistName = trim((string) $userDetail->publicDisplayName()) ?: 'your artist';
        $artistFirst = trim((string) ($this->artist->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $clientFirst = trim((string) ($this->client->first_name ?? ''));
        if ($clientFirst === '') {
            $clientFirst = 'there';
        }

        $about = $this->aboutLabel();
        $timeLabel = $this->messageTimeLabel();

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistInitials' => $this->initials($artistName),
            'artistAvatarUrl' => $this->avatarUrl($userDetail),
            'artistMeta' => $about !== '' ? 'About: '.$about : '',
            'introText' => $artistFirst.' sent you a message.',
            'quoteMessage' => $this->messageText,
            'quoteMeta' => $artistName.' · '.$timeLabel,
            'readUrl' => route('user.chat.index', array_filter([
                'artist' => (int) $this->artist->id,
                'booking' => $this->channel->booking_id ? (int) $this->channel->booking_id : null,
                'custom_request' => $this->channel->custom_request_id ? (int) $this->channel->custom_request_id : null,
                'booking_request' => $this->channel->booking_request_id ? (int) $this->channel->booking_request_id : null,
            ])),
            'replyNote' => 'Reply in Bookpay so '.$artistFirst.' gets it. Replies to this email aren’t read.',
        ];
    }

    private function aboutLabel(): string
    {
        $booking = $this->channel->booking;
        if ($booking instanceof Booking) {
            $title = trim((string) $booking->displayTitle());
            $timezone = trim((string) ($booking->timezone ?: 'UTC')) ?: 'UTC';
            $start = $booking->sessionStartUtc()?->copy()->timezone($timezone);
            $when = $start ? $start->format('D d/m/Y') : '';

            return trim($title.($title !== '' && $when !== '' ? ' · ' : '').$when);
        }

        if ($this->channel->customRequest) {
            $label = trim((string) $this->channel->customRequest->referenceLabel());

            return $label !== '' ? $label : 'your custom request';
        }

        if ($this->channel->bookingRequest) {
            $title = trim((string) ($this->channel->bookingRequest->tattoo?->title ?? ''));
            $ref = trim((string) $this->channel->bookingRequest->referenceLabel());

            return $title !== '' ? $title : ($ref !== '' ? $ref : 'your request');
        }

        return '';
    }

    private function messageTimeLabel(): string
    {
        $at = $this->messageAt->copy()->timezone(config('app.timezone'));
        $today = now()->timezone(config('app.timezone'))->startOfDay();
        $day = $at->copy()->startOfDay();

        if ($day->equalTo($today)) {
            return 'today, '.$at->format('H:i');
        }

        if ($day->equalTo($today->copy()->subDay())) {
            return 'yesterday, '.$at->format('H:i');
        }

        return $at->format('D d/m, H:i');
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
