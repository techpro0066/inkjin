<?php

namespace App\Mail;

use App\Models\PaymentLink;
use App\Models\UserDetail;
use App\Support\MoneyCurrency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentLinkExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PaymentLink $paymentLink,
        public UserDetail $userDetail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your booking link from '.$this->artistName().' expires tomorrow',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-link-expiry-reminder',
            with: [
                'artistName' => $this->artistName(),
                'title' => $this->sessionTitle(),
                'amount' => $this->paidAmount(),
                'bookUrl' => route('public.payment-link', ['code' => $this->paymentLink->code]),
            ],
        );
    }

    private function artistName(): string
    {
        $name = trim((string) $this->userDetail->publicDisplayName());

        return $name !== '' ? $name : 'your artist';
    }

    private function sessionTitle(): string
    {
        $title = trim((string) ($this->paymentLink->title ?? ''));

        return $title !== '' ? $title : 'your session';
    }

    private function paidAmount(): string
    {
        return MoneyCurrency::format((float) $this->paymentLink->amount, $this->userDetail->currency);
    }
}
