<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'description',
        'show_map',
        'show_artist_styles',
        'tattoo_styles',
        'extra_services',
        'accepts_walk_ins',
        'walk_ins_note',
        'show_guest_artists',
        'show_artists_flash',
        'show_artists_portfolio',
        'page_look_mode',
        'page_look_scheme',
        'page_banner',
        'page_bg_desktop',
        'page_bg_mobile',
        'page_bg_lum',
        'page_bg_avg',
        'page_box_color',
        'page_box_effect',
        'page_box_opacity',
        'page_title_mode',
        'page_title_color',
        'page_text_mode',
        'page_text_color',
        'page_button_mode',
        'page_button_color',
        'page_box_radius',
        'instagram_user_id',
        'instagram_username',
        'instagram_profile_picture',
        'instagram_access_token',
        'instagram_token_expires_at',
        'instagram_connected_at',
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
        'use_studio_consent_form',
        'allow_artist_own_consent',
        'stripe_setup_skipped_at',
        'welcome_seen_at',
    ];

    protected $casts = [
        'owner_is_artist' => 'boolean',
        'offers_revenue_split' => 'boolean',
        'offers_workstation_rent' => 'boolean',
        'default_revenue_artist_percent' => 'integer',
        'links' => 'array',
        'show_map' => 'boolean',
        'show_artist_styles' => 'boolean',
        'tattoo_styles' => 'array',
        'extra_services' => 'array',
        'accepts_walk_ins' => 'boolean',
        'show_guest_artists' => 'boolean',
        'show_artists_flash' => 'boolean',
        'show_artists_portfolio' => 'boolean',
        'page_bg_lum' => 'float',
        'page_box_opacity' => 'integer',
        'instagram_access_token' => 'encrypted',
        'instagram_token_expires_at' => 'datetime',
        'instagram_connected_at' => 'datetime',
        'use_studio_consent_form' => 'boolean',
        'allow_artist_own_consent' => 'boolean',
        'stripe_setup_skipped_at' => 'datetime',
        'welcome_seen_at' => 'datetime',
    ];

    public function portfolios(): HasMany
    {
        return $this->hasMany(StudioPortfolio::class);
    }

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
     * Artist ↔ studio membership rows.
     */
    public function userStudios(): HasMany
    {
        return $this->hasMany(UserStudio::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(StudioPayout::class);
    }

    public function activePayoutRelation(): HasOne
    {
        return $this->hasOne(StudioPayout::class)->where('is_active', true)->latestOfMany();
    }

    public function activePayout(): ?StudioPayout
    {
        if ($this->relationLoaded('activePayoutRelation')) {
            return $this->getRelation('activePayoutRelation');
        }

        return StudioPayout::activeForStudio((int) $this->id);
    }

    /**
     * Legacy read accessor — Stripe account now lives on studio_payouts.
     */
    public function getStripeAccountIdAttribute($value = null): ?string
    {
        if (array_key_exists('stripe_account_id', $this->attributes)
            && filled($this->attributes['stripe_account_id'] ?? null)) {
            return trim((string) $this->attributes['stripe_account_id']);
        }

        $accountId = trim((string) ($this->activePayout()?->stripe_account_id ?? ''));

        return $accountId !== '' ? $accountId : null;
    }

    /**
     * Legacy write accessor — persist on the active studio_payouts row.
     */
    public function setStripeAccountIdAttribute($value): void
    {
        if (! $this->id) {
            $this->attributes['stripe_account_id'] = $value;

            return;
        }

        $accountId = trim((string) ($value ?? ''));
        if ($accountId === '') {
            StudioPayout::deactivateActiveForStudio((int) $this->id);
            $this->unsetRelation('activePayoutRelation');

            return;
        }

        StudioPayout::upsertActiveForStudio((int) $this->id, [
            'stripe_account_id' => $accountId,
            'status' => StudioPayout::STATUS_APPROVED,
            'payout_bank_country' => $this->resolvePayoutBankCountryCode(),
        ]);
        $this->unsetRelation('activePayoutRelation');
    }

    /**
     * Legacy read accessor — requirement flag lives on studio_payouts.
     */
    public function getStripeRequirementAttribute($value = null): bool
    {
        if (array_key_exists('stripe_requirement', $this->attributes)) {
            return (bool) $this->attributes['stripe_requirement'];
        }

        return (bool) ($this->activePayout()?->stripe_requirement ?? false);
    }

    /**
     * Legacy write accessor — persist on the active studio_payouts row.
     */
    public function setStripeRequirementAttribute($value): void
    {
        if (! $this->id) {
            $this->attributes['stripe_requirement'] = (bool) $value;

            return;
        }

        $payout = StudioPayout::activeForStudio((int) $this->id);
        if (! $payout) {
            return;
        }

        $payout->stripe_requirement = (bool) $value;
        if ((bool) $value && ($payout->status ?? null) === StudioPayout::STATUS_APPROVED) {
            $payout->status = StudioPayout::STATUS_PENDING;
        }
        $payout->save();
        $this->unsetRelation('activePayoutRelation');
    }

    /**
     * Stripe Connect account id for this studio (active studio_payouts row).
     */
    public function resolveStripeAccountId(): ?string
    {
        $accountId = trim((string) ($this->activePayout()?->stripe_account_id ?? ''));

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

    public function hasSkippedStripeSetup(): bool
    {
        return $this->stripe_setup_skipped_at !== null;
    }

    /**
     * True when the studio accepted an artist payout invite but still must Connect or Skip Stripe.
     */
    public function needsInviteStripeChoice(): bool
    {
        if (! $this->user_id || $this->hasSkippedStripeSetup() || $this->hasStripeConnect()) {
            return false;
        }

        return $this->pendingPayoutInviteUserDetail() !== null;
    }

    /**
     * Artist user detail for the open payout invite that still needs Stripe Connect or Skip.
     */
    public function pendingPayoutInviteUserDetail(): ?UserDetail
    {
        if (! UserStudio::tableReady() || ! $this->id) {
            return null;
        }

        $link = UserStudio::query()
            ->with(['user.userDetail'])
            ->where('studio_id', (int) $this->id)
            ->where('payout', true)
            ->where(function ($query) {
                $query->where('status', UserStudio::STATUS_JOIN_PENDING)
                    ->orWhere(function ($activePending) {
                        $activePending->where('status', UserStudio::STATUS_ACTIVE)
                            ->where('connection_type', UserStudio::CONNECTION_NOT_CONNECTED);
                    });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        return $link?->user?->userDetail;
    }

    public function markStripeSetupSkipped(): void
    {
        if ($this->stripe_setup_skipped_at !== null) {
            return;
        }

        $this->forceFill(['stripe_setup_skipped_at' => now()])->save();
    }

    public function clearStripeSetupSkipped(): void
    {
        if ($this->stripe_setup_skipped_at === null) {
            return;
        }

        $this->forceFill(['stripe_setup_skipped_at' => null])->save();
    }

    /**
     * Signed invite URL to finish Connect / Skip Stripe after accepting an artist payout invite.
     */
    public function inviteStripeChoiceUrl(): ?string
    {
        if (! $this->needsInviteStripeChoice()) {
            return null;
        }

        $detail = $this->pendingPayoutInviteUserDetail();
        if (! $detail) {
            return null;
        }

        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'studio.payout-info.show',
            now()->addDays(30),
            ['userDetail' => $detail->id]
        );
    }

    /**
     * @deprecated Use hasStripeConnect() — kept for callers that checked manual bank details.
     */
    public function hasStoredBankDetails(): bool
    {
        return $this->hasStripeConnect();
    }

    private function resolvePayoutBankCountryCode(): ?string
    {
        $code = strtoupper(trim((string) ($this->country ?? '')));

        return strlen($code) === 2 ? $code : null;
    }
}
