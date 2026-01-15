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
        'country',
        'city',
        'studio_name',
        'studio_address',
        'google_maps_link',
        'google_calendar_token',
        'google_calendar_id',
        'avatar',
        'currency',
        'timezone',
        'date_time_format',
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
    ];

    protected $casts = [
        'completed_steps' => 'array',
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
}
