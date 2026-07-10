<?php

namespace App\Models;

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
        'is_active',
        'is_visible',
        'is_repeatable',
        'repeat_limit',
        'is_sensitive',
        'primary_style',
        'other_styles',
        'color',
        'tags',
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
            'is_visible' => 'boolean',
            'is_repeatable' => 'boolean',
            'repeat_limit' => 'integer',
            'is_sensitive' => 'boolean',
            'other_styles' => 'array',
            'tags' => 'array',
        ];
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
     * Width (min_size) and/or height (max_size) display label.
     */
    public function sizeLabel(): string
    {
        $width = (int) ($this->min_size ?? 0);
        $height = (int) ($this->max_size ?? 0);

        if ($width > 0 && $height > 0) {
            return $width.' × '.$height.' cm';
        }
        if ($width > 0) {
            return 'Width '.$width.' cm';
        }
        if ($height > 0) {
            return 'Height '.$height.' cm';
        }

        return '—';
    }

    public function canBeDeleted(): bool
    {
        if ($this->relationLoaded('booking_requests_count') || $this->relationLoaded('bookings_count')) {
            return ($this->booking_requests_count ?? 0) === 0
                && ($this->bookings_count ?? 0) === 0;
        }

        return ! $this->bookingRequests()->exists()
            && ! $this->bookings()->exists();
    }
}
