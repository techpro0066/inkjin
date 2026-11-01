<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPayout extends Model
{
    public const TYPE_ARTIST = 'artist_account';

    public const TYPE_STUDIO = 'studio_account';

    public const TYPE_INKJIN = 'inkjin_account';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_DISCONNECTED = 'disconnected';

    protected $fillable = [
        'user_id',
        'status',
        'is_active',
        'stripe_account_id',
        'stripe_requirement',
        'stripe_requirement_email_sent_at',
        'payout_setup_reminder_sent_at',
        'payout_bank_country',
        'payout_waiting_list_country',
        'payout_waiting_list_at',
        'payout_mode',
        'connected_at',
        'disconnected_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'stripe_requirement' => 'boolean',
        'stripe_requirement_email_sent_at' => 'datetime',
        'payout_setup_reminder_sent_at' => 'datetime',
        'payout_waiting_list_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function activeForUser(int $userId): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->active()
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Previously used Stripe payout rows (e.g. after changing bank country).
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function disconnectedForUser(int $userId)
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('is_active', false)
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->where(function ($query) {
                $query->where('status', self::STATUS_DISCONNECTED)
                    ->orWhereNotNull('disconnected_at');
            })
            ->orderByDesc('disconnected_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Create or update the active payout row for a user.
     *
     * @param  array<string, mixed>  $attrs
     */
    public static function upsertActiveForUser(int $userId, array $attrs = []): self
    {
        $payout = self::activeForUser($userId);

        if (! $payout) {
            $payout = new self([
                'user_id' => $userId,
                'status' => self::STATUS_DRAFT,
                'is_active' => true,
                'payout_mode' => 'automatic',
            ]);
        }

        $payout->fill($attrs);
        $payout->user_id = $userId;
        $payout->is_active = true;

        if (filled($payout->stripe_account_id) && empty($payout->connected_at)) {
            $payout->connected_at = now();
        }

        if (($payout->status ?? null) === self::STATUS_DISCONNECTED && filled($payout->stripe_account_id)) {
            $payout->status = self::STATUS_PENDING;
            $payout->disconnected_at = null;
        }

        $payout->save();

        return $payout->fresh() ?? $payout;
    }

    /**
     * Mark every active payout row inactive. Connected Stripe rows become disconnected.
     */
    public static function deactivateActiveForUser(int $userId): void
    {
        static::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('id')
            ->each(function (self $payout): void {
                $payout->is_active = false;
                if (filled($payout->stripe_account_id)) {
                    $payout->status = self::STATUS_DISCONNECTED;
                    $payout->disconnected_at = now();
                }
                $payout->save();
            });
    }

    /**
     * Incomplete change-account draft (inactive until Stripe finishes).
     */
    public static function pendingChangeForUser(int $userId, ?string $country = null): ?self
    {
        $query = static::query()
            ->where('user_id', $userId)
            ->where('is_active', false)
            ->whereIn('status', [self::STATUS_DRAFT, self::STATUS_PENDING])
            ->whereNull('disconnected_at');

        if ($country !== null && $country !== '') {
            $query->where('payout_bank_country', strtoupper($country));
        }

        return $query->orderByDesc('id')->first();
    }

    /**
     * Prepare an inactive draft for a new Stripe account. Does not touch the active payout.
     *
     * @param  array<string, mixed>  $attrs
     */
    public static function preparePendingChangeForUser(int $userId, string $country, array $attrs = []): self
    {
        $country = strtoupper($country);
        $existing = self::pendingChangeForUser($userId, $country);
        if ($existing) {
            return $existing;
        }

        // Reuse an incomplete draft with no Stripe account yet (e.g. user changed country again).
        $orphan = static::query()
            ->where('user_id', $userId)
            ->where('is_active', false)
            ->whereIn('status', [self::STATUS_DRAFT, self::STATUS_PENDING])
            ->whereNull('disconnected_at')
            ->where(function ($query) {
                $query->whereNull('stripe_account_id')->orWhere('stripe_account_id', '');
            })
            ->orderByDesc('id')
            ->first();

        if ($orphan) {
            $orphan->fill($attrs);
            $orphan->payout_bank_country = $country;
            $orphan->payout_waiting_list_country = null;
            $orphan->payout_waiting_list_at = null;
            $orphan->is_active = false;
            $orphan->disconnected_at = null;
            $orphan->save();

            return $orphan->fresh() ?? $orphan;
        }

        $active = self::activeForUser($userId);
        $payout = new self([
            'user_id' => $userId,
            'status' => self::STATUS_DRAFT,
            'is_active' => false,
            'payout_mode' => $attrs['payout_mode'] ?? $active?->payout_mode ?: 'automatic',
            'payout_bank_country' => $country,
        ]);
        $payout->fill($attrs);
        $payout->user_id = $userId;
        $payout->is_active = false;
        $payout->payout_bank_country = $country;
        $payout->disconnected_at = null;
        $payout->stripe_account_id = $attrs['stripe_account_id'] ?? null;
        $payout->connected_at = filled($payout->stripe_account_id) ? ($attrs['connected_at'] ?? now()) : null;
        $payout->save();

        return $payout->fresh() ?? $payout;
    }

    /**
     * After Stripe onboarding finishes on a pending draft: activate it and disconnect the current one.
     */
    public static function promotePendingChangeForUser(int $userId, int $payoutId): self
    {
        $payout = static::query()
            ->where('user_id', $userId)
            ->where('id', $payoutId)
            ->where('is_active', false)
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->first();

        if (! $payout) {
            throw new \InvalidArgumentException('That payout account is not ready to activate.');
        }

        self::deactivateActiveForUser($userId);

        static::query()
            ->where('user_id', $userId)
            ->where('id', '!=', $payoutId)
            ->where('is_active', false)
            ->whereIn('status', [self::STATUS_DRAFT, self::STATUS_PENDING])
            ->whereNull('disconnected_at')
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->orderBy('id')
            ->each(function (self $other): void {
                $other->status = self::STATUS_DISCONNECTED;
                $other->disconnected_at = now();
                $other->save();
            });

        $payout->is_active = true;
        $payout->disconnected_at = null;
        $payout->status = ! empty($payout->stripe_requirement)
            ? self::STATUS_PENDING
            : self::STATUS_APPROVED;
        if (empty($payout->connected_at)) {
            $payout->connected_at = now();
        }
        $payout->save();

        return $payout->fresh() ?? $payout;
    }

    /**
     * Make a previously disconnected payout the active one again.
     */
    public static function reactivateForUser(int $userId, int $payoutId): self
    {
        $payout = static::query()
            ->where('user_id', $userId)
            ->where('id', $payoutId)
            ->where('is_active', false)
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->first();

        if (! $payout) {
            throw new \InvalidArgumentException('That payout account is not available to activate.');
        }

        self::deactivateActiveForUser($userId);

        $payout->is_active = true;
        $payout->disconnected_at = null;
        if (($payout->status ?? null) === self::STATUS_DISCONNECTED) {
            $payout->status = ! empty($payout->stripe_requirement)
                ? self::STATUS_PENDING
                : self::STATUS_APPROVED;
        }
        if (filled($payout->stripe_account_id) && empty($payout->connected_at)) {
            $payout->connected_at = now();
        }
        $payout->save();

        return $payout->fresh() ?? $payout;
    }

    /**
     * Disconnect the current active payout and open a new active row (e.g. new bank country).
     *
     * @param  array<string, mixed>  $attrs
     */
    public static function startNewActiveForUser(int $userId, array $attrs = []): self
    {
        $previous = self::activeForUser($userId);

        self::deactivateActiveForUser($userId);

        $payout = new self([
            'user_id' => $userId,
            'status' => self::STATUS_DRAFT,
            'is_active' => true,
            'payout_mode' => $previous?->payout_mode ?: 'automatic',
        ]);

        $payout->fill($attrs);
        $payout->user_id = $userId;
        $payout->is_active = true;
        $payout->stripe_account_id = $attrs['stripe_account_id'] ?? null;
        $payout->connected_at = filled($payout->stripe_account_id) ? ($attrs['connected_at'] ?? now()) : null;
        $payout->disconnected_at = null;
        $payout->save();

        return $payout->fresh() ?? $payout;
    }
}
