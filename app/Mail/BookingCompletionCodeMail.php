<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCompletionCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Booking Completion Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-completion-code',
            with: [
                'booking' => $this->booking,
                'code' => (string) $this->booking->completion_code,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

