<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\InkJinTattoo;

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
        'rescheduled_from_booking_id',
        'rescheduled_by',
        'rescheduled_at',
        'reschedule_reason',
        'reschedule_count',
        'reschedule_limit',
        'payment_intent_id',
        'payment_status',
        'deposit_amount',
        'full_amount_paid',
        'platform_fee',
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
        'platform_fee_refunded',
        'questions_answers',
        'notes',
        'completed_at',
        'completion_notes',
        'no_show_marked_at',
        'action_history',
        'reminder_sent_at',
        'google_calendar_event_id',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'consultation_date' => 'date',
        'cancelled_at' => 'datetime',
        'rescheduled_at' => 'datetime',
        'cancellation_deadline' => 'datetime',
        'deposit_released_at' => 'datetime',
        'remaining_amount_released_at' => 'datetime',
        'completion_code_entered_at' => 'datetime',
        'refunded_at' => 'datetime',
        'completed_at' => 'datetime',
        'no_show_marked_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'deposit_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'total_amount_paid' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'full_amount_paid' => 'boolean',
        'has_consultation' => 'boolean',
        'consultation_completed' => 'boolean',
        'deposit_released' => 'boolean',
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
        return $this->belongsTo(InkJinTattoo::class, 'tattoo_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'rescheduled_from_booking_id');
    }

    public function rescheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rescheduled_by');
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

    // Helper methods
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isRescheduled(): bool
    {
        return $this->status === 'rescheduled';
    }
}
