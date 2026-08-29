<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Studio extends Model
{
    protected $fillable = [
        'name',
        'email',
        'stripe_account_id',
        'stripe_requirement',
    ];

    protected $casts = [
        'stripe_requirement' => 'boolean',
    ];

    /**
     * Artists (user details) associated with this studio.
     */
    public function userDetails(): HasMany
    {
        return $this->hasMany(UserDetail::class);
    }

    /**
     * Stripe connected account id shared by artists linked to this studio (stored on user_details).
     */
    public function resolveStripeAccountId(): ?string
    {
        if (! empty($this->stripe_account_id)) {
            return $this->stripe_account_id;
        }

        return UserDetail::query()
            ->where('studio_id', $this->id)
            ->whereNotNull('stripe_account_id')
            ->value('stripe_account_id');
    }

    /**
     * Whether this studio has completed Stripe Connect onboarding (used for approve/decline emails).
     */
    public function hasStripeConnect(): bool
    {
        $accountId = $this->resolveStripeAccountId();
        if ($accountId === null || $accountId === '') {
            return false;
        }

        $stripeConnect = app(\App\Services\StripeConnectService::class);
        if (! $stripeConnect->isConfigured()) {
            return false;
        }

        try {
            return $stripeConnect->isOnboardingSubmitted($accountId);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @deprecated Use hasStripeConnect() — kept for callers that checked manual bank details.
     */
    public function hasStoredBankDetails(): bool
    {
        return $this->hasStripeConnect();
    }
}

