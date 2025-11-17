<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InkJinTattoo extends Model
{
    protected $table = 'inkjin_tattoos';

    protected $fillable = [
        'tattoo_id',
        'title',
        'image',
        'tags',
        'color',
        'primary_style',
        'style',
        'suggested_placement',
        'available_to_ink',
        'available_to_ar',
        'mature_content',
        'status',
        'liked_by_current_user',
        'author_id',
        'author_username',
        'author_display_name',
        'author_profile_picture',
    ];

    protected $casts = [
        'tattoo_id' => 'integer',
        'available_to_ink' => 'boolean',
        'available_to_ar' => 'boolean',
        'mature_content' => 'boolean',
        'liked_by_current_user' => 'boolean',
        'author_id' => 'integer',
    ];

    /**
     * Get the artist that owns this tattoo
     */
    public function artist()
    {
        return $this->belongsTo(InkJinArtist::class, 'author_username', 'username');
    }
}

