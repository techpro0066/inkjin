<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TattooAftercareMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{key: string, title: string, items: list<string>}>  $sections
     * @param  array<string, array{title: string, body: string}>  $textBlocks
     */
    public function __construct(
        public Booking $booking,
        public User $client,
        public ?UserDetail $artistDetail,
        public array $sections,
        public array $textBlocks,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your tattoo aftercare instructions',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tattoo-aftercare',
            with: [
                'clientFirst' => trim((string) ($this->client->first_name ?? '')) ?: 'there',
                'artistName' => $this->artistName(),
                'studioName' => trim((string) ($this->artistDetail?->studio_name ?? '')),
                'sections' => $this->sections,
                'textBlocks' => $this->textBlocks,
            ],
        );
    }

    private function artistName(): string
    {
        if ($this->artistDetail) {
            $name = trim((string) $this->artistDetail->publicDisplayName());
            if ($name !== '') {
                return $name;
            }
        }

        $user = $this->artistDetail?->user ?? $this->booking->artist;
        if ($user) {
            $name = trim(trim((string) ($user->first_name ?? '')).' '.trim((string) ($user->last_name ?? '')));
            if ($name !== '') {
                return $name;
            }
        }

        return 'Your artist';
    }
}
