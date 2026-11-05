<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class BookingCancellationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    public $isArtistEmail;

    public $cancellationType;

    public $isNoShow;

    public function __construct($booking, $isArtistEmail = false, $cancellationType = 'client', $isNoShow = false)
    {
        $this->booking = $booking;
        $this->isArtistEmail = $isArtistEmail;
        $this->cancellationType = $cancellationType;
        $this->isNoShow = $isNoShow;
    }

    public function envelope(): Envelope
    {
        if ($this->isArtistEmail) {
            $title = $this->booking->displayTitle();
            $subject = $this->isNoShow
                ? 'Booking Marked as No-Show - '.$title
                : ($this->cancellationType === 'artist'
                    ? 'Booking Cancelled by Artist - '.$title
                    : 'Booking Cancelled - '.$title);

            return new Envelope(subject: $subject);
        }

        $data = $this->clientEmailData();
        $artist = (string) ($data['artistName'] ?? 'your artist');

        if ($this->isNoShow) {
            return new Envelope(subject: 'Your booking with '.$artist.' was marked as a no-show');
        }

        return new Envelope(subject: 'Your booking with '.$artist.' was cancelled');
    }

    public function content(): Content
    {
        $view = $this->isArtistEmail
            ? 'emails.booking-cancellation-artist'
            : 'emails.booking-cancellation-user';

        return new Content(
            view: $view,
            with: $this->isArtistEmail ? $this->artistEmailData() : $this->clientEmailData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function artistEmailData(): array
    {
        $booking = $this->booking;
        $artist = $booking->artist;
        $customer = $booking->user;

        $bookingDate = Carbon::parse($booking->booking_date)->format('l, F j, Y');
        try {
            $startTime = Carbon::createFromFormat('H:i:s', (string) $booking->start_time_utc)
                ->setTimezone($booking->timezone ?? 'UTC')
                ->format('g:i A');
            $endTime = Carbon::createFromFormat('H:i:s', (string) $booking->end_time_utc)
                ->setTimezone($booking->timezone ?? 'UTC')
                ->format('g:i A');
            $bookingTime = $startTime.' - '.$endTime;
        } catch (\Throwable) {
            $bookingTime = '';
        }

        $symbol = $this->currencySymbol((string) ($booking->currency ?? 'EUR'));

        return [
            'bookingId' => $booking->id,
            'tattooTitle' => $booking->displayTitle(),
            'artistName' => $artist->name ?? 'Artist',
            'customerName' => $customer->name ?? 'Customer',
            'customerEmail' => $customer->email ?? '',
            'bookingDate' => $bookingDate,
            'bookingTime' => $bookingTime,
            'cancellationReason' => $booking->cancellation_reason,
            'cancellationType' => $this->cancellationType,
            'isNoShow' => $this->isNoShow,
            'cancelledAt' => $booking->cancelled_at ? $booking->cancelled_at->format('l, F j, Y g:i A') : '',
            'refundAmount' => $booking->refund_amount ?? 0,
            'depositForfeited' => $booking->deposit_forfeited ?? 0,
            'totalAmountPaid' => $booking->total_amount_paid ?? 0,
            'currencySymbol' => $symbol,
            'hasRefund' => ((float) ($booking->refund_amount ?? 0)) > 0,
            'isPartialRefund' => $booking->refund_status === 'partial',
            'isFullRefund' => ((float) ($booking->refund_amount ?? 0)) > 0
                && (float) ($booking->refund_amount ?? 0) == (float) ($booking->total_amount_paid ?? 0),
            'refundStatus' => $booking->refund_status ?? 'completed',
            'refundReason' => $booking->refund_reason ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function clientEmailData(): array
    {
        $booking = $this->booking;
        $booking->loadMissing(['artist.userDetail', 'user', 'studio', 'tattoo']);

        $artist = $booking->artist;
        $customer = $booking->user;
        $userDetail = $artist?->userDetail;

        $artistName = $userDetail?->publicDisplayName()
            ?: trim(ucfirst((string) ($artist->first_name ?? '')).' '.ucfirst((string) ($artist->last_name ?? '')));
        if ($artistName === '') {
            $artistName = 'your artist';
        }

        $artistFirst = trim((string) ($artist->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $clientFirst = trim((string) ($customer->first_name ?? ''));
        if ($clientFirst === '') {
            $clientFirst = 'there';
        }

        $timezone = trim((string) ($booking->timezone ?: $userDetail?->timezone ?: 'UTC')) ?: 'UTC';
        $startLocal = $booking->sessionStartUtc()?->copy()->timezone($timezone);

        $sessionLabel = $startLocal
            ? $startLocal->format('D d/m/Y').', '.$startLocal->format('H:i')
            : $this->fallbackSessionLabel($booking);

        $design = trim((string) $booking->displayTitle());
        if ($design === '') {
            $design = 'your session';
        }

        $symbol = $this->currencySymbol((string) ($booking->currency ?? 'EUR'));
        $refundAmount = max(0, round((float) ($booking->refund_amount ?? 0), 2));
        $hasRefund = $refundAmount > 0;
        $cardLabel = $this->resolveCardLabel($booking);

        $reason = trim((string) ($booking->cancellation_reason ?? ''));

        if ($this->isNoShow) {
            $introText = 'Your booking with '.$artistFirst.' was marked as a no-show.';
        } elseif ($this->cancellationType === 'artist') {
            $introText = $artistFirst.' cancelled your booking.';
        } else {
            $introText = 'Your booking with '.$artistFirst.' was cancelled.';
        }

        $bookAgainUrl = $this->artistPublicUrl($userDetail);

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistInitials' => $this->initials($artistName),
            'artistAvatarUrl' => $this->avatarUrl($userDetail),
            'artistMeta' => trim($design.($sessionLabel !== '' ? ' · was '.$sessionLabel : '')),
            'introText' => $introText,
            'quoteMessage' => $reason,
            'bookingReference' => $booking->referenceLabel(),
            'hasRefund' => $hasRefund,
            'refundLabel' => $symbol.number_format($refundAmount, 2, '.', ''),
            'cardLabel' => $cardLabel,
            'refundNote' => $hasRefund
                ? 'Refunds take 5 to 10 working days to show on your card. The booking fee is refunded too.'
                : 'No refund applies for this cancellation.',
            'bookAgainUrl' => $bookAgainUrl,
            'bookAgainLabel' => 'Book with '.$artistFirst.' again',
            'cancellationType' => $this->cancellationType,
            'isNoShow' => $this->isNoShow,
        ];
    }

    private function artistPublicUrl($userDetail): string
    {
        $username = trim((string) ($userDetail?->user_name ?? ''));
        if ($username === '') {
            return route('user.bookings.index');
        }

        return route('public.artist', ['username' => ltrim($username, '@')]);
    }

    private function avatarUrl($userDetail): ?string
    {
        if (! $userDetail) {
            return null;
        }

        $avatarPath = trim((string) ($userDetail->avatar ?? ''));
        if ($avatarPath === '') {
            return null;
        }

        return str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')
            ? $avatarPath
            : asset(ltrim($avatarPath, '/'));
    }

    private function fallbackSessionLabel($booking): string
    {
        if (! $booking->booking_date) {
            return '';
        }

        try {
            $date = $booking->booking_date instanceof Carbon
                ? $booking->booking_date
                : Carbon::parse((string) $booking->booking_date);

            return $date->format('D d/m/Y');
        } catch (\Throwable) {
            return (string) $booking->booking_date;
        }
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

    private function resolveCardLabel($booking): string
    {
        $intentId = trim((string) ($booking->payment_intent_id ?? ''));
        if ($intentId === '' || ! str_starts_with($intentId, 'pi_')) {
            return '';
        }

        $secret = (string) (config('services.stripe.secret') ?: env('STRIPE_SECRET'));
        if ($secret === '') {
            return '';
        }

        try {
            Stripe::setApiKey($secret);
            $intent = PaymentIntent::retrieve($intentId, [
                'expand' => ['latest_charge', 'payment_method'],
            ]);

            $brand = '';
            $last4 = '';

            $pm = $intent->payment_method ?? null;
            if (is_object($pm) && isset($pm->card)) {
                $brand = trim((string) ($pm->card->brand ?? ''));
                $last4 = trim((string) ($pm->card->last4 ?? ''));
            }

            if (($brand === '' || $last4 === '') && isset($intent->latest_charge) && is_object($intent->latest_charge)) {
                $card = $intent->latest_charge->payment_method_details->card ?? null;
                if (is_object($card)) {
                    $brand = $brand !== '' ? $brand : trim((string) ($card->brand ?? ''));
                    $last4 = $last4 !== '' ? $last4 : trim((string) ($card->last4 ?? ''));
                }
            }

            if ($last4 === '') {
                return '';
            }

            $brandLabel = $brand !== '' ? ucfirst(strtolower($brand)) : 'Card';

            return $brandLabel.' •••• '.$last4;
        } catch (\Throwable $e) {
            Log::info('Could not resolve card label for booking cancellation email', [
                'booking_id' => $booking->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    public function attachments(): array
    {
        return [];
    }
}
