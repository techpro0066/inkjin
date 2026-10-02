<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Studio extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'username',
        'email',
        'image_url',
        'logo_url',
        'studio_type',
        'address',
        'street_name',
        'street_number',
        'city',
        'state',
        'postal_code',
        'country',
        'google_maps_link',
        'business_phone',
        'contact_email',
        'links',
        'owner_is_artist',
        'offers_revenue_split',
        'offers_workstation_rent',
        'default_revenue_artist_percent',
        'currency',
        'business_type',
        'tattooing_since',
        'timezone',
        'date_time_format',
        'size_unit',
        'stripe_account_id',
        'stripe_requirement',
    ];

    protected $casts = [
        'stripe_requirement' => 'boolean',
        'owner_is_artist' => 'boolean',
        'offers_revenue_split' => 'boolean',
        'offers_workstation_rent' => 'boolean',
        'default_revenue_artist_percent' => 'integer',
        'links' => 'array',
    ];

    /**
     * Studio owner account (users.role = studio).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Artists (user details) associated with this studio.
     */
    public function userDetails(): HasMany
    {
        return $this->hasMany(UserDetail::class);
    }

    /**
     * Stripe Connect account id for this studio (stored on studios.stripe_account_id only).
     * Do not fall back to linked artists — those hold the artist's own payout account.
     */
    public function resolveStripeAccountId(): ?string
    {
        $accountId = trim((string) ($this->stripe_account_id ?? ''));

        return $accountId !== '' ? $accountId : null;
    }

    /**
     * Whether this studio has completed Stripe Connect onboarding (used for approve/decline emails).
     */
    public function hasStripeConnect(): bool
    {
        $accountId = $this->resolveStripeAccountId();
        if ($accountId === null) {
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

