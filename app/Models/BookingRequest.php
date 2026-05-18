<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingRequest extends Model
{
    protected $table = 'booking_requests';

    protected $fillable = [
        'user_id',
        'artist_id',
        'tattoo_id',
        'status',
        'questions_answers',
        'consultation_details',
        'preferences',
        'preferred_days',
        'avoid_dates',
        'how_much_flexible',
        'urgency',
        'reason_decline',
        'artist_session_slots',
        'artist_consultation_slots',
        'artist_notes_to_client',
        'client_consultation_slots',
        'client_session_slots',
        'booking_id',
    ];

    protected $casts = [
        'questions_answers' => 'array',
        'preferences' => 'array',
        'preferred_days' => 'array',
        'artist_session_slots' => 'array',
        'artist_consultation_slots' => 'array',
        'client_consultation_slots' => 'array',
        'client_session_slots' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function tattoo(): BelongsTo
    {
        return $this->belongsTo(ArtistDesign::class, 'tattoo_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function referenceLabel(): string
    {
        return '#REQ-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function consultationDetailsArray(): ?array
    {
        if (!$this->consultation_details) {
            return null;
        }
        $decoded = json_decode($this->consultation_details, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function hasConsultation(): bool
    {
        $details = $this->consultationDetailsArray();

        return is_array($details) && !empty($details['required']);
    }

    public function filterStatusLabel(): string
    {
        return match ($this->status) {
            'confirmed', 'moved_to_booking' => 'Confirmed',
            'cancelled' => 'Declined',
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

        if ($this->canPay()) {
            return 'Complete payment';
        }

        if ($this->canSelectTimes()) {
            return 'Pick your times';
        }

        if ($this->isConfirmedForUser()) {
            return 'Confirmed';
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

    public function isConfirmedForUser(): bool
    {
        return in_array($this->status, ['confirmed', 'moved_to_booking'], true);
    }

    public function isBooked(): bool
    {
        return $this->status === 'moved_to_booking' && $this->booking_id;
    }

    public function clientHasSelectedTimes(): bool
    {
        return $this->normalizedArtistSlots($this->client_session_slots) !== [];
    }

    public function canSelectTimes(): bool
    {
        return $this->status === 'confirmed'
            && !$this->clientHasSelectedTimes()
            && $this->normalizedArtistSlots($this->artist_session_slots) !== [];
    }

    public function canPay(): bool
    {
        return $this->status === 'confirmed'
            && $this->clientHasSelectedTimes()
            && !$this->isBooked();
    }

    /** Client may change times after picking but before payment. */
    public function canEditSelectedTimes(): bool
    {
        return $this->canPay();
    }

    public function canAccessConfirmTimesPage(): bool
    {
        return $this->canSelectTimes() || $this->canEditSelectedTimes();
    }

    /**
     * @return array{date: string, from: string, to: string}|null
     */
    public function clientPickerSavedSelection(string $kind): ?array
    {
        $raw = $kind === 'consult'
            ? $this->client_consultation_slots
            : $this->client_session_slots;

        $normalized = $this->normalizedArtistSlots($raw);
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

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'confirmed', 'moved_to_booking' => 'status-confirmed',
            'cancelled' => 'status-declined',
            default => 'status-new',
        };
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

    public function clientInitials(): string
    {
        $user = $this->user;
        if (!$user) {
            return 'CL';
        }
        $first = Str::substr((string) ($user->first_name ?? ''), 0, 1);
        $last = Str::substr((string) ($user->last_name ?? ''), 0, 1);
        $initials = strtoupper($first . $last);

        return $initials !== '' ? $initials : 'CL';
    }

    public function clientSearchKey(): string
    {
        return Str::lower($this->clientDisplayName());
    }

    public function artistDisplayName(): string
    {
        $artist = $this->artist;
        if (!$artist) {
            return 'Artist';
        }
        $name = trim(($artist->first_name ?? '') . ' ' . ($artist->last_name ?? ''));

        return $name !== '' ? $name : (string) ($artist->email ?? 'Artist');
    }

    public function artistInitials(): string
    {
        $artist = $this->artist;
        if (!$artist) {
            return 'AR';
        }
        $first = Str::substr((string) ($artist->first_name ?? ''), 0, 1);
        $last = Str::substr((string) ($artist->last_name ?? ''), 0, 1);
        $initials = strtoupper($first . $last);

        return $initials !== '' ? $initials : 'AR';
    }

    public function artistSearchKey(): string
    {
        return Str::lower($this->artistDisplayName());
    }

    public function artistProfileUrl(): ?string
    {
        $username = trim((string) ($this->artist?->userDetail?->user_name ?? ''));

        return $username !== '' ? url('/'.$username) : null;
    }

    public function hasArtistOffer(): bool
    {
        $session = $this->normalizedArtistSlots($this->artist_session_slots);
        $consult = $this->normalizedArtistSlots($this->artist_consultation_slots);
        $notes = trim((string) ($this->artist_notes_to_client ?? ''));

        return $session !== [] || $consult !== [] || $notes !== '';
    }

    public function requiresConsultationPick(): bool
    {
        return $this->hasConsultation()
            && $this->normalizedArtistSlots($this->artist_consultation_slots) !== [];
    }

    /**
     * @return array<string, array<int, array{label: string, from: string, to: string}>>
     */
    public function offeredSlotsMapForPicker(string $kind): array
    {
        $raw = $kind === 'consult'
            ? $this->artist_consultation_slots
            : $this->artist_session_slots;

        $map = [];
        foreach ($this->normalizedArtistSlots($raw) as $slot) {
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

    /**
     * @return array{year: int, month: int}|null  month is 0-indexed for JS Date
     */
    public function initialPickerMonthFromSlots(array $consultMap, array $sessionMap): ?array
    {
        $dates = array_merge(array_keys($consultMap), array_keys($sessionMap));
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

    public function sessionDurationLabel(): string
    {
        $hours = (float) ($this->tattoo?->session_duration ?? 0);

        if ($hours <= 0) {
            return '—';
        }

        return rtrim(rtrim(number_format($hours, 1), '0'), '.').' hour'.($hours == 1 ? '' : 's');
    }

    public function schedulingLabel(): string
    {
        return $this->hasConsultation() ? 'Managed + Consultation' : 'Managed';
    }

    public function consultationTypeLabel(): string
    {
        if (!$this->hasConsultation()) {
            return 'None';
        }
        $type = (string) ($this->consultationDetailsArray()['type'] ?? '');

        return match ($type) {
            'video' => 'Video Call',
            'phone' => 'Phone Call',
            'studio' => 'In-Studio Visit',
            default => ucfirst($type ?: 'Consultation'),
        };
    }

    public function answerByKeywords(array $keywords): ?string
    {
        $answers = is_array($this->questions_answers) ? $this->questions_answers : [];
        foreach ($answers as $item) {
            if (!is_array($item)) {
                continue;
            }
            $question = Str::lower((string) ($item['question'] ?? ''));
            foreach ($keywords as $keyword) {
                if (!str_contains($question, Str::lower($keyword))) {
                    continue;
                }
                $answer = $item['answer'] ?? null;
                if (is_bool($answer)) {
                    return $answer ? 'Yes' : 'No';
                }
                if (is_array($answer)) {
                    return implode(', ', $answer);
                }
                $text = trim((string) $answer);
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }

    public function placementLabel(): string
    {
        return $this->answerByKeywords(['placement', 'body part', 'where']) ?? '—';
    }

    public function healthLabel(): string
    {
        return $this->answerByKeywords(['health', 'allerg', 'medical', 'condition']) ?? 'None reported';
    }

    public function designStyleLabel(): string
    {
        $style = (string) ($this->tattoo?->primary_style ?? '');

        return $style !== '' ? ucwords(str_replace('-', ' ', $style)) : '—';
    }

    public function priceLabel(): string
    {
        $tattoo = $this->tattoo;
        if (!$tattoo) {
            return '—';
        }
        $min = (float) ($tattoo->min_price ?? 0);
        $max = (float) ($tattoo->max_price ?? 0);
        if ($min > 0 && $max > 0 && $max > $min) {
            return '€' . number_format($min, 0) . ' – €' . number_format($max, 0);
        }
        if ($min > 0) {
            return '€' . number_format($min, 0);
        }

        return '—';
    }

    public function designImageUrl(): ?string
    {
        $image = (string) ($this->tattoo?->image ?? '');
        if ($image === '') {
            return null;
        }
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return asset(ltrim($image, '/'));
    }

    public function availabilitySummary(): string
    {
        $parts = [];
        $preferences = is_array($this->preferences) ? $this->preferences : [];
        foreach ($preferences as $pref) {
            if (!is_array($pref)) {
                continue;
            }
            $date = trim((string) ($pref['date'] ?? ''));
            if ($date === '') {
                continue;
            }
            $times = array_filter((array) ($pref['times_of_day'] ?? []));
            $parts[] = $date . ($times ? ' (' . implode(', ', $times) . ')' : '');
        }
        $days = is_array($this->preferred_days) ? $this->preferred_days : [];
        if ($days) {
            $parts[] = implode(' · ', $days);
        }
        if ($this->how_much_flexible) {
            $parts[] = (string) $this->how_much_flexible;
        }
        if ($this->urgency) {
            $parts[] = 'Urgency: ' . $this->urgency;
        }
        if ($this->avoid_dates) {
            $parts[] = 'Avoid: ' . $this->avoid_dates;
        }
        $consult = $this->consultationDetailsArray();
        if (is_array($consult) && !empty($consult['session_gap'])) {
            $parts[] = 'Session gap: ' . $consult['session_gap'];
        }

        return $parts ? implode(' · ', $parts) : '—';
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

        $consult = $this->consultationDetailsArray();
        $sessionGap = is_array($consult) ? trim((string) ($consult['session_gap'] ?? '')) : '';

        return [
            'preferredDates' => $preferredDates,
            'preferredDays' => array_values(array_filter((array) ($this->preferred_days ?? []))),
            'flexibility' => trim((string) ($this->how_much_flexible ?? '')),
            'urgency' => trim((string) ($this->urgency ?? '')),
            'avoidDates' => trim((string) ($this->avoid_dates ?? '')),
            'sessionGap' => $sessionGap,
        ];
    }

    public function additionalNotes(): string
    {
        $notes = [];
        $answers = is_array($this->questions_answers) ? $this->questions_answers : [];
        foreach ($answers as $item) {
            if (!is_array($item)) {
                continue;
            }
            $type = Str::lower((string) ($item['type'] ?? ''));
            if ($type !== 'textarea') {
                continue;
            }
            $text = trim((string) ($item['answer'] ?? ''));
            if ($text !== '') {
                $notes[] = $text;
            }
        }

        return $notes ? implode("\n\n", $notes) : '—';
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
            'designTitle' => (string) ($this->tattoo?->title ?? 'Design'),
            'designStyle' => $this->designStyleLabel(),
            'priceLabel' => $this->priceLabel(),
            'schedulingLabel' => $this->schedulingLabel(),
            'consultationLabel' => $this->consultationTypeLabel(),
            'placement' => $this->placementLabel(),
            'health' => $this->healthLabel(),
            'availability' => $this->availabilitySummary(),
            'availabilityDetails' => $this->availabilityStructured(),
            'additionalNotes' => $this->additionalNotes(),
            'designImage' => $this->designImageUrl(),
            'isPending' => $this->status === 'pending',
            'isConfirmed' => $this->isConfirmedForUser(),
            'isBooked' => $this->isBooked(),
            'canSelectTimes' => $this->canSelectTimes(),
            'canPay' => $this->canPay(),
            'clientHasSelectedTimes' => $this->clientHasSelectedTimes(),
            'confirmTimesUrl' => $this->canAccessConfirmTimesPage()
                ? route('user.requests.confirm-times', ['bookingRequest' => $this->id])
                : null,
            'paymentUrl' => $this->canPay()
                ? route('user.requests.payment', ['bookingRequest' => $this->id])
                : null,
            'clientSessionSlots' => $this->normalizedArtistSlots($this->client_session_slots),
            'clientConsultationSlots' => $this->normalizedArtistSlots($this->client_consultation_slots),
            'isDeclined' => $this->status === 'cancelled',
            'reasonDecline' => $this->reason_decline,
            'artistNotesToClient' => $this->artist_notes_to_client,
            'artistSessionSlots' => $this->normalizedArtistSlots($this->artist_session_slots),
            'artistConsultationSlots' => $this->normalizedArtistSlots($this->artist_consultation_slots),
            'hasArtistOffer' => $this->hasArtistOffer(),
            'hasConsultation' => $this->hasConsultation(),
            'questionsAnswers' => is_array($this->questions_answers) ? array_values($this->questions_answers) : [],
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
            'clientSearch' => $this->clientSearchKey(),
            'submittedAt' => $this->created_at?->format('M j, Y') ?? '—',
            'submittedIso' => $this->created_at?->format('Y-m-d') ?? '',
            'designTitle' => (string) ($this->tattoo?->title ?? 'Design'),
            'designStyle' => $this->designStyleLabel(),
            'priceLabel' => $this->priceLabel(),
            'schedulingLabel' => $this->schedulingLabel(),
            'consultationLabel' => $this->consultationTypeLabel(),
            'placement' => $this->placementLabel(),
            'health' => $this->healthLabel(),
            'availability' => $this->availabilitySummary(),
            'availabilityDetails' => $this->availabilityStructured(),
            'additionalNotes' => $this->additionalNotes(),
            'designImage' => $this->designImageUrl(),
            'isPending' => $this->status === 'pending',
            'canDecline' => $this->status === 'pending',
            'reasonDecline' => $this->reason_decline,
            'artistNotesToClient' => $this->artist_notes_to_client,
            'artistSessionSlots' => $this->normalizedArtistSlots($this->artist_session_slots),
            'artistConsultationSlots' => $this->normalizedArtistSlots($this->artist_consultation_slots),
            'questionsAnswers' => is_array($this->questions_answers) ? array_values($this->questions_answers) : [],
        ];
    }

    /**
     * @param  mixed  $slots
     * @return array<int, array{date: string, ranges: array<int, array{from: string, to: string}>}>
     */
    public function normalizedArtistSlots(mixed $slots): array
    {
        if (!is_array($slots)) {
            return [];
        }

        $normalized = [];

        foreach ($slots as $slot) {
            if (!is_array($slot)) {
                continue;
            }

            $date = trim((string) ($slot['date'] ?? ''));
            if ($date === '') {
                continue;
            }

            $ranges = [];
            foreach ((array) ($slot['ranges'] ?? []) as $range) {
                if (!is_array($range)) {
                    continue;
                }
                $from = substr(trim((string) ($range['from'] ?? '')), 0, 5);
                $to = substr(trim((string) ($range['to'] ?? '')), 0, 5);
                if ($from !== '' && $to !== '') {
                    $ranges[] = ['from' => $from, 'to' => $to];
                }
            }

            if ($ranges !== []) {
                $normalized[] = ['date' => $date, 'ranges' => $ranges];
            }
        }

        return $normalized;
    }
}
