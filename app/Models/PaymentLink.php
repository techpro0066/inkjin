<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLink extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAID = 'paid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'artist_id',
        'status',
        'code',
        'url',
        'amount',
        'payment_type',
        'title',
        'date_time',
        'session_duration',
        'total_price',
        'due_amount',
        'expires',
        'expires_at',
        'client_message',
        'scheduling_type',
        'payer_name',
        'payer_email',
        'payer_phone',
        'slot_ymd',
        'slot_time',
        'payment_intent_id',
        'viva_order_code',
        'payment_method',
        'paid_at',
        'expiry_reminder_sent_at',
        'booking_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'due_amount' => 'decimal:2',
            'date_time' => 'datetime',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'expiry_reminder_sent_at' => 'datetime',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || $this->paid_at !== null;
    }

    public function listStatus(): string
    {
        if ($this->isPaid()) {
            return 'paid';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        return 'pending';
    }

    public function publicUrl(): string
    {
        $url = trim((string) ($this->url ?? ''));

        return $url !== '' ? $url : route('public.payment-link', ['code' => $this->code]);
    }

    public function depositAmount(): float
    {
        return round((float) $this->amount, 2);
    }

    public function totalAmount(): float
    {
        if ($this->payment_type === 'deposit' && $this->total_price !== null) {
            return round((float) $this->total_price, 2);
        }

        return $this->depositAmount();
    }

    public function isExpired(): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function sessionDetailsAreOpen(): bool
    {
        if (! $this->isPaid()) {
            return false;
        }

        $booking = $this->relationLoaded('booking') ? $this->booking : $this->booking()->first();
        $sessionStart = $booking?->sessionStartUtc();
        if ($sessionStart) {
            return $sessionStart->isFuture();
        }

        return $this->expires_at === null || ! $this->expires_at->isPast();
    }

    public function sessionDetailsExpiresAt(): \Carbon\CarbonInterface
    {
        $booking = $this->relationLoaded('booking') ? $this->booking : $this->booking()->first();
        $sessionStart = $booking?->sessionStartUtc();
        if ($sessionStart && $sessionStart->isFuture()) {
            return $sessionStart->copy();
        }

        if ($this->expires_at) {
            return $this->expires_at->copy();
        }

        return now()->addHour();
    }
}
