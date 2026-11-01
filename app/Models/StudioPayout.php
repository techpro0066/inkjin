<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudioPayout extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DISCONNECTED = 'disconnected';

    protected $fillable = [
        'studio_id',
        'status',
        'is_active',
        'stripe_account_id',
        'stripe_requirement',
        'stripe_requirement_email_sent_at',
        'payout_bank_country',
        'connected_at',
        'disconnected_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'stripe_requirement' => 'boolean',
        'stripe_requirement_email_sent_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
    ];

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function activeForStudio(int $studioId): ?self
    {
        return static::query()
            ->where('studio_id', $studioId)
            ->active()
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function disconnectedForStudio(int $studioId)
    {
        return static::query()
            ->where('studio_id', $studioId)
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
     * @param  array<string, mixed>  $attrs
     */
    public static function upsertActiveForStudio(int $studioId, array $attrs = []): self
    {
        $payout = self::activeForStudio($studioId);

        if (! $payout) {
            $payout = new self([
                'studio_id' => $studioId,
                'status' => self::STATUS_DRAFT,
                'is_active' => true,
            ]);
        }

        $payout->fill($attrs);
        $payout->studio_id = $studioId;
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

    public static function deactivateActiveForStudio(int $studioId): void
    {
        static::query()
            ->where('studio_id', $studioId)
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
    public static function pendingChangeForStudio(int $studioId, ?string $country = null): ?self
    {
        $query = static::query()
            ->where('studio_id', $studioId)
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
    public static function preparePendingChangeForStudio(int $studioId, string $country, array $attrs = []): self
    {
        $country = strtoupper($country);
        $existing = self::pendingChangeForStudio($studioId, $country);
        if ($existing) {
            return $existing;
        }

        $orphan = static::query()
            ->where('studio_id', $studioId)
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
            $orphan->is_active = false;
            $orphan->disconnected_at = null;
            $orphan->save();

            return $orphan->fresh() ?? $orphan;
        }

        $payout = new self([
            'studio_id' => $studioId,
            'status' => self::STATUS_DRAFT,
            'is_active' => false,
            'payout_bank_country' => $country,
        ]);
        $payout->fill($attrs);
        $payout->studio_id = $studioId;
        $payout->is_active = false;
        $payout->payout_bank_country = $country;
        $payout->disconnected_at = null;
        $payout->stripe_account_id = $attrs['stripe_account_id'] ?? null;
        $payout->connected_at = filled($payout->stripe_account_id) ? ($attrs['connected_at'] ?? now()) : null;
        $payout->save();

        return $payout->fresh() ?? $payout;
    }

    /**
     * After Stripe finishes on a pending draft: activate it and disconnect the current one.
     */
    public static function promotePendingChangeForStudio(int $studioId, int $payoutId): self
    {
        $payout = static::query()
            ->where('studio_id', $studioId)
            ->where('id', $payoutId)
            ->where('is_active', false)
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->first();

        if (! $payout) {
            throw new \InvalidArgumentException('That payout account is not ready to activate.');
        }

        self::deactivateActiveForStudio($studioId);

        static::query()
            ->where('studio_id', $studioId)
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
     * @param  array<string, mixed>  $attrs
     */
    public static function startNewActiveForStudio(int $studioId, array $attrs = []): self
    {
        self::deactivateActiveForStudio($studioId);

        $payout = new self([
            'studio_id' => $studioId,
            'status' => self::STATUS_DRAFT,
            'is_active' => true,
        ]);

        $payout->fill($attrs);
        $payout->studio_id = $studioId;
        $payout->is_active = true;
        $payout->stripe_account_id = $attrs['stripe_account_id'] ?? null;
        $payout->connected_at = filled($payout->stripe_account_id) ? ($attrs['connected_at'] ?? now()) : null;
        $payout->disconnected_at = null;
        $payout->save();

        return $payout->fresh() ?? $payout;
    }

    public static function reactivateForStudio(int $studioId, int $payoutId): self
    {
        $payout = static::query()
            ->where('studio_id', $studioId)
            ->where('id', $payoutId)
            ->where('is_active', false)
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->first();

        if (! $payout) {
            throw new \InvalidArgumentException('That payout account is not available to activate.');
        }

        self::deactivateActiveForStudio($studioId);

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
}
