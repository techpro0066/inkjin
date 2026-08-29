<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDetail extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'display_name',
        'mobile_number',
        'tattoo_styles',
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
        'payout_bank_country',
        'payout_waiting_list_country',
        'payout_waiting_list_at',
        'current_step',
        'completed_steps',
        'scheduling_type',
        'booking_fee_type',
        'payment_type',
        'studio_id',
        'payment_status',
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
        'design_whats_included',
        'design_whats_included_is_active',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'tattoo_styles' => 'array',
        'social_links' => 'array',
        'google_calendar_token' => 'array',
        'instagram_access_token' => 'encrypted',
        'instagram_token_expires_at' => 'datetime',
        'instagram_connected_at' => 'datetime',
        'design_whats_included' => 'array',
        'design_whats_included_is_active' => 'boolean',
        'stripe_requirement' => 'boolean',
        'color_percent' => 'float',
        'customize_page_notice_dismissed' => 'boolean',
        'display_policies' => 'boolean',
        'display_tagline' => 'boolean',
        'display_bio' => 'boolean',
        'display_guest_spots' => 'boolean',
        'display_faq' => 'boolean',
        'require_consultation' => 'boolean',
        'require_gap_between_consultation_tattoo' => 'boolean',
        'payout_waiting_list_at' => 'datetime',
        'payout_setup_reminder_sent_at' => 'datetime',
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
     * Studio location as separate lines for checkout summaries.
     *
     * @return list<string>
     */
    public function studioLocationLines(): array
    {
        $studioLine = $this->studioNameWithCityCountry();

        $streetLine = trim(trim((string) ($this->street_number ?? '')).' '.trim((string) ($this->street_name ?? '')));
        if ($streetLine === '') {
            $streetLine = trim((string) ($this->studio_address ?? ''));
        }

        $postalCode = trim((string) ($this->postal_code ?? ''));

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
        return implode(', ', array_values(array_filter([
            trim((string) ($this->studio_name ?? '')),
            trim((string) ($this->city ?? '')),
            trim((string) ($this->country ?? '')),
        ], fn (string $part) => $part !== '')));
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
