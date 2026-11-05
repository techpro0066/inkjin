<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArtistPayoutSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $artist,
        public Booking $booking,
        public float $amount,
        public string $currency = 'EUR',
        public ?string $bankLast4 = null,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();

        return new Envelope(
            subject: $data['amountLabel'].' is on its way to your bank',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.artist-payout-sent',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $this->booking->loadMissing(['tattoo']);

        $amount = max(0, round($this->amount, 2));
        $symbol = $this->currencySymbol($this->currency);
        $amountLabel = $symbol.number_format($amount, 2, '.', '');

        $bankLabel = 'Bank account';
        $last4 = trim((string) ($this->bankLast4 ?? ''));
        if ($last4 !== '') {
            $bankLabel .= ' ••• '.$last4;
        }

        $reference = $this->booking->referenceLabel();
        $title = trim((string) $this->booking->displayTitle());
        $bookingsLabel = $reference;
        if ($title !== '') {
            $bookingsLabel .= ' · '.$title;
        }
        $bookingsLabel .= ' (your share after fees)';

        return [
            'artistFirstName' => $this->artistFirstName(),
            'amountLabel' => $amountLabel,
            'bankLabel' => $bankLabel,
            'arrivesLabel' => '1 to 2 working days',
            'bookingsLabel' => $bookingsLabel,
            'moneyUrl' => route('artist.payments.index'),
        ];
    }

    private function artistFirstName(): string
    {
        $first = trim((string) ($this->artist->first_name ?? ''));
        if ($first !== '') {
            return $first;
        }

        $detailName = trim((string) ($this->artist->userDetail?->publicDisplayName() ?? ''));
        if ($detailName !== '') {
            return trim((string) strtok($detailName, ' ')) ?: $detailName;
        }

        return 'there';
    }

    private function currencySymbol(string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'AED ',
            'SAR' => 'SAR ',
            'INR' => '₹',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];

        $code = strtoupper(trim($currency));

        return $symbols[$code] ?? ($code !== '' ? $code.' ' : '€');
    }
}
