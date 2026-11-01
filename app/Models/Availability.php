<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Availability extends Model
{
    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'studio_ids',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
        'studio_ids' => 'array',
    ];

    /**
     * Get the user that owns the availability.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
