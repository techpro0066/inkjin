<?php

namespace App\Mail;

use App\Services\ArtistPayoutService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    public $isArtistEmail;

    public $questions;

    public function __construct($booking, $isArtistEmail = false, $questions = [])
    {
        $this->booking = $booking;
        $this->isArtistEmail = $isArtistEmail;
        $this->questions = $questions;
    }

    public function envelope(): Envelope
    {
        if ($this->isArtistEmail) {
            $data = $this->artistEmailData();
            $client = (string) ($data['clientDisplayName'] ?? 'Client');
            $whenShort = (string) ($data['whenShort'] ?? '');
            $subject = $whenShort !== ''
                ? "New booking: {$client} · {$whenShort}"
                : "New booking: {$client}";

            return new Envelope(subject: $subject);
        }

        $data = $this->clientEmailData();
        $artist = (string) ($data['artistName'] ?? 'your artist');
        $whenShort = (string) ($data['whenShort'] ?? '');
        $subject = $whenShort !== ''
            ? "You're booked with {$artist} · {$whenShort}"
            : "You're booked with {$artist}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $view = $this->isArtistEmail
            ? 'emails.booking-confirmation-artist'
            : 'emails.booking-confirmation-user';

        return new Content(
            view: $view,
            with: $this->getEmailData(),
        );
    }

    private function getEmailData(): array
    {
        if ($this->isArtistEmail) {
            return $this->artistEmailData();
        }

        return $this->clientEmailData();
    }

    private function artistEmailData(): array
    {
        $booking = $this->booking;
        $booking->loadMissing(['artist.userDetail', 'user', 'studio']);

        $artist = $booking->artist;
        $customer = $booking->user;
        $userDetail = $artist?->userDetail;

        $artistFirst = trim((string) ($artist->first_name ?? ''));
        if ($artistFirst === '') {
            $artistDisplay = $userDetail?->publicDisplayName()
                ?: trim(ucfirst((string) ($artist->first_name ?? '')).' '.ucfirst((string) ($artist->last_name ?? '')));
            $artistFirst = trim((string) strtok((string) $artistDisplay, ' ')) ?: 'there';
        }

        $clientFirst = trim((string) ($customer->first_name ?? ''));
        $clientLast = trim((string) ($customer->last_name ?? ''));
        if ($clientFirst === '') {
            $clientFirst = 'A client';
        }

        $clientDisplayName = ucfirst($clientFirst);
        if ($clientLast !== '') {
            $clientDisplayName .= ' '.strtoupper(mb_substr($clientLast, 0, 1)).'.';
        }

        $timezone = $booking->timezone ?: ($userDetail?->timezone ?: 'UTC');
        $startLocal = $this->sessionStartLocal($booking, $timezone);
        $endLocal = $this->sessionEndLocal($booking, $timezone);

        $whenLabel = $this->formatWhenLabel($startLocal, $endLocal, $booking);
        $whenShort = $startLocal
            ? $startLocal->format('D j M, H:i')
            : '';

        $durationLabel = $this->durationLabel($booking, $startLocal, $endLocal);
        if ($durationLabel !== '' && $whenLabel !== '') {
            $whenLabel .= ' ('.$durationLabel.')';
        }

        $currencySymbol = $this->currencySymbol((string) ($booking->currency ?? 'EUR'));
        $deposit = max(0, round((float) ($booking->deposit_amount ?? 0), 2));
        $balanceDue = max(0, round((float) $booking->remainingBalanceAmount(), 2));

        return [
            'artistFirstName' => $artistFirst,
            'clientFirstName' => $clientFirst === 'A client' ? 'A client' : ucfirst($clientFirst),
            'clientDisplayName' => $clientDisplayName,
            'clientInitials' => $this->initials($clientDisplayName),
            'bookingReference' => $booking->referenceLabel(),
            'tattooTitle' => $booking->displayTitle(),
            'whenLabel' => $whenLabel,
            'whenShort' => $whenShort,
            'whereLabel' => $this->locationLine($booking, $userDetail),
            'currencySymbol' => $currencySymbol,
            'depositAmount' => $deposit,
            'paidLabel' => $currencySymbol.number_format($deposit, 2).' deposit',
            'balanceAmount' => $balanceDue,
            'balanceLabel' => $currencySymbol.number_format($balanceDue, 2).' (estimate)',
            'viewBookingUrl' => route('artist.bookings.index', ['booking' => $booking->id]),
        ];
    }

    private function clientEmailData(): array
    {
        $booking = $this->booking;
        $booking->loadMissing(['artist.userDetail', 'user', 'studio']);

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

        $timezone = $booking->timezone ?: ($userDetail?->timezone ?: 'UTC');
        $startLocal = $this->sessionStartLocal($booking, $timezone);
        $endLocal = $this->sessionEndLocal($booking, $timezone);

        $whenLabel = $this->formatWhenLabel($startLocal, $endLocal, $booking);
        $whenShort = $startLocal
            ? $startLocal->format('D j M, H:i')
            : '';

        $durationLabel = $this->durationLabel($booking, $startLocal, $endLocal);
        if ($durationLabel !== '' && $whenLabel !== '') {
            $whenLabel .= ' ('.$durationLabel.')';
        }

        $handle = trim((string) ($userDetail?->user_name ?? ''));
        if ($handle !== '' && ! str_starts_with($handle, '@')) {
            $handle = '@'.$handle;
        }

        $locationLine = $this->locationLine($booking, $userDetail);
        $whoMeta = trim(($handle !== '' ? $handle : '').($handle !== '' && $locationLine !== '' ? ' · ' : '').$this->studioShortName($booking, $userDetail, $locationLine));

        $avatarUrl = null;
        $avatarPath = trim((string) ($userDetail?->avatar ?? ''));
        if ($avatarPath !== '') {
            $avatarUrl = str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')
                ? $avatarPath
                : asset(ltrim($avatarPath, '/'));
        }

        $currencySymbol = $this->currencySymbol((string) ($booking->currency ?? 'EUR'));
        $deposit = max(0, round((float) ($booking->deposit_amount ?? 0), 2));
        $bookingFee = max(0, round((float) ($booking->platform_fee ?? 0), 2));
        $paidNow = max(0, round((float) ($booking->total_amount_paid ?? ($deposit + $bookingFee)), 2));
        $balanceDue = max(0, round((float) $booking->remainingBalanceAmount(), 2));

        $paidAt = $booking->created_at?->timezone(config('app.timezone')) ?? now();
        $paidDateLabel = $paidAt->format('d/m/Y');
        $cardLabel = $this->resolveCardLabel($booking);
        $paidNowLabel = $cardLabel !== ''
            ? 'Paid now · '.$cardLabel.' · '.$paidDateLabel
            : 'Paid now · '.$paidDateLabel;

        $cancelUntil = null;
        try {
            $deadline = app(ArtistPayoutService::class)->cancellationDeadlineAt($booking);
            if ($deadline) {
                $cancelUntil = $deadline->timezone($timezone)->format('D d/m');
            }
        } catch (\Throwable) {
            $cancelUntil = null;
        }

        $seeBookingUrl = null;
        if ($customer?->id) {
            $seeBookingUrl = URL::temporarySignedRoute(
                'user.post-booking.access',
                now()->addDays(14),
                ['user' => $customer->id, 'booking' => $booking->id]
            );
        }

        return [
            'userName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistInitials' => $this->initials($artistName),
            'artistAvatarUrl' => $avatarUrl,
            'artistMeta' => $whoMeta,
            'bookingReference' => $booking->referenceLabel(),
            'tattooTitle' => $booking->displayTitle(),
            'whenLabel' => $whenLabel,
            'whenShort' => $whenShort,
            'whereLabel' => $locationLine,
            'currencySymbol' => $currencySymbol,
            'depositAmount' => $deposit,
            'bookingFeeAmount' => $bookingFee,
            'paidNowAmount' => $paidNow,
            'paidNowLabel' => $paidNowLabel,
            'balanceAmount' => $balanceDue,
            'cancelUntilLabel' => $cancelUntil,
            'seeBookingUrl' => $seeBookingUrl,
        ];
    }

    private function locationLine($booking, $userDetail): string
    {
        $studio = $booking->studio;
        if ($studio) {
            $street = trim(trim((string) ($studio->street_number ?? '')).' '.trim((string) ($studio->street_name ?? '')));
            if ($street === '') {
                $street = trim((string) ($studio->address ?? ''));
            }
            $parts = array_values(array_filter([
                trim((string) ($studio->name ?? '')),
                $street,
                trim(implode(' ', array_filter([
                    trim((string) ($studio->postal_code ?? '')),
                    trim((string) ($studio->city ?? '')),
                ]))),
            ], fn (string $part) => $part !== ''));

            if ($parts !== []) {
                return implode(', ', $parts);
            }
        }

        if ($userDetail) {
            $lines = $userDetail->studioLocationLines();
            if ($lines !== []) {
                return implode(', ', $lines);
            }
            $compact = trim((string) $userDetail->studioNameWithCityCountry());
            if ($compact !== '') {
                return $compact;
            }
        }

        return '';
    }

    private function studioShortName($booking, $userDetail, string $locationLine): string
    {
        $name = trim((string) ($booking->studio?->name ?? ''));
        if ($name !== '') {
            $city = trim((string) ($booking->studio?->city ?? ''));

            return $city !== '' ? $name.', '.$city : $name;
        }

        if ($userDetail) {
            $resolved = trim((string) $userDetail->resolvedStudioName());
            if ($resolved !== '') {
                return $resolved;
            }
        }

        // Fall back to first segment of the where line for the artist card meta.
        if ($locationLine !== '') {
            return trim((string) strtok($locationLine, ','));
        }

        return '';
    }

    private function sessionStartLocal($booking, string $timezone): ?Carbon
    {
        $bookingDate = $booking->booking_date instanceof Carbon
            ? $booking->booking_date->format('Y-m-d')
            : (string) $booking->booking_date;
        $startUtc = (string) ($booking->start_time_utc ?? '');
        if ($bookingDate === '' || $startUtc === '') {
            return null;
        }

        try {
            return Carbon::parse($bookingDate.' '.$startUtc, 'UTC')->timezone($timezone);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sessionEndLocal($booking, string $timezone): ?Carbon
    {
        $bookingDate = $booking->booking_date instanceof Carbon
            ? $booking->booking_date->format('Y-m-d')
            : (string) $booking->booking_date;
        $endUtc = (string) ($booking->end_time_utc ?? '');
        if ($bookingDate === '' || $endUtc === '') {
            return null;
        }

        try {
            $end = Carbon::parse($bookingDate.' '.$endUtc, 'UTC')->timezone($timezone);
            $start = $this->sessionStartLocal($booking, $timezone);
            if ($start && $end->lt($start)) {
                $end->addDay();
            }

            return $end;
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatWhenLabel(?Carbon $startLocal, ?Carbon $endLocal, $booking): string
    {
        if ($startLocal && $endLocal) {
            return $startLocal->format('D d/m/Y').' · '.$startLocal->format('H:i').' to '.$endLocal->format('H:i');
        }

        if ($startLocal) {
            return $startLocal->format('D d/m/Y').' · '.$startLocal->format('H:i');
        }

        try {
            return Carbon::parse($booking->booking_date)->format('D d/m/Y');
        } catch (\Throwable) {
            return (string) $booking->booking_date;
        }
    }

    private function durationHours($booking): float
    {
        try {
            $start = Carbon::createFromFormat('H:i:s', (string) $booking->start_time_utc);
            $end = Carbon::createFromFormat('H:i:s', (string) $booking->end_time_utc);
            if ($end->lt($start)) {
                $end->addDay();
            }

            return round($start->diffInMinutes($end) / 60, 1);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function durationLabel($booking, ?Carbon $startLocal, ?Carbon $endLocal): string
    {
        $minutes = 0;
        if ($startLocal && $endLocal) {
            $minutes = max(0, $startLocal->diffInMinutes($endLocal));
        } else {
            $hours = $this->durationHours($booking);
            $minutes = (int) round($hours * 60);
        }

        if ($minutes < 1) {
            return '';
        }

        if ($minutes % 60 === 0) {
            $h = (int) ($minutes / 60);

            return $h === 1 ? '1 hour' : $h.' hours';
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h === 0) {
            return $m.' min';
        }

        return $h.'h '.$m.'m';
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

        return $symbols[strtoupper($currency)] ?? strtoupper($currency).' ';
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
            Log::info('Could not resolve card label for booking confirmation email', [
                'booking_id' => $booking->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    private function resolveArtistAmountReceived($booking, $artist): float
    {
        $userDetail = $artist?->userDetail;
        if ($userDetail) {
            return app(ArtistPayoutService::class)->computeArtistPayoutAmount($booking, $userDetail);
        }

        return max(0, round((float) ($booking->deposit_amount ?? 0), 2));
    }

    public function attachments(): array
    {
        return [];
    }
}
