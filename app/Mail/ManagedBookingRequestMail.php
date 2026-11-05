<?php

namespace App\Mail;

use App\Models\BookingRequest;
use App\Models\UserDetail;
use App\Support\RequestReceivedEmailPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagedBookingRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BookingRequest $bookingRequest,
        public UserDetail $userDetail,
        public string $accessUrl,
        public bool $isNewUser = false,
    ) {}

    public function envelope(): Envelope
    {
        $artistName = trim($this->userDetail->publicDisplayName()) ?: 'your artist';

        return new Envelope(
            subject: $artistName.' got your request',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.request-received-user',
            with: RequestReceivedEmailPresenter::forBookingRequest(
                $this->bookingRequest,
                $this->userDetail,
                $this->accessUrl,
                $this->bookingRequest->user,
            ),
        );
    }
}
