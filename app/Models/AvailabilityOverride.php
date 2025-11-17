<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityOverride extends Model
{
    protected $fillable = [
        'user_id',
        'override_date',
        'start_time',
        'end_time',
        'is_unavailable',
        'notes',
    ];

    protected $casts = [
        'override_date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string',
        'is_unavailable' => 'boolean',
    ];

    /**
     * Get the user that owns the availability override.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
