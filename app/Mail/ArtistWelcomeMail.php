<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserPayout;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $dashboardUrl,
        public string $payoutsUrl,
        public string $instagramChannelUrl = 'https://www.instagram.com/channel/AbYwJKN7J0hktPIG/',
        public string $weeklyCallUrl = 'https://meet.google.com/ptq-argf-zap',
        public ?User $artist = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $data = $this->viewData();

        return new Envelope(
            subject: 'Welcome to Bookpay, '.$data['artistFirstName']
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.artist-welcome',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $artist = $this->artist;
        $detail = $artist?->userDetail;
        $firstName = trim((string) ($artist?->first_name ?? ''));
        if ($firstName === '') {
            $firstName = trim((string) ($detail?->publicDisplayName() ?? '')) ?: 'there';
        }

        $payout = $artist
            ? UserPayout::activeForUser((int) $artist->id)
            : null;
        $showPayoutStep = ! $payout || $payout->status !== UserPayout::STATUS_APPROVED;
        $showWorkingHoursStep = true;
        $showOpenBooksStep = ! $detail
            || (string) ($detail->availability_status ?? '') === 'closed';

        $remainingCount = (int) $showPayoutStep
            + (int) $showWorkingHoursStep
            + (int) $showOpenBooksStep;

        $handle = trim((string) ($detail?->user_name ?? ''));
        $publicPageUrl = $handle !== ''
            ? 'inkjin.com/@'.$handle
            : '';

        $createdAt = $artist?->created_at;
        $showFounding = $createdAt !== null
            && $createdAt->lt(\Carbon\Carbon::create(2027, 1, 1, 0, 0, 0));

        return [
            'artistFirstName' => $firstName,
            'dashboardUrl' => $this->dashboardUrl,
            'payoutsUrl' => $this->payoutsUrl,
            'instagramChannelUrl' => $this->instagramChannelUrl,
            'weeklyCallUrl' => $this->weeklyCallUrl,
            'showPayoutStep' => $showPayoutStep,
            'showWorkingHoursStep' => $showWorkingHoursStep,
            'showOpenBooksStep' => $showOpenBooksStep,
            'remainingCount' => $remainingCount,
            'remainingLabel' => match ($remainingCount) {
                1 => 'one thing',
                2 => 'two things',
                default => 'three things',
            },
            'publicPageUrl' => $publicPageUrl,
            'showFounding' => $showFounding,
        ];
    }
}
