<?php

namespace App\Mail;

use App\Models\CustomRequest;
use App\Models\UserDetail;
use App\Support\RequestReceivedEmailPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomRequestSubmittedUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomRequest $customRequest,
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
            with: RequestReceivedEmailPresenter::forCustomRequest(
                $this->customRequest,
                $this->userDetail,
                $this->accessUrl,
                $this->customRequest->user,
            ),
        );
    }
}
