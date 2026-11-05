<?php

namespace App\Mail;

use App\Models\BookingRequest;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\BookingCheckoutPricingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagedBookingSlotsOfferedUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BookingRequest $bookingRequest,
        public string $chooseTimesUrl,
        public string $requestsUrl = '',
    ) {}

    public function envelope(): Envelope
    {
        $data = $this->viewData();
        $artist = (string) ($data['artistName'] ?? 'your artist');
        $whenShort = (string) ($data['whenShort'] ?? '');

        $subject = $whenShort !== ''
            ? 'Your appointment with '.$artist.': '.$whenShort
            : 'Your appointment with '.$artist;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.managed-booking-slots-offered-user',
            with: $this->viewData(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        $request = $this->bookingRequest;
        $request->loadMissing(['artist.userDetail', 'user', 'tattoo']);

        $artist = $request->artist;
        $customer = $request->user;
        $userDetail = $artist?->userDetail ?? new UserDetail;

        $artistName = $this->artistName($userDetail, $artist);
        $artistFirst = trim((string) ($artist?->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $clientFirst = trim((string) ($customer?->first_name ?? ''));
        if ($clientFirst === '') {
            $clientName = trim((string) $request->clientDisplayName());
            if ($clientName !== '' && ! str_starts_with($clientName, 'Client #')) {
                $clientFirst = trim((string) strtok($clientName, ' ')) ?: $clientName;
            } else {
                $clientFirst = 'there';
            }
        }

        $slot = $this->primarySessionSlot($request);
        $whenShort = $slot
            ? $this->formatWhenShort($slot['date'], $slot['from'])
            : '';
        $whenLabel = $slot
            ? $this->formatWhenLabel($slot['date'], $slot['from'], $slot['to'])
            : 'See offered times';

        $whereLabel = $this->whereLabel($userDetail);
        $currency = strtoupper((string) ($userDetail->currency ?: 'EUR'));
        $symbol = $this->currencySymbol($currency);
        $deposit = $this->depositAmount($request, $userDetail);
        $depositLabel = $symbol.number_format($deposit, 2, '.', '');

        $design = trim((string) ($request->tattoo?->title ?? ''));
        if ($design === '') {
            $design = 'Design';
        }

        $slotCount = $this->sessionRangeCount($request);
        $introText = $slotCount <= 1 && $whenShort !== ''
            ? $artistFirst.' set your appointment for <b>'.$whenShort.'</b>. Pay the deposit to confirm it.'
            : $artistFirst.' set times for your appointment. Pick a slot and pay the deposit to confirm it.';

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistInitials' => $this->initials($artistName),
            'artistAvatarUrl' => $this->avatarUrl($userDetail),
            'artistMeta' => $this->artistMeta($userDetail),
            'introHtml' => $introText,
            'bookingReference' => $request->referenceLabel(),
            'designTitle' => $design,
            'whenLabel' => $whenLabel,
            'whenShort' => $whenShort,
            'whereLabel' => $whereLabel,
            'depositLabel' => $depositLabel,
            'payUrl' => $this->chooseTimesUrl,
            'ctaLabel' => 'Pay '.$depositLabel.' deposit',
            'validityNote' => 'Pay within 48 hours to keep this time. After that, '.$artistFirst.' may give it to someone else.',
        ];
    }

    /**
     * @return array{date: string, from: string, to: string}|null
     */
    private function primarySessionSlot(BookingRequest $request): ?array
    {
        $slots = $request->normalizedArtistSlots($request->artist_session_slots);
        if ($slots === []) {
            return null;
        }

        usort($slots, fn (array $a, array $b) => strcmp((string) $a['date'], (string) $b['date']));

        foreach ($slots as $slot) {
            $ranges = (array) ($slot['ranges'] ?? []);
            usort($ranges, fn (array $a, array $b) => strcmp((string) ($a['from'] ?? ''), (string) ($b['from'] ?? '')));
            $range = $ranges[0] ?? null;
            if (! is_array($range)) {
                continue;
            }

            $from = substr((string) ($range['from'] ?? ''), 0, 5);
            $to = substr((string) ($range['to'] ?? ''), 0, 5);
            if ($from === '' || $to === '') {
                continue;
            }

            return [
                'date' => (string) $slot['date'],
                'from' => $from,
                'to' => $to,
            ];
        }

        return null;
    }

    private function sessionRangeCount(BookingRequest $request): int
    {
        $count = 0;
        foreach ($request->normalizedArtistSlots($request->artist_session_slots) as $slot) {
            $count += count((array) ($slot['ranges'] ?? []));
        }

        return $count;
    }

    private function formatWhenShort(string $date, string $from): string
    {
        try {
            $day = Carbon::createFromFormat('Y-m-d', $date);
            $time = Carbon::createFromFormat('H:i', $from);

            return $day->format('D j M').', '.$time->format('H:i');
        } catch (\Throwable) {
            return trim($date.' '.$from);
        }
    }

    private function formatWhenLabel(string $date, string $from, string $to): string
    {
        try {
            $day = Carbon::createFromFormat('Y-m-d', $date);
            $start = Carbon::createFromFormat('H:i', $from);
            $end = Carbon::createFromFormat('H:i', $to);
            if ($end->lt($start)) {
                $end->addDay();
            }

            $minutes = max(0, $start->diffInMinutes($end));
            $duration = $this->durationLabel($minutes);

            $label = $day->format('D d/m/Y').' · '.$start->format('H:i').' to '.$end->format('H:i');

            return $duration !== '' ? $label.' ('.$duration.')' : $label;
        } catch (\Throwable) {
            return trim($date.' · '.$from.' to '.$to);
        }
    }

    private function durationLabel(int $minutes): string
    {
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

    private function depositAmount(BookingRequest $request, UserDetail $userDetail): float
    {
        $tattooMin = (float) ($request->tattoo?->min_price ?? 0);
        if ($tattooMin <= 0) {
            $tattooMin = (float) ($request->tattoo?->max_price ?? 0);
        }

        try {
            $meta = app(BookingCheckoutPricingService::class)
                ->resolveDepositForTattoo($userDetail, max(0, $tattooMin));

            return max(0, round((float) ($meta['deposit'] ?? 0), 2));
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function whereLabel(UserDetail $userDetail): string
    {
        $lines = $userDetail->studioLocationLines();
        if ($lines !== []) {
            return implode(', ', $lines);
        }

        $compact = trim((string) $userDetail->studioNameWithCityCountry());
        if ($compact !== '') {
            return $compact;
        }

        $resolved = trim((string) $userDetail->resolvedStudioName());

        return $resolved !== '' ? $resolved : '';
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

    private function artistMeta(UserDetail $userDetail): string
    {
        $handle = trim((string) ($userDetail->user_name ?? ''));
        if ($handle !== '' && ! str_starts_with($handle, '@')) {
            $handle = '@'.$handle;
        }

        $studio = trim((string) $userDetail->studioNameWithCityCountry());
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
