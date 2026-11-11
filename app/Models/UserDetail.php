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
        'google_calendar_email',
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
        'travel_buffer_minutes',
        'require_consultation',
        'session_type',
        'session_duration_minutes',
        'consultation_timing',
        'require_gap_between_consultation_tattoo',
        'consultation_tattoo_gap_value',
        'consultation_tattoo_gap_unit',
        'current_step',
        'completed_steps',
        'scheduling_type',
        'booking_fee_type',
        'studio_id',
        'studio_revenue_artist_percent',
        'studio_relationship_type',
        'availability_status',
        'waitlist_signup_mode',
        'guest_waitlist_auto_notify',
        'personal_page_background_image',
        'personal_page_color',
        'page_look_mode',
        'page_look_scheme',
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
        'personal_page_tagline',
        'personal_page_description',
        'personal_page_name_alias',
        'display_policies',
        'policies_extra_note',
        'display_tagline',
        'display_bio',
        'display_guest_spots',
        'display_faq',
        'display_portfolio',
        'display_designs',
        'customize_page_notice_dismissed',
        'welcome_seen_at',
        'clients_nav_seen_at',
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
        'guest_waitlist_auto_notify' => 'boolean',
        'aftercare_content' => 'array',
        'consent_questions_seeded_at' => 'datetime',
        'color_percent' => 'float',
        'customize_page_notice_dismissed' => 'boolean',
        'welcome_seen_at' => 'datetime',
        'clients_nav_seen_at' => 'datetime',
        'instagram_bio_added_at' => 'datetime',
        'display_policies' => 'boolean',
        'display_tagline' => 'boolean',
        'display_bio' => 'boolean',
        'display_guest_spots' => 'boolean',
        'display_faq' => 'boolean',
        'display_portfolio' => 'boolean',
        'display_designs' => 'boolean',
        'page_bg_lum' => 'float',
        'page_box_opacity' => 'integer',
        'require_consultation' => 'boolean',
        'require_gap_between_consultation_tattoo' => 'boolean',
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

    public function activePayout(): ?UserPayout
    {
        if (! $this->user_id || ! Schema::hasTable('user_payouts')) {
            return null;
        }

        return UserPayout::activeForUser((int) $this->user_id);
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    /**
     * Google account email for the connected calendar (not the Bookpay login email).
     */
    public function resolvedGoogleCalendarEmail(): ?string
    {
        if (empty($this->google_calendar_token)) {
            return null;
        }

        $stored = trim((string) ($this->google_calendar_email ?? ''));
        if ($stored !== '' && str_contains($stored, '@')) {
            return $stored;
        }

        $calendarId = trim((string) ($this->google_calendar_id ?? ''));
        if ($calendarId !== '' && str_contains($calendarId, '@')) {
            return $calendarId;
        }

        return null;
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
     * Studio membership used for payout (payout=true + studio_id + revenue_split).
     */
    public function payoutUserStudio(): ?UserStudio
    {
        if (! $this->user_id || ! UserStudio::tableReady()) {
            return null;
        }

        return UserStudio::payoutLinkForUser((int) $this->user_id);
    }

    /**
     * Direct vs studio revenue split — from user_studios, not user_details.payment_type.
     */
    public function resolvedPayoutPaymentType(): string
    {
        return $this->payoutUserStudio() ? 'studio_account' : 'artist_account';
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

                // Column moved to user_studios — do not dirty a removed attribute.
                if (! Schema::hasColumn($this->getTable(), 'studio_name')) {
                    return [];
                }

                return ['studio_name' => $stored];
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
     * Full public address line for booking UIs (UserStudio preferred — same as custom request).
     */
    public function publicStudioAddressLine(): string
    {
        $link = $this->activeUserStudio();
        if ($link) {
            $street = trim(trim((string) ($link->street_number ?? '')).' '.trim((string) ($link->street_name ?? '')));
            if ($street === '') {
                $street = trim((string) ($link->studio_address ?? ''));
            }
            $cityLine = implode(', ', array_filter([
                trim((string) ($link->postal_code ?? '')),
                trim((string) ($link->city ?? '')),
                trim((string) ($link->country ?? '')),
            ]));

            return trim(implode(', ', array_filter([$street, $cityLine])));
        }

        return trim(implode(', ', array_filter([
            trim((string) ($this->attributes['studio_address'] ?? '')),
            trim((string) ($this->attributes['city'] ?? '')),
            trim((string) ($this->attributes['country'] ?? '')),
        ])));
    }

    /**
     * Google Maps link for public booking UIs; falls back to a maps search for the address.
     */
    public function publicStudioMapsLink(): string
    {
        $link = $this->activeUserStudio();
        $url = trim((string) ($link?->google_maps_link ?? ''));
        if ($url === '' && $link?->studio_id) {
            $url = trim((string) ($link->studio?->google_maps_link
                ?? Studio::query()->whereKey($link->studio_id)->value('google_maps_link')
                ?? ''));
        }
        if ($url === '') {
            $url = trim((string) ($this->attributes['google_maps_link'] ?? ''));
        }
        if ($url !== '') {
            return $url;
        }

        $query = $this->publicStudioAddressLine();
        if ($query === '') {
            $query = $this->resolvedStudioName();
        }
        if ($query === '') {
            return '';
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
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
