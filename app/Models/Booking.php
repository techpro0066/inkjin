<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\ArtistDesign;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'artist_user_id',
        'tattoo_id',
        'booking_type',
        'custom_tattoo_details',
        'booking_date',
        'start_time_utc',
        'end_time_utc',
        'timezone',
        'has_consultation',
        'consultation_date',
        'consultation_start_time_utc',
        'consultation_end_time_utc',
        'consultation_completed',
        'status',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'cancellation_deadline',
        'cancellation_window_hours',
        'payment_intent_id',
        'payment_status',
        'deposit_amount',
        'full_amount_paid',
        'platform_fee',
        'tax_amount',
        'tax_rate',
        'tax_country',
        'tax_label',
        'total_amount_paid',
        'currency',
        'deposit_released',
        'deposit_released_at',
        'remaining_amount_released',
        'remaining_amount_released_at',
        'completion_code',
        'completion_code_entered_at',
        'refund_amount',
        'refund_intent_id',
        'refunded_at',
        'refund_reason',
        'refund_status',
        'deposit_forfeited',
        'platform_fee_refunded',
        'cancellation_initiated_at',
        'cancellation_type',
        'questions_answers',
        'notes',
        'completed_at',
        'completion_notes',
        'no_show_marked_at',
        'action_history',
        'reminder_sent_at',
        'google_calendar_event_id',
        'google_meet_link',
        'consultation_timing_type',
        'consultation_booking_id',
        'rescheduled_from_booking_id',
        'rescheduled_by',
        'rescheduled_at',
        'reschedule_reason',
        'reschedule_count',
        'reschedule_limit',
        'reschedule_status',
        'reschedule_requested_by',
        'pay_artist',
        'payment_provider',
        'viva_order_code',
        'viva_transaction_id',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'consultation_date' => 'date',
        'cancelled_at' => 'datetime',
        'cancellation_initiated_at' => 'datetime',
        'cancellation_deadline' => 'datetime',
        'deposit_released_at' => 'datetime',
        'remaining_amount_released_at' => 'datetime',
        'completion_code_entered_at' => 'datetime',
        'refunded_at' => 'datetime',
        'completed_at' => 'datetime',
        'no_show_marked_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'rescheduled_at' => 'datetime',
        'deposit_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total_amount_paid' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'deposit_forfeited' => 'decimal:2',
        'full_amount_paid' => 'boolean',
        'has_consultation' => 'boolean',
        'consultation_completed' => 'boolean',
        'deposit_released' => 'boolean',
        'pay_artist' => 'boolean',
        'remaining_amount_released' => 'boolean',
        'platform_fee_refunded' => 'boolean',
        'questions_answers' => 'array',
        'custom_tattoo_details' => 'array',
        'action_history' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_user_id');
    }

    public function tattoo(): BelongsTo
    {
        return $this->belongsTo(ArtistDesign::class, 'tattoo_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function artistPayout(): HasOne
    {
        return $this->hasOne(ArtistPayout::class);
    }


    // Scopes
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'confirmed')
            ->where('booking_date', '>=', now()->toDateString());
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeBetweenUsers($query, int $clientId, int $artistId)
    {
        return $query->where('user_id', $clientId)
            ->where('artist_user_id', $artistId);
    }

    public static function hasOpenChatBetween(int $clientId, int $artistId): bool
    {
        return static::query()
            ->open()
            ->betweenUsers($clientId, $artistId)
            ->exists();
    }

    public static function latestOpenBetween(int $clientId, int $artistId): ?self
    {
        return static::query()
            ->open()
            ->betweenUsers($clientId, $artistId)
            ->orderByDesc('booking_date')
            ->first();
    }

    public function isOpenForChat(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true);
    }

    // Helper methods
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCustomBooking(): bool
    {
        return $this->booking_type === 'custom' || $this->tattoo_id === null;
    }

    public function isCustomRequestBooking(): bool
    {
        $details = is_array($this->custom_tattoo_details) ? $this->custom_tattoo_details : [];

        if (($details['custom_request_id'] ?? null) !== null) {
            return true;
        }

        return $this->booking_type === 'custom' && $this->tattoo_id === null;
    }

    public function referenceLabel(): string
    {
        if ($this->isCustomRequestBooking()) {
            $details = is_array($this->custom_tattoo_details) ? $this->custom_tattoo_details : [];
            $id = (int) ($details['custom_request_id'] ?? $this->id);

            return 'INK-CR-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
        }

        return 'INK-FL-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function displayTitle(): string
    {
        if ($this->tattoo) {
            return (string) $this->tattoo->title;
        }

        $details = is_array($this->custom_tattoo_details) ? $this->custom_tattoo_details : [];
        $reference = trim((string) ($details['reference'] ?? ''));

        return $reference !== '' ? 'Custom · '.$reference : 'Custom tattoo';
    }

    public function quoteAmount(): float
    {
        if ($this->tattoo) {
            return (float) ($this->tattoo->min_price ?? 0);
        }

        $details = is_array($this->custom_tattoo_details) ? $this->custom_tattoo_details : [];

        return max(0, (float) ($details['estimated_price'] ?? 0));
    }

    /**
     * @return array{min: float, max: float}
     */
    public function estimatedBalanceRange(): array
    {
        $deposit = (float) ($this->deposit_amount ?? 0);

        if ($this->tattoo) {
            $minPrice = (float) ($this->tattoo->min_price ?? 0);
            $maxPrice = (float) ($this->tattoo->max_price ?? 0);

            return [
                'min' => max(0, round($minPrice - $deposit, 2)),
                'max' => max(0, round($maxPrice - $deposit, 2)),
            ];
        }

        $details = is_array($this->custom_tattoo_details) ? $this->custom_tattoo_details : [];
        $price = max(0, (float) ($details['estimated_price'] ?? 0));
        $balance = max(0, round($price - $deposit, 2));

        return ['min' => $balance, 'max' => $balance];
    }

    public function estimatedBalanceLabel(): string
    {
        $range = $this->estimatedBalanceRange();
        $format = static fn (float $amount): string => '€'.number_format($amount, 2);

        if ($range['min'] === $range['max']) {
            return $format($range['min']);
        }

        return $format($range['min']).' - '.$format($range['max']);
    }

    public function remainingBalanceAmount(): float
    {
        return max(0, round($this->quoteAmount() - (float) ($this->deposit_amount ?? 0), 2));
    }

    // Accessor methods for booking and consultation times
    public function getBookingTimeAttribute()
    {
        if (!$this->start_time_utc || !$this->end_time_utc) {
            return null;
        }

        $timezone = $this->timezone ?? 'UTC';
        $startTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->booking_date->format('Y-m-d') . ' ' . $this->start_time_utc, 'UTC')
            ->setTimezone($timezone);
        $endTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->booking_date->format('Y-m-d') . ' ' . $this->end_time_utc, 'UTC')
            ->setTimezone($timezone);

        // For combined consultation, calculate tattoo session time separately
        if ($this->consultation_timing_type === 'combined' && $this->has_consultation) {
            // Tattoo session starts after consultation
            if ($this->consultation_end_time_utc) {
                $tattooStart = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->booking_date->format('Y-m-d') . ' ' . $this->consultation_end_time_utc, 'UTC')
                    ->setTimezone($timezone);
                return [
                    'start' => $tattooStart->format('g:i A'),
                    'end' => $endTime->format('g:i A'),
                    'start_datetime' => $tattooStart->toDateTimeString(),
                    'end_datetime' => $endTime->toDateTimeString(),
                    'duration_minutes' => $tattooStart->diffInMinutes($endTime),
                    'duration_hours' => round($tattooStart->diffInMinutes($endTime) / 60, 2),
                ];
            }
        }

        return [
            'start' => $startTime->format('g:i A'),
            'end' => $endTime->format('g:i A'),
            'start_datetime' => $startTime->toDateTimeString(),
            'end_datetime' => $endTime->toDateTimeString(),
            'duration_minutes' => $startTime->diffInMinutes($endTime),
            'duration_hours' => round($startTime->diffInMinutes($endTime) / 60, 2),
        ];
    }

    public function getConsultationTimeAttribute()
    {
        if (!$this->has_consultation || !$this->consultation_start_time_utc || !$this->consultation_end_time_utc) {
            return null;
        }

        $timezone = $this->timezone ?? 'UTC';
        $consultationDate = $this->consultation_date ?? $this->booking_date;
        $startTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $consultationDate->format('Y-m-d') . ' ' . $this->consultation_start_time_utc, 'UTC')
            ->setTimezone($timezone);
        $endTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $consultationDate->format('Y-m-d') . ' ' . $this->consultation_end_time_utc, 'UTC')
            ->setTimezone($timezone);

        return [
            'start' => $startTime->format('g:i A'),
            'end' => $endTime->format('g:i A'),
            'start_datetime' => $startTime->toDateTimeString(),
            'end_datetime' => $endTime->toDateTimeString(),
            'duration_minutes' => $startTime->diffInMinutes($endTime),
            'duration_hours' => round($startTime->diffInMinutes($endTime) / 60, 2),
        ];
    }
}
