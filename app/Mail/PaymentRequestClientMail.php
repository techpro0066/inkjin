<?php

namespace App\Mail;

use App\Models\BalanceCollection;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRequestClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BalanceCollection $collection,
        public Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();
        $artist = (string) ($data['artistName'] ?? 'your artist');
        $amount = (string) ($data['payNowLabel'] ?? '');
        $subject = $amount !== ''
            ? $artist.' asked for the balance: '.$amount
            : $artist.' asked for the balance';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-request-client',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $booking = $this->booking;
        $booking->loadMissing(['artist.userDetail', 'user', 'studio']);

        $artist = $booking->artist;
        $customer = $booking->user;
        $userDetail = $artist?->userDetail ?? new UserDetail;

        $artistName = $this->artistName($userDetail, $artist);
        $artistFirst = trim((string) ($artist?->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $clientFirst = trim((string) ($customer?->first_name ?? ''));
        if ($clientFirst === '') {
            $clientFirst = 'there';
        }

        $timezone = trim((string) ($booking->timezone ?: $userDetail->timezone ?: 'UTC')) ?: 'UTC';
        $startLocal = $booking->sessionStartUtc()?->copy()->timezone($timezone);

        $sessionLabel = $startLocal
            ? $startLocal->format('D d/m/Y').', '.$startLocal->format('H:i')
            : $this->fallbackSessionLabel($booking);

        $currency = strtoupper((string) ($this->collection->currency ?: $booking->currency ?: 'EUR'));
        $symbol = $this->currencySymbol($currency);

        $alreadyPaid = max(0, round((float) ($booking->deposit_amount ?? 0), 2));
        $payNow = max(0, round((float) ($this->collection->amount ?? 0), 2));

        $design = trim((string) $booking->displayTitle());
        if ($design === '') {
            $design = 'tattoo';
        }

        $payUrl = trim((string) ($this->collection->payment_link_url ?? ''));
        if ($payUrl === '' && $this->collection->payment_link_code) {
            $payUrl = url('/p/'.$this->collection->payment_link_code);
        }

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistInitials' => $this->initials($artistName),
            'artistAvatarUrl' => $this->avatarUrl($userDetail),
            'artistMeta' => $this->artistMeta($userDetail, $booking),
            'introText' => $artistFirst.' asked you to pay the balance for your '.$design.'.',
            'bookingReference' => $booking->referenceLabel(),
            'sessionLabel' => $sessionLabel,
            'alreadyPaidLabel' => $this->moneyLabel($alreadyPaid, $symbol),
            'payNowLabel' => $this->moneyLabel($payNow, $symbol),
            'payUrl' => $payUrl,
            'validityNote' => 'The link works for 7 days. You pay securely with Stripe.',
        ];
    }

    private function artistName(UserDetail $userDetail, ?User $artist): string
    {
        $name = trim((string) $userDetail->publicDisplayName());
        if ($name !== '') {
            return $name;
        }

        $fallback = trim(ucfirst((string) ($artist?->first_name ?? '')).' '.ucfirst((string) ($artist?->last_name ?? '')));

        return $fallback !== '' ? $fallback : 'your artist';
    }

    private function artistMeta(UserDetail $userDetail, Booking $booking): string
    {
        $handle = trim((string) ($userDetail->user_name ?? ''));
        if ($handle !== '' && ! str_starts_with($handle, '@')) {
            $handle = '@'.$handle;
        }

        $studio = trim((string) ($booking->studio?->name ?? ''));
        if ($studio === '') {
            $studio = trim((string) $userDetail->studioNameWithCityCountry());
        }
        if ($studio === '') {
            $studio = trim((string) $userDetail->resolvedStudioName());
        }

        return trim(($handle !== '' ? $handle : '').($handle !== '' && $studio !== '' ? ' · ' : '').$studio);
    }

    private function avatarUrl(UserDetail $userDetail): ?string
    {
        $avatarPath = trim((string) ($userDetail->avatar ?? ''));
        if ($avatarPath === '') {
            return null;
        }

        return str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')
            ? $avatarPath
            : asset(ltrim($avatarPath, '/'));
    }

    private function fallbackSessionLabel(Booking $booking): string
    {
        try {
            $date = $booking->booking_date instanceof Carbon
                ? $booking->booking_date
                : Carbon::parse((string) $booking->booking_date);

            return $date->format('D d/m/Y');
        } catch (\Throwable) {
            return (string) ($booking->booking_date ?? '');
        }
    }

    private function moneyLabel(float $amount, string $symbol): string
    {
        return $symbol.number_format($amount, 2, '.', '');
    }

    private function currencySymbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'AED ',
            'SAR' => 'SAR ',
            'INR' => '₹',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
            default => strtoupper($currency).' ',
        };
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : '?';
    }
}
