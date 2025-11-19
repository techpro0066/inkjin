<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InkJinArtist extends Model
{
    protected $table = 'inkjin_artists';

    protected $fillable = [
        'artist_handle',
        'visibility',
        'email',
        'first_name',
        'last_name',
        'mobile_phone',
        'nickname',
        'profile_name',
        'city',
        'state_province',
        'country',
        'style',
        'other_styles',
        'since',
        'studio',
        'instagram',
        'tiktok',
        'website',
        'artist_dashboard_signup',
    ];

    protected $casts = [
        'artist_dashboard_signup' => 'date',
    ];

    /**
     * Get the tattoos for this artist
     */
    public function tattoos()
    {
        return $this->hasMany(InkJinTattoo::class, 'artist_handle', 'artist_handle');
    }

    /**
     * Get display name (profile_name or constructed from first_name and last_name)
     */
    public function getDisplayNameAttribute()
    {
        return $this->profile_name ?? ($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get username (alias for artist_handle for compatibility)
     */
    public function getUsernameAttribute()
    {
        return $this->artist_handle;
    }
}

