<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentFormQuestion extends Model
{
    public const SYSTEM_USER_ID = 1;

    protected $fillable = [
        'user_id',
        'question_type',
        'translations',
        'enabled',
    ];

    protected $casts = [
        'translations' => 'array',
        'enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sortings(): HasMany
    {
        return $this->hasMany(ConsentFormQuestionSorting::class, 'consent_form_question_id');
    }

    public function isSystemQuestion(): bool
    {
        return (int) $this->user_id === self::SYSTEM_USER_ID;
    }
}
