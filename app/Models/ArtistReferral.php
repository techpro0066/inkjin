<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtistReferral extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT_TO_ADMIN = 'sent_to_admin';

    public const STATUS_REWARDED = 'rewarded';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'referrer_user_id',
        'referred_user_id',
        'status',
        'qualified_booking_id',
        'qualified_at',
        'admin_notified_at',
        'reward_amount',
        'fee_waived',
        'reward_paid_at',
        'rejection_reason',
        'rejected_at',
        'stripe_transfer_id',
        'stripe_account_id',
        'reward_currency',
    ];

    protected $casts = [
        'qualified_at' => 'datetime',
        'admin_notified_at' => 'datetime',
        'reward_paid_at' => 'datetime',
        'rejected_at' => 'datetime',
        'reward_amount' => 'decimal:2',
        'fee_waived' => 'boolean',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function qualifiedBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'qualified_booking_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRewarded(): bool
    {
        return $this->status === self::STATUS_REWARDED;
    }

    public function isSentToAdmin(): bool
    {
        return $this->status === self::STATUS_SENT_TO_ADMIN;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
