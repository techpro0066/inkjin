<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserDetail;
use App\Support\AppointmentReminderEmailPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminder72hMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public User $client,
        public ?UserDetail $artistDetail,
        public ?string $consentUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: (string) ($this->viewData()['subject'] ?? 'Your session is coming up'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-reminder',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        return AppointmentReminderEmailPresenter::forBooking(
            $this->booking,
            $this->client,
            $this->artistDetail,
            '72h',
            $this->consentUrl,
        );
    }
}
