<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class AppointmentReminderEmailPresenter
{
    /**
     * @param  '24h'|'72h'  $kind
     * @return array<string, mixed>
     */
    public static function forBooking(
        Booking $booking,
        User $client,
        ?UserDetail $artistDetail,
        string $kind,
        ?string $consentUrl = null,
    ): array {
        $booking->loadMissing(['artist.userDetail', 'studio', 'tattoo']);

        $userDetail = $artistDetail ?? $booking->artist?->userDetail ?? new UserDetail;
        if ($artistDetail && $booking->artist) {
            $userDetail->setRelation('user', $booking->artist);
        }

        $artistName = trim((string) $userDetail->publicDisplayName()) ?: 'your artist';
        $artistFirst = trim((string) ($booking->artist?->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $clientFirst = trim((string) ($client->first_name ?? ''));
        if ($clientFirst === '') {
            $clientFirst = 'there';
        }

        $timezone = trim((string) ($booking->timezone ?: $userDetail->timezone ?: 'UTC')) ?: 'UTC';
        $startLocal = $booking->sessionStartUtc()?->copy()->timezone($timezone);
        $endLocal = self::sessionEndLocal($booking, $timezone);

        $whenLabel = self::formatWhenLabel($startLocal, $endLocal, $booking);
        $subjectTime = $startLocal ? $startLocal->format('H:i') : '';

        $handle = trim((string) ($userDetail->user_name ?? ''));
        if ($handle !== '' && ! str_starts_with($handle, '@')) {
            $handle = '@'.$handle;
        }

        $whereLabel = self::locationLine($booking, $userDetail);
        $studioShort = self::studioShortName($booking, $userDetail, $whereLabel);
        $artistMeta = trim(($handle !== '' ? $handle : '').($handle !== '' && $studioShort !== '' ? ' · ' : '').$studioShort);

        $currency = strtoupper((string) ($booking->currency ?: 'EUR'));
        $symbol = self::currencySymbol($currency);
        $balance = max(0, round((float) $booking->remainingBalanceAmount(), 2));

        $mapsUrl = self::mapsUrl($booking, $userDetail, $whereLabel);
        $showConsent = filled($consentUrl);

        $introText = $kind === '24h'
            ? 'See you tomorrow. Here’s everything you need.'
            : 'Your session is coming up. Here’s everything you need.';

        $subject = self::subject($kind, $artistName, $subjectTime, $startLocal);

        $viewBookingUrl = URL::temporarySignedRoute(
            'user.post-booking.access',
            now()->addDays(14),
            ['user' => $client->id, 'booking' => $booking->id]
        );

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistInitials' => self::initials($artistName),
            'artistAvatarUrl' => self::avatarUrl($userDetail),
            'artistMeta' => $artistMeta,
            'introText' => $introText,
            'whenLabel' => $whenLabel,
            'whereLabel' => $whereLabel,
            'balanceLabel' => $symbol.number_format($balance, 2, '.', ''),
            'showBalance' => $balance > 0,
            'mapsUrl' => $mapsUrl,
            'showConsentCta' => $showConsent,
            'consentUrl' => $consentUrl,
            'viewBookingUrl' => $viewBookingUrl,
            'subject' => $subject,
            'policyNote' => 'Need to move it? Rescheduling is free until 24 hours before. Cancelling now means the deposit isn’t refunded.',
        ];
    }

    private static function subject(
        string $kind,
        string $artistName,
        string $subjectTime,
        ?Carbon $startLocal,
    ): string {
        if ($kind === '24h') {
            return $subjectTime !== ''
                ? 'Tomorrow at '.$subjectTime.': your session with '.$artistName
                : 'Tomorrow: your session with '.$artistName;
        }

        if ($startLocal && $subjectTime !== '') {
            return $startLocal->format('D j M').' at '.$subjectTime.': your session with '.$artistName;
        }

        return 'Your session with '.$artistName.' is coming up';
    }

    private static function sessionEndLocal(Booking $booking, string $timezone): ?Carbon
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
            $start = $booking->sessionStartUtc()?->copy()->timezone($timezone);
            if ($start && $end->lt($start)) {
                $end->addDay();
            }

            return $end;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function formatWhenLabel(?Carbon $startLocal, ?Carbon $endLocal, Booking $booking): string
    {
        $duration = self::durationLabel($startLocal, $endLocal);

        if ($startLocal && $endLocal) {
            $label = $startLocal->format('D d/m/Y').' · '.$startLocal->format('H:i').' to '.$endLocal->format('H:i');

            return $duration !== '' ? $label.' ('.$duration.')' : $label;
        }

        if ($startLocal) {
            $label = $startLocal->format('D d/m/Y').' · '.$startLocal->format('H:i');

            return $duration !== '' ? $label.' ('.$duration.')' : $label;
        }

        try {
            return Carbon::parse((string) $booking->booking_date)->format('D d/m/Y');
        } catch (\Throwable) {
            return (string) ($booking->booking_date ?? '');
        }
    }

    private static function durationLabel(?Carbon $startLocal, ?Carbon $endLocal): string
    {
        if (! $startLocal || ! $endLocal) {
            return '';
        }

        $minutes = max(0, $startLocal->diffInMinutes($endLocal));
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

    private static function locationLine(Booking $booking, UserDetail $userDetail): string
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

        $lines = $userDetail->studioLocationLines();
        if ($lines !== []) {
            return implode(', ', $lines);
        }

        return trim((string) $userDetail->studioNameWithCityCountry());
    }

    private static function studioShortName(Booking $booking, UserDetail $userDetail, string $locationLine): string
    {
        $name = trim((string) ($booking->studio?->name ?? ''));
        if ($name !== '') {
            $city = trim((string) ($booking->studio?->city ?? ''));

            return $city !== '' ? $name.', '.$city : $name;
        }

        $resolved = trim((string) $userDetail->resolvedStudioName());
        if ($resolved !== '') {
            return $resolved;
        }

        if ($locationLine !== '') {
            return trim((string) strtok($locationLine, ','));
        }

        return '';
    }

    private static function mapsUrl(Booking $booking, UserDetail $userDetail, string $whereLabel): string
    {
        $studioUrl = trim((string) ($booking->studio?->google_maps_link ?? ''));
        if ($studioUrl !== '') {
            return $studioUrl;
        }

        $artistUrl = trim((string) $userDetail->publicStudioMapsLink());
        if ($artistUrl !== '') {
            return $artistUrl;
        }

        if ($whereLabel === '') {
            return '';
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($whereLabel);
    }

    private static function avatarUrl(UserDetail $userDetail): ?string
    {
        $avatarPath = trim((string) ($userDetail->avatar ?? ''));
        if ($avatarPath === '') {
            return null;
        }

        return str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')
            ? $avatarPath
            : asset(ltrim($avatarPath, '/'));
    }

    private static function currencySymbol(string $currency): string
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

    private static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : '?';
    }
}
