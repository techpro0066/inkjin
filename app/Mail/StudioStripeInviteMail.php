<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class StudioStripeInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $studioName;
    public $artistName;
    public $userDetailId;
    public $connectLink;

    /**
     * Create a new message instance.
     */
    public function __construct($studioName, $artistName, $userDetailId)
    {
        $this->studioName = $studioName;
        $this->artistName = $artistName;
        $this->userDetailId = $userDetailId;
        
        // Generate a signed URL for studio to connect Stripe
        // The link expires in 30 days for security
        $this->connectLink = URL::signedRoute('studio.stripe.connect', [
            'user_detail_id' => $userDetailId,
        ], now()->addDays(30));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Connect Your Stripe Account - Inkjin',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.studio-stripe-invite',
            with: [
                'studioName' => $this->studioName,
                'artistName' => $this->artistName,
                'connectLink' => $this->connectLink,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
