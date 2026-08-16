<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\PaymentLink;
use App\Models\QuestionSorting;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PaymentLinkSessionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public User $client,
        public UserDetail $userDetail,
        public PaymentLink $paymentLink,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your session with '.$this->artistName().' is '.$this->dayName().' at '.$this->sessionTime(),
        );
    }

    public function content(): Content
    {
        $intakeCount = $this->missingIntakeCount();

        return new Content(
            view: 'emails.payment-link-session-reminder',
            with: [
                'artistName' => $this->artistName(),
                'clientFirst' => $this->clientFirstName(),
                'title' => $this->sessionTitle(),
                'dayName' => $this->dayName(),
                'sessionDate' => $this->sessionDate(),
                'sessionTime' => $this->sessionTime(),
                'locationLine' => $this->locationLine(),
                'dueAmount' => $this->dueAmount(),
                'placement' => $this->placementLabel(),
                'viewBookingUrl' => $this->viewBookingUrl(),
                'intakeIncomplete' => $intakeCount > 0,
                'intakeCount' => $intakeCount,
                'sessionDetailsUrl' => $intakeCount > 0 ? $this->sessionDetailsUrl() : null,
            ],
        );
    }

    private function artistName(): string
    {
        $name = trim((string) $this->userDetail->publicDisplayName());

        return $name !== '' ? $name : 'your artist';
    }

    private function clientFirstName(): string
    {
        $fromPayer = trim((string) strtok((string) ($this->paymentLink->payer_name ?? ''), ' '));
        if ($fromPayer !== '') {
            return $fromPayer;
        }

        return trim((string) ($this->client->first_name ?? '')) ?: 'there';
    }

    private function sessionTitle(): string
    {
        $title = trim((string) ($this->paymentLink->title ?? ''));

        return $title !== '' ? $title : $this->booking->displayTitle();
    }

    private function sessionStartLocal(): ?\Carbon\Carbon
    {
        return $this->booking->sessionStartLocal();
    }

    private function dayName(): string
    {
        $start = $this->sessionStartLocal();

        return $start ? $start->format('l') : 'soon';
    }

    private function sessionDate(): string
    {
        $start = $this->sessionStartLocal();
        if ($start) {
            return $start->format('D j M');
        }

        return (string) $this->booking->booking_date;
    }

    private function sessionTime(): string
    {
        $start = $this->sessionStartLocal();

        return $start ? $start->format('H:i') : '';
    }

    private function locationLine(): string
    {
        $studio = trim((string) ($this->userDetail->studio_name ?? ''));
        $lines = $this->userDetail->studioLocationLines();
        if ($studio !== '' && ($lines[0] ?? null) === $studio) {
            array_shift($lines);
        }
        $address = implode(', ', $lines);

        return implode(', ', array_values(array_filter([$studio, $address], fn (string $part) => $part !== '')));
    }

    private function dueAmount(): string
    {
        if ($this->paymentLink->payment_type !== 'deposit') {
            return $this->formatEuro(0);
        }

        return $this->formatEuro((float) $this->paymentLink->due_amount);
    }

    private function placementLabel(): string
    {
        $answers = is_array($this->booking->questions_answers) ? $this->booking->questions_answers : [];
        foreach ($answers as $item) {
            if (! is_array($item)) {
                continue;
            }
            $type = strtolower((string) ($item['type'] ?? ''));
            $label = strtolower((string) ($item['question'] ?? ''));
            $isPlacement = $type === 'placement'
                || str_contains($label, 'placement')
                || str_contains($label, 'body part')
                || str_contains($label, 'where');
            if (! $isPlacement) {
                continue;
            }
            $value = $item['answer'] ?? null;
            if (is_array($value)) {
                $value = implode(', ', array_filter(array_map(static fn ($part) => trim((string) $part), $value)));
            }
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return 'placement';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function artistQuestions(): array
    {
        return QuestionSorting::activeQuestionsPayloadForArtist((int) $this->userDetail->user_id, 'default');
    }

    private function missingIntakeCount(): int
    {
        $questions = $this->artistQuestions();
        if ($questions === []) {
            return 0;
        }

        $answers = is_array($this->booking->questions_answers) ? $this->booking->questions_answers : [];
        if ($answers !== []) {
            return 0;
        }

        return count($questions);
    }

    private function sessionDetailsUrl(): string
    {
        return URL::temporarySignedRoute(
            'public.payment-link.session-details',
            $this->paymentLink->sessionDetailsExpiresAt(),
            [
                'code' => $this->paymentLink->code,
                'booking' => $this->booking->id,
            ]
        );
    }

    private function viewBookingUrl(): string
    {
        return URL::temporarySignedRoute(
            'user.post-booking.access',
            now()->addDays(14),
            ['user' => $this->client->id, 'booking' => $this->booking->id]
        );
    }

    private function formatEuro(float $value): string
    {
        $rounded = round($value, 2);
        if (fmod($rounded, 1.0) === 0.0) {
            return '€'.(string) (int) $rounded;
        }

        return '€'.number_format($rounded, 2, '.', '');
    }
}
