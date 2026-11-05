<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\ChatChannel;
use App\Models\Studio;
use App\Models\User;
use App\Support\RequestReceivedEmailPresenter;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessageToArtistMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ChatChannel $channel,
        public User $artist,
        public User $sender,
        public string $messageText,
        public Carbon $messageAt,
        public bool $fromStudio = false,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();

        return new Envelope(
            subject: 'New message from '.($data['senderDisplayName'] ?? 'a client'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-message-to-artist',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $this->channel->loadMissing([
            'booking.tattoo',
            'booking.studio',
            'bookingRequest.tattoo',
            'customRequest',
            'studioUser.ownedStudio',
        ]);
        $this->sender->loadMissing('ownedStudio');
        $this->artist->loadMissing('userDetail');

        $senderDisplay = $this->senderDisplayName();
        $senderFirst = $this->senderFirstName($senderDisplay);
        $artistFirst = $this->artistFirstName();
        $about = $this->aboutLabel();
        $timeLabel = $this->messageTimeLabel();

        return [
            'artistFirstName' => $artistFirst,
            'senderDisplayName' => $senderDisplay,
            'senderInitials' => $this->initials($senderDisplay),
            'senderAvatarUrl' => $this->fromStudio ? $this->studioAvatarUrl() : null,
            'senderMeta' => $about !== '' ? 'About: '.$about : '',
            'introText' => $senderFirst.' sent you a message.',
            'quoteMessage' => $this->messageText,
            'quoteMeta' => $senderDisplay.' · '.$timeLabel,
            'inboxUrl' => $this->inboxUrl(),
        ];
    }

    private function senderDisplayName(): string
    {
        if ($this->fromStudio) {
            $studio = $this->resolveStudio();
            $name = trim((string) ($studio?->name ?? ''));
            if ($name !== '') {
                return $name;
            }

            return 'Studio';
        }

        $first = trim((string) ($this->sender->first_name ?? ''));
        $last = trim((string) ($this->sender->last_name ?? ''));
        if ($first === '') {
            return 'Client';
        }

        $display = ucfirst($first);
        if ($last !== '') {
            $display .= ' '.strtoupper(mb_substr($last, 0, 1)).'.';
        }

        return $display;
    }

    private function senderFirstName(string $displayName): string
    {
        if ($this->fromStudio) {
            return $displayName;
        }

        $first = trim((string) ($this->sender->first_name ?? ''));
        if ($first !== '') {
            return ucfirst($first);
        }

        return trim((string) strtok($displayName, ' ')) ?: $displayName;
    }

    private function artistFirstName(): string
    {
        $first = trim((string) ($this->artist->first_name ?? ''));
        if ($first !== '') {
            return $first;
        }

        $detailName = trim((string) ($this->artist->userDetail?->publicDisplayName() ?? ''));
        if ($detailName !== '') {
            return trim((string) strtok($detailName, ' ')) ?: $detailName;
        }

        return 'there';
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
            $idea = RequestReceivedEmailPresenter::ideaLabelFromAnswers($this->channel->customRequest->questions_answers);
            if ($idea !== '' && $idea !== 'your custom tattoo request') {
                return $idea;
            }
            $label = trim((string) $this->channel->customRequest->referenceLabel());

            return $label !== '' ? $label : 'custom request';
        }

        if ($this->channel->bookingRequest) {
            $title = trim((string) ($this->channel->bookingRequest->tattoo?->title ?? ''));
            $ref = trim((string) $this->channel->bookingRequest->referenceLabel());

            return $title !== '' ? $title : ($ref !== '' ? $ref : 'request');
        }

        return '';
    }

    private function inboxUrl(): string
    {
        $params = [];
        if ($this->fromStudio) {
            $params['studio'] = (int) $this->channel->studio_user_id;
        } else {
            $params['client'] = (int) $this->channel->client_user_id;
        }

        if ($this->channel->booking_id) {
            $params['booking'] = (int) $this->channel->booking_id;
        }
        if ($this->channel->custom_request_id) {
            $params['custom_request'] = (int) $this->channel->custom_request_id;
        }
        if ($this->channel->booking_request_id) {
            $params['booking_request'] = (int) $this->channel->booking_request_id;
        }

        return route('artist.chat.index', array_filter($params));
    }

    private function resolveStudio(): ?Studio
    {
        $fromBooking = $this->channel->booking?->studio;
        if ($fromBooking) {
            return $fromBooking;
        }

        $owned = $this->sender->ownedStudio;
        if ($owned instanceof Studio) {
            return $owned;
        }

        return null;
    }

    private function studioAvatarUrl(): ?string
    {
        $studio = $this->resolveStudio();
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

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : '?';
    }
}
