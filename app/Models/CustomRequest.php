<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomRequest extends Model
{
    protected $fillable = [
        'user_id',
        'artist_id',
        'is_guest',
        'guest_id',
        'guest_spot_held',
        'guest_hold_expires_at',
        'type',
        'questions_answers',
        'anything_else_notes',
        'status',
        'booking_id',
        'reason_decline',
        'preferences',
        'preferred_days',
        'avoid_dates',
        'how_much_flexible',
        'urgency',
        'estimated_price',
        'estimated_time',
        'number_of_sessions',
        'message_for_client',
        'artist_consultation_slots',
        'artist_session_slots',
        'client_session_slots',
        'client_consultation_slots',
    ];

    protected $casts = [
        'is_guest' => 'boolean',
        'guest_spot_held' => 'boolean',
        'guest_hold_expires_at' => 'datetime',
        'questions_answers' => 'array',
        'preferences' => 'array',
        'preferred_days' => 'array',
        'artist_consultation_slots' => 'array',
        'artist_session_slots' => 'array',
        'client_session_slots' => 'array',
        'client_consultation_slots' => 'array',
        'estimated_price' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function guestSpot(): BelongsTo
    {
        return $this->belongsTo(GuestSpot::class, 'guest_id');
    }

    public function isGuestRequest(): bool
    {
        return (bool) $this->is_guest && $this->guest_id !== null;
    }

    public function referenceLabel(): string
    {
        return '#CR-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function clientDisplayName(): string
    {
        $user = $this->user;
        if (!$user) {
            return 'Client #' . $this->user_id;
        }
        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $name !== '' ? $name : (string) ($user->email ?? 'Client');
    }

    public function contactPhone(): ?string
    {
        $answers = is_array($this->questions_answers) ? $this->questions_answers : [];
        $contact = $answers['_contact'] ?? null;

        if (!is_array($contact)) {
            return null;
        }

        $phone = trim((string) ($contact['phone'] ?? ''));

        return $phone !== '' ? $phone : null;
    }

    public function filterStatusLabel(): string
    {
        return match ($this->status) {
            'confirmed' => 'Quote sent',
            'cancelled' => 'Declined',
            'moved_to_booking' => 'Moved to booking',
            default => 'New Request',
        };
    }

    public function userFilterStatusLabel(): string
    {
        if ($this->status === 'cancelled') {
            return 'Declined';
        }

        if ($this->isBooked()) {
            return 'Booked';
        }

        if ($this->canAccessConfirmTimesPage()) {
            return 'Pick your times';
        }

        if ($this->isConfirmedForUser()) {
            return 'Quote sent';
        }

        return 'Pending review';
    }

    public function userFilterKey(): string
    {
        return match ($this->status) {
            'confirmed', 'moved_to_booking' => 'confirmed',
            'cancelled' => 'declined',
            default => 'pending',
        };
    }

    public function artistInitials(): string
    {
        $artist = $this->artist;
        if (!$artist) {
            return 'AR';
        }
        $first = Str::substr((string) ($artist->first_name ?? ''), 0, 1);
        $last = Str::substr((string) ($artist->last_name ?? ''), 0, 1);
        $initials = strtoupper($first.$last);

        return $initials !== '' ? $initials : 'AR';
    }

    public function artistSearchKey(): string
    {
        $parts = [
            Str::lower($this->artistDisplayName()),
            Str::lower($this->referenceLabel()),
        ];

        return trim(implode(' ', array_filter($parts)));
    }

    public function artistProfileUrl(): ?string
    {
        $username = trim((string) ($this->artist?->userDetail?->user_name ?? ''));

        return $username !== '' ? url('/'.$username) : null;
    }

    public function isConfirmedForUser(): bool
    {
        return in_array($this->status, ['confirmed', 'moved_to_booking'], true) && $this->hasQuote();
    }

    public function isBooked(): bool
    {
        return $this->status === 'moved_to_booking' && $this->booking_id;
    }

    public function isManagedRequest(): bool
    {
        return $this->type === 'managed';
    }

    public function autoRequiresConsultation(): bool
    {
        if ($this->isGuestRequest() || $this->isManagedRequest() || $this->usesArtistOfferedSlotsPicker()) {
            return false;
        }

        $this->loadMissing(['artist.userDetail']);

        return (bool) ($this->artist?->userDetail?->require_consultation ?? false);
    }

    public function autoConsultationTiming(): string
    {
        $this->loadMissing(['artist.userDetail']);
        $timing = strtolower((string) ($this->artist?->userDetail?->consultation_timing ?? 'combined'));

        return $timing === 'separate' ? 'separate' : 'combined';
    }

    public function clientHasSelectedConsultation(): bool
    {
        if (!$this->autoRequiresConsultation()) {
            return true;
        }

        return $this->normalizedArtistSlots($this->client_consultation_slots) !== [];
    }

    public function clientHasSelectedTimes(): bool
    {
        if ($this->normalizedArtistSlots($this->client_session_slots) === []) {
            return false;
        }

        return $this->clientHasSelectedConsultation();
    }

    public function hasArtistOfferedSessionSlots(): bool
    {
        return $this->normalizedArtistSlots($this->artist_session_slots) !== [];
    }

    public function usesArtistOfferedSlotsPicker(): bool
    {
        return $this->hasArtistOfferedSessionSlots();
    }

    public function isGuestHoldActive(): bool
    {
        if (! $this->isGuestRequest() || ! $this->hasQuote() || $this->isBooked()) {
            return false;
        }

        if (! $this->guest_hold_expires_at) {
            return false;
        }

        return $this->guest_hold_expires_at->isFuture();
    }

    public function isGuestHoldExpired(): bool
    {
        if (! $this->isGuestRequest() || ! $this->hasQuote() || $this->isBooked()) {
            return false;
        }

        if (! $this->guest_hold_expires_at) {
            return false;
        }

        return $this->guest_hold_expires_at->isPast();
    }

    /**
     * User-facing block reason for guest scheduling/payment.
     * quote_expired → ask artist to send quote again
     * slots_full → guest spot has no remaining capacity
     */
    public function guestActionBlockReason(): ?string
    {
        if (! $this->isGuestRequest() || $this->isBooked()) {
            return null;
        }

        if ($this->isGuestHoldExpired() || ($this->hasQuote() && $this->status === 'confirmed' && ! $this->isGuestHoldActive())) {
            return 'quote_expired';
        }

        if ($this->status === 'confirmed' && $this->hasQuote() && $this->isGuestHoldActive()) {
            return null;
        }

        $this->loadMissing('guestSpot');
        if ($this->guestSpot && $this->guestSpot->tracksSpotCapacity() && ! $this->guestSpot->hasAvailableSpots()) {
            return 'slots_full';
        }

        return null;
    }

    public function guestActionBlockMessage(): ?string
    {
        return match ($this->guestActionBlockReason()) {
            'quote_expired' => 'Your quote hold has expired. Please ask the artist to send a quote again.',
            'slots_full' => 'All slots are full.',
            default => null,
        };
    }

    public function canSelectTimes(): bool
    {
        if ($this->status !== 'confirmed' || !$this->hasQuote() || $this->isBooked()) {
            return false;
        }

        if ($this->clientHasSelectedTimes()) {
            return false;
        }

        if ($this->isGuestRequest()) {
            return $this->isGuestHoldActive();
        }

        if ($this->hasArtistOfferedSessionSlots()) {
            return true;
        }

        return ! $this->isManagedRequest();
    }

    public function canPay(): bool
    {
        if ($this->isGuestRequest() && ! $this->isGuestHoldActive()) {
            return false;
        }

        return $this->status === 'confirmed'
            && $this->hasQuote()
            && $this->clientHasSelectedTimes()
            && !$this->isBooked();
    }

    public function canEditSelectedTimes(): bool
    {
        return $this->canPay();
    }

    public function canAccessConfirmTimesPage(): bool
    {
        return $this->canSelectTimes() || $this->canEditSelectedTimes();
    }

    /**
     * @return array<string, array<int, array{label: string, from: string, to: string}>>
     */
    public function offeredSlotsMapForPicker(): array
    {
        $map = [];
        foreach ($this->normalizedArtistSlots($this->artist_session_slots) as $slot) {
            $ranges = [];
            foreach ($slot['ranges'] as $range) {
                $ranges[] = [
                    'label' => $this->formatTimeRangeLabel($range['from'], $range['to']),
                    'from' => $range['from'],
                    'to' => $range['to'],
                ];
            }
            if ($ranges !== []) {
                $map[$slot['date']] = $ranges;
            }
        }

        return $map;
    }

    /**
     * @return array{year: int, month: int}|null
     */
    public function initialPickerMonthFromOfferedSlots(): ?array
    {
        $dates = array_keys($this->offeredSlotsMapForPicker());
        if ($dates === []) {
            return null;
        }
        sort($dates);
        try {
            $first = Carbon::parse($dates[0]);

            return ['year' => (int) $first->year, 'month' => (int) $first->month - 1];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{date: string, from: string, to: string}|null
     */
    public function clientPickerSavedConsultSelection(): ?array
    {
        $normalized = $this->normalizedArtistSlots($this->client_consultation_slots);
        if ($normalized === []) {
            return null;
        }

        $first = $normalized[0];
        $range = $first['ranges'][0] ?? null;
        if (!$range) {
            return null;
        }

        return [
            'date' => $first['date'],
            'from' => $range['from'],
            'to' => $range['to'],
        ];
    }

    public function clientConsultSlotSummary(): ?string
    {
        $selection = $this->clientPickerSavedConsultSelection();
        if (!$selection) {
            return null;
        }

        try {
            $dateLabel = Carbon::createFromFormat('Y-m-d', $selection['date'])->format('l, M j, Y');
        } catch (\Throwable) {
            $dateLabel = $selection['date'];
        }

        return $this->formatTimeRangeLabel($selection['from'], $selection['to']).' · '.$dateLabel;
    }

    /**
     * @return array{date: string, from: string, to: string}|null
     */
    public function clientPickerSavedSelection(): ?array
    {
        $normalized = $this->normalizedArtistSlots($this->client_session_slots);
        if ($normalized === []) {
            return null;
        }

        $first = $normalized[0];
        $range = $first['ranges'][0] ?? null;
        if (!$range) {
            return null;
        }

        return [
            'date' => $first['date'],
            'from' => $range['from'],
            'to' => $range['to'],
        ];
    }

    public function clientSlotSummary(): ?string
    {
        $selection = $this->clientPickerSavedSelection();
        if (!$selection) {
            return null;
        }

        try {
            $dateLabel = Carbon::createFromFormat('Y-m-d', $selection['date'])->format('l, M j, Y');
        } catch (\Throwable) {
            $dateLabel = $selection['date'];
        }

        return $this->formatTimeRangeLabel($selection['from'], $selection['to']).' · '.$dateLabel;
    }

    public function checkoutPriceAmount(): float
    {
        return max(0.01, (float) ($this->estimated_price ?? 0));
    }

    public function sessionDurationMinutes(): int
    {
        $estimated = trim((string) ($this->estimated_time ?? ''));
        if ($estimated !== '' && preg_match('/(\d+(?:\.\d+)?)/', $estimated, $match)) {
            $hours = (float) $match[1];

            return max(60, (int) round($hours * 60));
        }

        return 120;
    }

    public function checkoutDurationLabel(): string
    {
        $label = trim((string) ($this->estimated_time ?? ''));

        return $label !== '' ? $label : $this->sessionDurationMinutes().' min';
    }

    /**
     * @param  array<string, mixed>  $depositMeta
     */
    public function checkoutDepositLabel(array $depositMeta): string
    {
        return 'Artist Deposit';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'confirmed', 'moved_to_booking' => 'status-confirmed',
            'cancelled' => 'status-declined',
            default => 'status-new',
        };
    }

    public function clientInitials(): string
    {
        $user = $this->user;
        if (!$user) {
            return 'CL';
        }
        $first = Str::substr((string) ($user->first_name ?? ''), 0, 1);
        $last = Str::substr((string) ($user->last_name ?? ''), 0, 1);
        $initials = strtoupper($first.$last);

        return $initials !== '' ? $initials : 'CL';
    }

    public function clientSearchKey(): string
    {
        $parts = [
            Str::lower($this->clientDisplayName()),
            Str::lower((string) ($this->user?->email ?? '')),
            Str::lower($this->referenceLabel()),
        ];

        return trim(implode(' ', array_filter($parts)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function formattedQuestionAnswers(): array
    {
        $answers = is_array($this->questions_answers) ? $this->questions_answers : [];
        $output = [];

        foreach ($answers as $key => $entry) {
            if ($key === '_contact' || !is_array($entry)) {
                continue;
            }
            $output[] = $entry;
        }

        return array_values($output);
    }

    public function artistDisplayName(): string
    {
        $artist = $this->artist;
        if (! $artist) {
            return 'Artist';
        }

        $detail = $artist->relationLoaded('userDetail') ? $artist->userDetail : $artist->userDetail()->first();
        if ($detail) {
            return $detail->publicDisplayName();
        }

        $name = trim(($artist->first_name ?? '').' '.($artist->last_name ?? ''));

        return $name !== '' ? $name : (string) ($artist->email ?? 'Artist');
    }

    public function estimatedPriceLabel(): string
    {
        $price = $this->estimated_price;
        if ($price === null || (float) $price <= 0) {
            return '—';
        }

        return '€'.number_format((float) $price, 2, '.', ',');
    }

    public function hasQuote(): bool
    {
        return (float) ($this->estimated_price ?? 0) > 0
            || trim((string) ($this->estimated_time ?? '')) !== ''
            || trim((string) ($this->number_of_sessions ?? '')) !== ''
            || trim((string) ($this->message_for_client ?? '')) !== '';
    }

    public function referralSource(): ?string
    {
        $answers = is_array($this->questions_answers) ? $this->questions_answers : [];
        $contact = $answers['_contact'] ?? null;
        if (!is_array($contact)) {
            return null;
        }
        $source = trim((string) ($contact['referral_source'] ?? ''));

        return $source !== '' ? $source : null;
    }

    public function schedulingLabel(): string
    {
        return $this->isManagedRequest() ? 'Managed scheduling' : 'Auto scheduling';
    }

    /**
     * @return array<int, array{date: string, ranges: array<int, array{from: string, to: string}>}>
     */
    public function normalizedArtistSlots(mixed $slots = null): array
    {
        $raw = $slots ?? $this->artist_session_slots;

        return \App\Support\ArtistSessionSlots::normalize(is_array($raw) ? $raw : []);
    }

    public function formatTimeRangeLabel(string $from, string $to): string
    {
        try {
            $fromLabel = Carbon::createFromFormat('H:i', strlen($from) >= 5 ? substr($from, 0, 5) : $from)->format('g:i A');
            $toLabel = Carbon::createFromFormat('H:i', strlen($to) >= 5 ? substr($to, 0, 5) : $to)->format('g:i A');
        } catch (\Throwable) {
            return $from.' – '.$to;
        }

        return $fromLabel.' – '.$toLabel;
    }

    public function availabilityStructured(): array
    {
        $preferredDates = [];
        $preferences = is_array($this->preferences) ? $this->preferences : [];
        foreach ($preferences as $index => $pref) {
            if (!is_array($pref)) {
                continue;
            }
            $date = trim((string) ($pref['date'] ?? ''));
            if ($date === '') {
                continue;
            }
            try {
                $dateLabel = Carbon::parse($date)->format('l, M j, Y');
            } catch (\Throwable) {
                $dateLabel = $date;
            }
            $preferredDates[] = [
                'preference' => (int) ($pref['preference'] ?? ($index + 1)),
                'date' => $date,
                'dateLabel' => $dateLabel,
                'times' => array_values(array_filter((array) ($pref['times_of_day'] ?? []))),
            ];
        }

        return [
            'preferredDates' => $preferredDates,
            'preferredDays' => array_values(array_filter((array) ($this->preferred_days ?? []))),
            'flexibility' => trim((string) ($this->how_much_flexible ?? '')),
            'urgency' => trim((string) ($this->urgency ?? '')),
            'avoidDates' => trim((string) ($this->avoid_dates ?? '')),
            'sessionGap' => '',
        ];
    }

    public function toUserPanelArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->referenceLabel(),
            'status' => $this->status,
            'filterStatus' => $this->userFilterStatusLabel(),
            'filterKey' => $this->userFilterKey(),
            'statusBadgeClass' => $this->statusBadgeClass(),
            'artistName' => $this->artistDisplayName(),
            'artistInitials' => $this->artistInitials(),
            'artistSearch' => $this->artistSearchKey(),
            'artistProfileUrl' => $this->artistProfileUrl(),
            'submittedAt' => $this->created_at?->format('M j, Y') ?? '—',
            'submittedIso' => $this->created_at?->format('Y-m-d') ?? '',
            'additionalNotes' => trim((string) ($this->anything_else_notes ?? '')) ?: '—',
            'referralSource' => $this->referralSource() ?? '—',
            'type' => $this->type ?? 'auto',
            'isGuest' => $this->isGuestRequest(),
            'guestSpot' => $this->artistGuestSpotSummary(),
            'schedulingLabel' => $this->schedulingLabel(),
            'availabilityDetails' => $this->availabilityStructured(),
            'questionsAnswers' => $this->formattedQuestionAnswers(),
            'reasonDecline' => $this->reason_decline,
            'estimatedPrice' => $this->estimated_price,
            'estimatedPriceLabel' => $this->estimatedPriceLabel(),
            'estimatedTime' => $this->estimated_time,
            'numberOfSessions' => $this->number_of_sessions,
            'messageForClient' => $this->message_for_client,
            'artistSessionSlots' => $this->normalizedArtistSlots(),
            'hasQuote' => $this->hasQuote(),
            'isPending' => $this->status === 'pending',
            'isDeclined' => $this->status === 'cancelled',
            'isManaged' => $this->isManagedRequest(),
            'isBooked' => $this->isBooked(),
            'canSelectTimes' => $this->canSelectTimes(),
            'canPay' => $this->canPay(),
            'guestHoldExpiresAt' => $this->guest_hold_expires_at?->toIso8601String(),
            'guestHoldActive' => $this->isGuestHoldActive(),
            'guestActionBlockReason' => $this->guestActionBlockReason(),
            'guestActionBlockMessage' => $this->guestActionBlockMessage(),
            'confirmTimesUrl' => $this->canAccessConfirmTimesPage()
                ? route('user.custom-requests.confirm-times', ['customRequest' => $this->id, 'fresh' => 1])
                : null,
            'paymentUrl' => $this->canPay()
                ? route('user.custom-requests.payment', ['customRequest' => $this->id])
                : null,
        ];
    }

    public function toArtistPanelArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->referenceLabel(),
            'status' => $this->status,
            'filterStatus' => $this->filterStatusLabel(),
            'statusBadgeClass' => $this->statusBadgeClass(),
            'clientName' => $this->clientDisplayName(),
            'clientInitials' => $this->clientInitials(),
            'clientEmail' => (string) ($this->user?->email ?? ''),
            'clientPhone' => $this->contactPhone() ?? '',
            'clientSearch' => $this->clientSearchKey(),
            'submittedAt' => $this->created_at?->format('M j, Y') ?? '—',
            'submittedIso' => $this->created_at?->format('Y-m-d') ?? '',
            'additionalNotes' => trim((string) ($this->anything_else_notes ?? '')) ?: '—',
            'referralSource' => $this->referralSource() ?? '—',
            'type' => $this->type ?? 'auto',
            'isGuest' => $this->isGuestRequest(),
            'guestSpot' => $this->artistGuestSpotSummary(),
            'guestHoldExpiresAt' => $this->guest_hold_expires_at?->toIso8601String(),
            'guestHoldActive' => $this->isGuestHoldActive(),
            'schedulingLabel' => $this->schedulingLabel(),
            'availabilityDetails' => $this->availabilityStructured(),
            'artistSessionSlots' => $this->normalizedArtistSlots(),
            'questionsAnswers' => $this->formattedQuestionAnswers(),
            'reasonDecline' => $this->reason_decline,
            'estimatedPrice' => $this->estimated_price,
            'estimatedTime' => $this->estimated_time,
            'numberOfSessions' => $this->number_of_sessions,
            'messageForClient' => $this->message_for_client,
            'isPending' => $this->status === 'pending',
            'canDecline' => $this->status === 'pending',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function artistGuestSpotSummary(): ?array
    {
        if (! $this->isGuestRequest()) {
            return null;
        }

        $this->loadMissing('guestSpot');
        $spot = $this->guestSpot;
        if (! $spot) {
            return null;
        }

        return [
            'city' => $spot->city,
            'country' => $spot->country,
            'fromDate' => $spot->from_date?->format('M j, Y'),
            'toDate' => $spot->to_date?->format('M j, Y'),
            'availabilityTime' => $spot->listAvailabilityTimeLabel(),
            'studio' => $spot->listStudioLabel(),
            'location' => $spot->listLocationLabel(),
            'remainingSpotsLabel' => $spot->listRemainingSpotsLabel(),
            'tracksCapacity' => $spot->tracksSpotCapacity(),
            'remainingSpots' => (int) ($spot->remaining_spots ?? 0),
            'isFull' => $spot->isFull(),
        ];
    }
}
