<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentFormQuestion extends Model
{
    public const SYSTEM_USER_ID = 1;

    protected $fillable = [
        'user_id',
        'question_type',
        'translations',
        'enabled',
        'order',
    ];

    protected $casts = [
        'translations' => 'array',
        'enabled' => 'boolean',
        'order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSystemQuestion(): bool
    {
        return (int) $this->user_id === self::SYSTEM_USER_ID;
    }
}
