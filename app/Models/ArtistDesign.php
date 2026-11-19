<?php

namespace App\Models;

use App\Models\Scopes\NotDeletedScope;
use App\Support\DesignSessionDuration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtistDesign extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'image',
        'original_image',
        'image_display_mode',
        'is_active',
        'is_deleted',
        'is_visible',
        'is_repeatable',
        'repeat_limit',
        'is_sensitive',
        'primary_style',
        'other_styles',
        'suggested_placements',
        'color',
        'tags',
        'sort_order',
        'min_price',
        'max_price',
        'min_size',
        'max_size',
        'min_sessions',
        'max_sessions',
        'session_duration',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_deleted' => 'boolean',
            'is_visible' => 'boolean',
            'is_repeatable' => 'boolean',
            'repeat_limit' => 'integer',
            'is_sensitive' => 'boolean',
            'other_styles' => 'array',
            'suggested_placements' => 'array',
            'tags' => 'array',
        ];
    }

    protected $attributes = [
        'image_display_mode' => 'whole',
    ];

    public function getDisplayImageAttribute(): ?string
    {
        if ($this->image_display_mode === 'crop' && filled($this->image)) {
            return $this->image;
        }

        return $this->original_image ?: $this->image;
    }

    public function getDisplayImageFitAttribute(): string
    {
        return $this->image_display_mode === 'crop' ? 'cover' : 'contain';
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new NotDeletedScope);
    }

    /**
     * Display label for the public design page.
     * Empty selection shows "Anywhere".
     */
    public function getSuggestedPlacementAttribute(): string
    {
        $items = $this->suggested_placements;
        if (! is_array($items)) {
            return 'Anywhere';
        }

        $items = array_values(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $items
        )));

        return $items === [] ? 'Anywhere' : implode(', ', $items);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class, 'tattoo_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'tattoo_id');
    }

    public function scopeWithSoldOutState($query)
    {
        return $query->withCount([
            'bookings as active_bookings_count' => fn ($q) => $q->where('status', '!=', 'cancelled'),
            'bookingRequests as reserved_requests_count' => fn ($q) => $q->where('status', 'confirmed')->whereNull('booking_id'),
        ]);
    }

    public function effectiveRepeatLimit(): int
    {
        if (! $this->is_repeatable) {
            return 1;
        }

        return max(1, (int) ($this->repeat_limit ?? 1));
    }

    public function claimedBookingCount(): int
    {
        if ($this->active_bookings_count !== null && $this->reserved_requests_count !== null) {
            return (int) $this->active_bookings_count + (int) $this->reserved_requests_count;
        }

        return (int) $this->bookings()->where('status', '!=', 'cancelled')->count()
            + (int) $this->bookingRequests()->where('status', 'confirmed')->whereNull('booking_id')->count();
    }

    public function remainingBookingSlots(): int
    {
        return max(0, $this->effectiveRepeatLimit() - $this->claimedBookingCount());
    }

    public function isSoldOut(): bool
    {
        return $this->claimedBookingCount() >= $this->effectiveRepeatLimit();
    }

    /**
     * Minimum size display label using the artist's size unit (cm/in).
     */
    public function sizeLabel(?string $unit = null): string
    {
        $size = (int) ($this->min_size ?? 0);
        if ($size <= 0) {
            // Legacy designs may only have had height stored in max_size.
            $size = (int) ($this->max_size ?? 0);
        }
        if ($size <= 0) {
            return '—';
        }

        if ($unit === null) {
            $this->loadMissing('user.userDetail');
            $unit = $this->user?->userDetail?->size_unit;
        }

        $unit = in_array($unit, ['cm', 'in'], true) ? $unit : 'cm';

        return $size.' '.$unit;
    }

    public function sessionDurationMinutes(int $default = 120): int
    {
        return DesignSessionDuration::minutes(
            is_string($this->session_duration) ? $this->session_duration : null,
            $default
        );
    }

    public function sessionDurationLabel(): string
    {
        return DesignSessionDuration::label(
            is_string($this->session_duration) ? $this->session_duration : null
        );
    }

    public function scopeWithDeleted(Builder $query): Builder
    {
        return $query->withoutGlobalScope(NotDeletedScope::class);
    }

    public function scopeOnlyDeleted(Builder $query): Builder
    {
        return $query->withoutGlobalScope(NotDeletedScope::class)
            ->where($this->getTable().'.is_deleted', true);
    }

    /**
     * True when any booking or request still references this design (hard-delete unsafe).
     */
    public function hasLinkedBookingsOrRequests(): bool
    {
        if ($this->offsetExists('booking_requests_count') || $this->offsetExists('bookings_count')) {
            return ((int) ($this->booking_requests_count ?? 0)) > 0
                || ((int) ($this->bookings_count ?? 0)) > 0;
        }

        return $this->bookingRequests()->exists()
            || $this->bookings()->exists();
    }

    /**
     * @deprecated Use !hasLinkedBookingsOrRequests() — kept for older call sites.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->hasLinkedBookingsOrRequests();
    }

    /**
     * Soft-delete: hide from dashboard / public, keep row for history.
     */
    public function markDeleted(): void
    {
        $this->forceFill([
            'is_active' => false,
            'is_visible' => false,
            'is_deleted' => true,
        ])->save();
    }
}
