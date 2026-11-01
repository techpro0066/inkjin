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
        'image',
        'attribution',
        'artist_user_id',
        'source',
        'instagram_media_id',
        'instagram_media_type',
        'sort_order',
    ];

    protected $casts = [
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
        return in_array($this->attribution, [self::ATTRIBUTION_STUDIO, self::ATTRIBUTION_ARTIST], true);
    }
}
