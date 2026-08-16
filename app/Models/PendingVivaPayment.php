<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingVivaPayment extends Model
{
    public const FLOW_PUBLIC_BOOKING = 'public_booking';

    public const FLOW_MANAGED_REQUEST = 'managed_request';

    public const FLOW_CUSTOM_REQUEST = 'custom_request';

    public const FLOW_PAYMENT_LINK = 'payment_link';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'viva_order_code',
        'viva_transaction_id',
        'flow',
        'reference_id',
        'artist_user_id',
        'client_user_id',
        'amount_cents',
        'currency',
        'merchant_trns',
        'status',
        'metadata',
        'paid_at',
        'expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class, 'reference_id');
    }

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class, 'reference_id');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->expires_at->isFuture();
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
