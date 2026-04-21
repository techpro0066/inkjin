<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'is_active',
        'image',
        'primary_style',
        'other_styles',
        'color',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'other_styles' => 'array',
            'tags' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
