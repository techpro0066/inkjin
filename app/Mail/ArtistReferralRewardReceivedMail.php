<?php

namespace App\Mail;

use App\Models\ArtistReferral;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistReferralRewardReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ArtistReferral $referral,
        public string $referEarnUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You received your referral reward!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.artist-referral-reward-received',
        );
    }
}
