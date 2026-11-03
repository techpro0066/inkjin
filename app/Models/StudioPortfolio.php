<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudioPortfolio extends Model
{
    public const ATTRIBUTION_PENDING = 'pending';

    public const ATTRIBUTION_STUDIO = 'studio';

    public const ATTRIBUTION_ARTIST = 'artist';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_INSTAGRAM = 'instagram';

    protected $fillable = [
        'studio_id',
        'title',
        'description',
        'image',
        'is_active',
        'primary_style',
        'other_styles',
        'placement',
        'color',
        'tags',
        'attribution',
        'artist_user_id',
        'source',
        'instagram_media_id',
        'instagram_media_type',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'other_styles' => 'array',
        'tags' => 'array',
        'sort_order' => 'integer',
    ];

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_user_id');
    }

    public function isVisibleOnPage(): bool
    {
        if ($this->is_active === false) {
            return false;
        }

        return in_array($this->attribution, [self::ATTRIBUTION_STUDIO, self::ATTRIBUTION_ARTIST], true);
    }

    public function getPlacementLabelAttribute(): string
    {
        $value = trim((string) ($this->placement ?? ''));

        return $value !== '' ? $value : 'Anywhere';
    }
}
