<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class UserDetail extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'display_name',
        'mobile_number',
        'tattoo_styles',
        'extra_services',
        'social_links',
        'country',
        'city',
        'studio_name',
        'studio_address',
        'street_name',
        'street_number',
        'state',
        'postal_code',
        'google_maps_link',
        'workspace_type',
        'google_calendar_token',
        'google_calendar_id',
        'instagram_user_id',
        'instagram_username',
        'instagram_profile_picture',
        'instagram_access_token',
        'instagram_token_expires_at',
        'instagram_connected_at',
        'avatar',
        'currency', 
        'timezone',
        'date_time_format',
        'size_unit',
        'pricing_type',
        'color_percent',
        'minimum_deposit_amount',
        'minimum_deposit_type',
        'hourly_rate',
        'half_day_rate',
        'full_day_rate',
        'cancellation_window',
        'reschedule_times',
        'session_buffer_period',
        'require_consultation',
        'session_type',
        'session_duration_minutes',
        'consultation_timing',
        'require_gap_between_consultation_tattoo',
        'consultation_tattoo_gap_value',
        'consultation_tattoo_gap_unit',
        'stripe_account_id',
        'stripe_requirement',
        'payout_setup_reminder_sent_at',
        'stripe_requirement_email_sent_at',
        'payout_bank_country',
        'payout_waiting_list_country',
        'payout_waiting_list_at',
        'current_step',
        'completed_steps',
        'scheduling_type',
        'booking_fee_type',
        'payment_type',
        'studio_id',
        'studio_revenue_artist_percent',
        'studio_relationship_type',
        'payment_status',
        'payout_mode',
        'availability_status',
        'personal_page_background_image',
        'personal_page_color',
        'personal_page_tagline',
        'personal_page_description',
        'personal_page_name_alias',
        'display_policies',
        'display_tagline',
        'display_bio',
        'display_guest_spots',
        'display_faq',
        'customize_page_notice_dismissed',
        'welcome_seen_at',
        'instagram_bio_added_at',
        'design_whats_included',
        'design_whats_included_is_active',
        'aftercare_send_automatically',
        'aftercare_content',
        'consent_questions_seeded_at',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'tattoo_styles' => 'array',
        'extra_services' => 'array',
        'social_links' => 'array',
        'google_calendar_token' => 'array',
        'instagram_access_token' => 'encrypted',
        'instagram_token_expires_at' => 'datetime',
        'instagram_connected_at' => 'datetime',
        'design_whats_included' => 'array',
        'design_whats_included_is_active' => 'boolean',
        'aftercare_send_automatically' => 'boolean',
        'aftercare_content' => 'array',
        'consent_questions_seeded_at' => 'datetime',
        'stripe_requirement' => 'boolean',
        'color_percent' => 'float',
        'customize_page_notice_dismissed' => 'boolean',
        'welcome_seen_at' => 'datetime',
        'instagram_bio_added_at' => 'datetime',
        'display_policies' => 'boolean',
        'display_tagline' => 'boolean',
        'display_bio' => 'boolean',
        'display_guest_spots' => 'boolean',
        'display_faq' => 'boolean',
        'require_consultation' => 'boolean',
        'require_gap_between_consultation_tattoo' => 'boolean',
        'payout_waiting_list_at' => 'datetime',
        'payout_setup_reminder_sent_at' => 'datetime',
        'stripe_requirement_email_sent_at' => 'datetime',
        'studio_revenue_artist_percent' => 'integer',
    ];

    public const STUDIO_RELATIONSHIP_TYPES = [
        'co_owner',
        'resident',
        'collective_member',
        'apprentice',
        'other',
    ];

    /**
     * Get the user that owns the user detail.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    /**
     * Active workplace / membership row (user_studios), when the table exists.
     */
    public function activeUserStudio(): ?UserStudio
    {
        if (! $this->user_id || ! UserStudio::tableReady()) {
            return null;
        }

        return UserStudio::activeForUser((int) $this->user_id);
    }

    /**
     * Studio membership used for payout (payout=true + studio_id).
     */
    public function payoutUserStudio(): ?UserStudio
    {
        if (! $this->user_id || ! UserStudio::tableReady()) {
            return null;
        }

        return UserStudio::payoutLinkForUser((int) $this->user_id);
    }

    /**
     * Resolved linked studio id: payout user_studios row, else legacy user_details.studio_id.
     */
    public function resolvedStudioId(): ?int
    {
        $link = $this->payoutUserStudio() ?? $this->activeUserStudio();
        if ($link?->studio_id) {
            return (int) $link->studio_id;
        }

        if (Schema::hasColumn($this->getTable(), 'studio_id') && ! empty($this->attributes['studio_id'] ?? null)) {
            return (int) $this->attributes['studio_id'];
        }

        return null;
    }

    /**
     * Prefer user_studios.studio_id when the legacy column is empty / removed.
     */
    protected function studioId(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value) {
                if ($value !== null && $value !== '') {
                    return (int) $value;
                }

                $link = $this->payoutUserStudio() ?? $this->activeUserStudio();

                return $link?->studio_id ? (int) $link->studio_id : null;
            },
            set: fn (mixed $value) => $value,
        );
    }

    /**
     * Prefer user_studios.revenue_split when legacy column is empty / removed.
     */
    protected function studioRevenueArtistPercent(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value) {
                if ($value !== null && $value !== '') {
                    return (int) $value;
                }

                $link = $this->payoutUserStudio() ?? $this->activeUserStudio();

                return $link?->revenue_split !== null ? (int) $link->revenue_split : null;
            },
            set: fn (mixed $value) => $value,
        );
    }

    /**
     * Canonical studio display name: studios.name when linked, else user_studios / user_details name.
     */
    public function resolvedStudioName(): string
    {
        $studioId = $this->resolvedStudioId();
        if ($studioId) {
            $studio = $this->relationLoaded('studio') && (int) ($this->studio?->id ?? 0) === $studioId
                ? $this->studio
                : Studio::query()->find($studioId);
            $fromStudio = trim((string) ($studio?->name ?? ''));
            if ($fromStudio !== '') {
                return $fromStudio;
            }
        }

        $link = $this->activeUserStudio();
        $fromLink = trim((string) ($link?->studio_name ?? ''));
        if ($fromLink !== '') {
            return $fromLink;
        }

        if (Schema::hasColumn($this->getTable(), 'studio_name')) {
            return trim((string) ($this->attributes['studio_name'] ?? ''));
        }

        return '';
    }

    /**
     * Prefer studios.name for linked artists; keep user_details.studio_name as fallback / denormalized copy.
     * Writes still persist user_details.studio_name when the column exists.
     */
    protected function studioName(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->resolvedStudioName() !== ''
                ? $this->resolvedStudioName()
                : $value,
            set: function (?string $value) {
                $name = is_string($value) ? trim($value) : '';
                $stored = $name !== '' ? $name : null;

                $studioId = $this->resolvedStudioId();
                if ($this->exists && $studioId && $stored !== null) {
                    $studio = Studio::query()->find($studioId);

                    if ($studio && (int) ($studio->user_id ?? 0) === (int) ($this->user_id ?? 0)
                        && trim((string) ($studio->name ?? '')) !== $stored) {
                        $studio->forceFill(['name' => $stored])->save();
                    }
                }

                return $stored;
            },
        );
    }

    /**
     * Studio location as separate lines for checkout summaries.
     *
     * @return list<string>
     */
    public function studioLocationLines(): array
    {
        $link = $this->activeUserStudio();
        $studioLine = $this->studioNameWithCityCountry();

        $streetNumber = $link?->street_number ?? ($this->attributes['street_number'] ?? null);
        $streetName = $link?->street_name ?? ($this->attributes['street_name'] ?? null);
        $studioAddress = $link?->studio_address ?? ($this->attributes['studio_address'] ?? null);
        $postalCode = $link?->postal_code ?? ($this->attributes['postal_code'] ?? null);

        $streetLine = trim(trim((string) $streetNumber).' '.trim((string) $streetName));
        if ($streetLine === '') {
            $streetLine = trim((string) $studioAddress);
        }

        $postalCode = trim((string) $postalCode);

        return array_values(array_filter([
            $studioLine,
            $streetLine,
            $postalCode,
        ], fn (string $line) => $line !== ''));
    }

    /**
     * Compact studio label: "Studio Name, City, Country".
     */
    public function studioNameWithCityCountry(): string
    {
        $link = $this->activeUserStudio();

        return implode(', ', array_values(array_filter([
            $this->resolvedStudioName(),
            trim((string) ($link?->city ?? $this->attributes['city'] ?? '')),
            trim((string) ($link?->country ?? $this->attributes['country'] ?? '')),
        ], fn (string $part) => $part !== '')));
    }

    /**
     * Artist % for studio payout: prefer user_studios.revenue_split.
     */
    public function resolvedStudioRevenueArtistPercent(int $default = 50): int
    {
        $link = $this->payoutUserStudio() ?? $this->activeUserStudio();
        if ($link && $link->revenue_split !== null) {
            return max(0, min(100, (int) $link->revenue_split));
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'studio_revenue_artist_percent')
            && isset($this->attributes['studio_revenue_artist_percent'])
            && $this->attributes['studio_revenue_artist_percent'] !== null) {
            return max(0, min(100, (int) $this->attributes['studio_revenue_artist_percent']));
        }

        return max(0, min(100, $default));
    }

    /**
     * Artist name for public / client-facing surfaces (respects Content & Style name choice).
     */
    public function publicDisplayName(): string
    {
        $user = $this->relationLoaded('user') ? $this->user : $this->user()->first();
        $fullName = trim(($user?->first_name ?? '').' '.($user?->last_name ?? ''));
        $username = trim((string) ($this->user_name ?? ''));
        $displayName = trim((string) ($this->display_name ?? ''));
        $alias = in_array($this->personal_page_name_alias, ['full', 'username', 'display_name'], true)
            ? $this->personal_page_name_alias
            : 'full';

        return match ($alias) {
            'username' => $username !== '' ? $username : ($fullName !== '' ? $fullName : 'Artist'),
            'display_name' => $displayName !== ''
                ? $displayName
                : ($fullName !== '' ? $fullName : ($username !== '' ? $username : 'Artist')),
            default => $fullName !== '' ? $fullName : ($username !== '' ? $username : 'Artist'),
        };
    }

    /**
     * Initials derived from {@see publicDisplayName()} for avatars on public pages.
     */
    public function publicDisplayInitials(): string
    {
        $name = $this->publicDisplayName();
        $parts = preg_split('/[\s._\-]+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) >= 2) {
            $initials = mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));

            return $initials !== '' ? $initials : 'AR';
        }

        if (count($parts) === 1) {
            $initials = mb_strtoupper(mb_substr($parts[0], 0, min(2, mb_strlen($parts[0]))));

            return $initials !== '' ? $initials : 'AR';
        }

        return 'AR';
    }

    /**
     * Whether the public About / bio block should render (toggle on and bio text set).
     */
    public function shouldDisplayBio(): bool
    {
        if (! ($this->display_bio ?? false)) {
            return false;
        }

        return trim((string) ($this->personal_page_description ?? '')) !== '';
    }

    /**
     * @return list<string>
     */
    public function activeDesignWhatsIncludedItems(): array
    {
        if (! $this->design_whats_included_is_active) {
            return [];
        }

        $items = is_array($this->design_whats_included) ? $this->design_whats_included : [];

        return array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $items
        ), fn (string $item) => $item !== ''));
    }
}
