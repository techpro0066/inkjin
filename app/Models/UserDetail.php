<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDetail extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
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
        'avatar',
        'currency', 
        'timezone',
        'date_time_format',
        'size_unit',
        'minimum_deposit_amount',
        'minimum_deposit_type',
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
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'tattoo_styles' => 'array',
        'social_links' => 'array',
        'google_calendar_token' => 'array',
        'require_consultation' => 'boolean',
        'require_gap_between_consultation_tattoo' => 'boolean',
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
}
