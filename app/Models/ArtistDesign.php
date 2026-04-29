<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtistDesign extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'image',
        'is_active',
        'is_visible',
        'is_repeatable',
        'is_sensitive',
        'primary_style',
        'other_styles',
        'color',
        'tags',
        'min_price',
        'max_price',
        'min_size',
        'max_size',
        'min_sessions',
        'max_sessions',
        'session_duration',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'is_repeatable' => 'boolean',
            'is_sensitive' => 'boolean',
            'other_styles' => 'array',
            'tags' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
