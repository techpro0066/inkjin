<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InkJinTattoo extends Model
{
    protected $table = 'inkjin_tattoos';

    protected $fillable = [
        'artist_handle',
        'type',
        'visibility',
        'filename',
        'ink',
        'ar',
        'repeatable',
        'sensitive',
        'title',
        'description',
        'primary_style',
        'other_styles',
        'suggested_placement',
        'color',
        'tags',
        'price',
        'max_price',
        'size_height',
        'size_width',
        'cost_per_session',
        'min_sessions',
        'max_sessions',
        'session_time_h',
        'currency',
        'price_model',
        'notes',
    ];

    protected $casts = [
        'size_height' => 'float',
        'size_width' => 'float',
        'session_time_h' => 'float',
        'min_sessions' => 'integer',
        'max_sessions' => 'integer',
        'price' => 'float',
        'max_price' => 'float',
        'cost_per_session' => 'float',
    ];

    /**
     * Get the artist that owns this tattoo
     */
    public function artist()
    {
        return $this->belongsTo(InkJinArtist::class, 'artist_handle', 'artist_handle');
    }

    /**
     * Get image URL (alias for filename for compatibility)
     */
    public function getImageAttribute()
    {
        return $this->filename;
    }
}

