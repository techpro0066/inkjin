<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestSpot extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'city',
        'country',
        'from_date',
        'to_date',
        'start_time',
        'end_time',
        'sort_order',
        'response_deadline',
        'response_deadline_unit',
        'buffer_days_before',
        'buffer_days_after',
        'number_of_spots',
        'remaining_spots',
        'studio_name',
        'studio_address',
        'street_number',
        'street_name',
        'studio_city',
        'studio_state',
        'postal_code',
        'studio_country',
        'google_maps_link',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'start_time' => 'string',
            'end_time' => 'string',
            'sort_order' => 'integer',
            'response_deadline' => 'integer',
            'buffer_days_before' => 'integer',
            'buffer_days_after' => 'integer',
            'number_of_spots' => 'integer',
            'remaining_spots' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tracksSpotCapacity(): bool
    {
        return $this->status === 'available' && (int) ($this->number_of_spots ?? 0) > 0;
    }

    public function hasAvailableSpots(): bool
    {
        if (! $this->tracksSpotCapacity()) {
            return true;
        }

        return (int) ($this->remaining_spots ?? 0) > 0;
    }

    /**
     * Reserve one paid spot. Returns false when capacity is exhausted.
     */
    public function consumeRemainingSpot(): bool
    {
        if (! $this->tracksSpotCapacity()) {
            return true;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () {
            /** @var self|null $spot */
            $spot = static::query()->whereKey($this->id)->lockForUpdate()->first();
            if (! $spot || ! $spot->tracksSpotCapacity()) {
                return true;
            }

            if ((int) $spot->remaining_spots <= 0) {
                return false;
            }

            $spot->decrement('remaining_spots');
            $this->remaining_spots = max(0, (int) $spot->remaining_spots - 1);

            return true;
        });
    }

    /**
     * Return one spot after a guest booking is cancelled.
     */
    public function releaseRemainingSpot(): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            /** @var self|null $spot */
            $spot = static::query()->whereKey($this->id)->lockForUpdate()->first();
            if (! $spot) {
                return;
            }

            $cap = (int) ($spot->number_of_spots ?? 0);
            if ($cap <= 0) {
                return;
            }

            $remaining = (int) ($spot->remaining_spots ?? 0);
            if ($remaining >= $cap) {
                return;
            }

            $spot->increment('remaining_spots');
            $this->remaining_spots = $remaining + 1;
        });
    }

    public static function remainingSpotsForCapacityChange(
        int $newCapacity,
        int $oldCapacity,
        int $oldRemaining
    ): int {
        if ($newCapacity <= 0) {
            return 0;
        }

        if ($oldCapacity <= 0) {
            return $newCapacity;
        }

        $booked = max(0, $oldCapacity - max(0, $oldRemaining));

        return max(0, $newCapacity - $booked);
    }

    public function isFull(): bool
    {
        return $this->tracksSpotCapacity() && (int) ($this->remaining_spots ?? 0) <= 0;
    }

    public function publicStatusLabel(): string
    {
        if ($this->status !== 'available') {
            return 'Planned';
        }

        if ($this->isFull()) {
            return 'Full';
        }

        return 'Available';
    }

    /**
     * Public spot-count line. Null when capacity is unlimited / not set.
     */
    public function listRemainingSpotsLabel(): ?string
    {
        if (! $this->tracksSpotCapacity()) {
            return null;
        }

        if ($this->isFull()) {
            return 'Available spots: Full';
        }

        return 'Available spots: '.max(0, (int) ($this->remaining_spots ?? 0));
    }

    public function bufferDaysBefore(): int
    {
        return $this->status === 'available'
            ? (int) ($this->buffer_days_before ?? 0)
            : 0;
    }

    public function bufferDaysAfter(): int
    {
        return $this->status === 'available'
            ? (int) ($this->buffer_days_after ?? 0)
            : 0;
    }

    public function blockedFromDate(): Carbon
    {
        return $this->from_date->copy()->startOfDay()->subDays($this->bufferDaysBefore());
    }

    public function blockedToDate(): Carbon
    {
        return $this->to_date->copy()->startOfDay()->addDays($this->bufferDaysAfter());
    }

    public function overlapsBlockedRange(Carbon $from, Carbon $to): bool
    {
        return $this->blockedFromDate()->lte($to) && $from->lte($this->blockedToDate());
    }

    /**
     * Full away window for home-studio calendars: buffer before + spot dates + buffer after.
     *
     * @return array{start_date: string, end_date: string}|null
     */
    public function awayBlockedPeriod(): ?array
    {
        if ($this->status !== 'available' || ! $this->from_date || ! $this->to_date) {
            return null;
        }

        return [
            'start_date' => $this->blockedFromDate()->format('Y-m-d'),
            'end_date' => $this->blockedToDate()->format('Y-m-d'),
        ];
    }

    /**
     * Travel buffer days only (excludes the guest spot's own from_date..to_date).
     *
     * @return list<array{start_date: string, end_date: string}>
     */
    public function bufferOnlyBlockedPeriods(): array
    {
        if ($this->status !== 'available' || ! $this->from_date || ! $this->to_date) {
            return [];
        }

        $periods = [];
        $before = $this->bufferDaysBefore();
        if ($before > 0) {
            $periods[] = [
                'start_date' => $this->from_date->copy()->subDays($before)->format('Y-m-d'),
                'end_date' => $this->from_date->copy()->subDay()->format('Y-m-d'),
            ];
        }

        $after = $this->bufferDaysAfter();
        if ($after > 0) {
            $periods[] = [
                'start_date' => $this->to_date->copy()->addDay()->format('Y-m-d'),
                'end_date' => $this->to_date->copy()->addDays($after)->format('Y-m-d'),
            ];
        }

        return $periods;
    }

    public function containsBookableDate(string $ymd): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd) || ! $this->from_date || ! $this->to_date) {
            return false;
        }

        $from = $this->from_date->format('Y-m-d');
        $to = $this->to_date->format('Y-m-d');

        return $ymd >= $from && $ymd <= $to;
    }

    /**
     * @return list<array{start_date: string, end_date: string}>
     */
    public static function awayBlockedPeriodsForArtist(int $artistUserId, ?int $exceptGuestSpotId = null): array
    {
        return static::query()
            ->where('user_id', $artistUserId)
            ->where('status', 'available')
            ->whereNotNull('from_date')
            ->whereNotNull('to_date')
            ->when($exceptGuestSpotId, fn ($query) => $query->where('id', '!=', $exceptGuestSpotId))
            ->orderBy('from_date')
            ->get()
            ->map(fn (self $spot) => $spot->awayBlockedPeriod())
            ->filter()
            ->values()
            ->all();
    }

    public function listStudioLabel(): ?string
    {
        if ($this->status !== 'available') {
            return null;
        }

        $name = trim((string) ($this->studio_name ?? ''));

        return $name !== '' ? $name : null;
    }

    public function listLocationLabel(): ?string
    {
        if ($this->status !== 'available') {
            return null;
        }

        $address = trim((string) ($this->studio_address ?? ''));
        if ($address !== '') {
            return $address;
        }

        $street = trim(trim((string) ($this->street_number ?? '')).' '.trim((string) ($this->street_name ?? '')));
        $cityLine = trim(implode(', ', array_filter([
            trim((string) ($this->studio_city ?? '')),
            trim((string) ($this->studio_state ?? '')),
            trim((string) ($this->postal_code ?? '')),
        ], fn (string $part) => $part !== '')));
        $country = trim((string) ($this->studio_country ?? ''));

        $parts = array_values(array_filter([$street, $cityLine, $country], fn (string $part) => $part !== ''));

        return $parts !== [] ? implode(', ', $parts) : null;
    }

    public function listBufferLabel(): ?string
    {
        if ($this->status !== 'available') {
            return null;
        }

        $before = $this->bufferDaysBefore();
        $after = $this->bufferDaysAfter();
        $parts = [];

        if ($before > 0) {
            $parts[] = $before === 1 ? '1 day before' : "{$before} days before";
        }

        if ($after > 0) {
            $parts[] = $after === 1 ? '1 day after' : "{$after} days after";
        }

        return $parts !== [] ? 'Buffer: '.implode(', ', $parts) : null;
    }

    public function listAvailabilityTimeLabel(): ?string
    {
        if ($this->status !== 'available' || ! $this->start_time || ! $this->end_time) {
            return null;
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->format('g:i A').' – '.$end->format('g:i A');
    }

    public function hasListDetails(): bool
    {
        return $this->status === 'available'
            && ($this->listStudioLabel()
                || $this->listLocationLabel()
                || $this->listBufferLabel()
                || $this->listAvailabilityTimeLabel()
                || $this->listRemainingSpotsLabel());
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'city' => $this->city,
            'country' => $this->country,
            'from_date' => $this->from_date?->format('Y-m-d'),
            'from_label' => $this->from_date?->format('M j, Y'),
            'to_date' => $this->to_date?->format('Y-m-d'),
            'to_label' => $this->to_date?->format('M j, Y'),
            'start_time' => $this->timeInputValue($this->start_time),
            'end_time' => $this->timeInputValue($this->end_time),
            'list_availability_time' => $this->listAvailabilityTimeLabel(),
            'sort_order' => $this->sort_order,
            'response_deadline' => $this->response_deadline,
            'response_deadline_unit' => $this->response_deadline_unit ?? 'hours',
            'buffer_days_before' => $this->buffer_days_before ?? 0,
            'buffer_days_after' => $this->buffer_days_after ?? 0,
            'number_of_spots' => $this->number_of_spots ?? 0,
            'remaining_spots' => $this->remaining_spots ?? 0,
            'list_remaining_spots' => $this->listRemainingSpotsLabel(),
            'guest_studio_name' => $this->studio_name,
            'guest_studio_address' => $this->studio_address,
            'guest_street_number' => $this->street_number,
            'guest_street_name' => $this->street_name,
            'guest_studio_city' => $this->studio_city,
            'guest_studio_state' => $this->studio_state,
            'guest_postal_code' => $this->postal_code,
            'guest_studio_country' => $this->studio_country,
            'guest_google_maps_link' => $this->google_maps_link,
            'guest_latitude' => $this->latitude,
            'guest_longitude' => $this->longitude,
            'list_studio' => $this->listStudioLabel(),
            'list_location' => $this->listLocationLabel(),
            'list_buffer' => $this->listBufferLabel(),
            'has_list_details' => $this->hasListDetails(),
        ];
    }

    private function timeInputValue(?string $time): ?string
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        return substr(trim($time), 0, 5);
    }
}
