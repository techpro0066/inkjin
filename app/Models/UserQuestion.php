<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuestion extends Model
{
    protected $fillable = [
        'user_id',
        'question',
        'type',
        'options',
        'status',
        'max_images',
    ];

    protected $casts = [
        'status' => 'string',
        'options' => 'array',
        'max_images' => 'integer',
    ];

    /**
     * Get the user that owns the question.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
