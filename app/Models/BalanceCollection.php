<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceCollection extends Model
{
    public const TYPE_PAYMENT_LINK = 'payment_link';

    public const TYPE_PAID_IN_CASH = 'paid_in_cash';

    public const TYPE_NOT_SETTLED_YET = 'not_settled_yet';

    public const STATUS_PENDING = 'pending';

    public const STATUS_LINK_SENT = 'link_sent';

    public const STATUS_CASH_CONFIRMED = 'cash_confirmed';

    public const STATUS_SETTLEMENT_DEFERRED = 'settlement_deferred';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const PAYMENT_STATUS_PENDING = 'pending';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_FAILED = 'failed';

    protected $fillable = [
        'booking_id',
        'artist_user_id',
        'client_user_id',
        'collection_type',
        'amount',
        'currency',
        'platform_fee',
        'tax_amount',
        'tax_rate',
        'tax_country',
        'tax_label',
        'payment_link_id',
        'payment_link_code',
        'payment_link_url',
        'client_message',
        'completion_code',
        'completion_code_entered_at',
        'expected_payment_type',
        'expected_payment_date',
        'note',
        'payment_provider',
        'payment_method',
        'payment_intent_id',
        'viva_order_code',
        'viva_transaction_id',
        'payment_status',
        'paid_at',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'expected_payment_date' => 'date',
            'completion_code_entered_at' => 'datetime',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID
            || $this->payment_status === self::PAYMENT_STATUS_PAID
            || $this->paid_at !== null;
    }

    public function isUnsettledOpen(): bool
    {
        return $this->collection_type === self::TYPE_NOT_SETTLED_YET
            && $this->status === self::STATUS_SETTLEMENT_DEFERRED;
    }

    public function expectedDuePhrase(): string
    {
        $when = (string) $this->expected_payment_type;
        if ($when === 'no_date' || ! $this->expected_payment_date) {
            return 'no date set';
        }

        $today = now()->startOfDay();
        $due = $this->expected_payment_date->copy()->startOfDay();
        $days = (int) round($today->diffInDays($due, false));

        if ($days === 0) {
            return 'expected today';
        }
        if ($days === 1) {
            return 'expected tomorrow';
        }
        if ($days > 1) {
            return 'expected in '.$days.' days';
        }
        if ($days === -1) {
            return 'expected yesterday';
        }

        return 'expected '.abs($days).' days ago';
    }

    public function reminderNudge(string $firstName, string $amountLabel): ?string
    {
        if ((string) $this->expected_payment_type === 'no_date' || ! $this->expected_payment_date) {
            return null;
        }

        $today = now()->startOfDay();
        $due = $this->expected_payment_date->copy()->startOfDay();
        if ($today->lt($due)) {
            return null;
        }

        $span = match ((string) $this->expected_payment_type) {
            '3_days' => 3,
            '1_week' => 7,
            default => max(1, (int) round(($this->created_at?->copy()->startOfDay()->diffInDays($due, false) ?: 1))),
        };

        $name = trim($firstName);
        if ($name === '') {
            $name = 'Client';
        }
        $possessive = str_ends_with(strtolower($name), 's') ? $name."'" : $name."'s";

        return 'Day '.$span.' nudge: “'.$possessive.' '.$amountLabel.' was expected today — get a payment link, or mark cash?”';
    }
}
