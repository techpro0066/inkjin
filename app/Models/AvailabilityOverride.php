<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityOverride extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason',
        'studio_ids',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'studio_ids' => 'array',
    ];

    /** Null / empty studio_ids = blocked period; otherwise a location override. */
    public function isLocationOverride(): bool
    {
        return is_array($this->studio_ids) && count($this->studio_ids) > 0;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
