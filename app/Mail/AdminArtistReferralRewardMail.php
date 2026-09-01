<?php

namespace App\Mail;

use App\Models\ArtistReferral;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminArtistReferralRewardMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ArtistReferral $referral,
        public Booking $booking,
        public string $qualifyReason,
        public string $referralsUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Artist referral reward ready to pay',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-artist-referral-reward',
        );
    }
}
