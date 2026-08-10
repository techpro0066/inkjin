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
        'placement',
        'color',
        'tags',
        'sort_order',
        'instagram_media_id',
        'instagram_media_type',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'other_styles' => 'array',
            'tags' => 'array',
        ];
    }

    /**
     * Display label for placement on public pages.
     */
    public function getPlacementLabelAttribute(): string
    {
        $value = trim((string) ($this->placement ?? ''));

        return $value !== '' ? $value : 'Anywhere';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
