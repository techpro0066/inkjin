<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InkJinArtist extends Model
{
    protected $table = 'inkjin_artists';

    protected $fillable = [
        'user_id',
        'username',
        'display_name',
        'profile_id',
        'email',
        'phone',
        'instagram',
        'tiktok',
        'website',
        'studio',
        'primary_style',
        'style',
        'tattooing_since',
        'description',
        'address_number',
        'address_street',
        'city',
        'country',
        'followers_count',
        'tattoo_count',
        'allow_messages',
        'profile_picture',
        'created_date',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'profile_id' => 'integer',
        'followers_count' => 'integer',
        'tattoo_count' => 'integer',
        'allow_messages' => 'boolean',
        'created_date' => 'date',
    ];

    /**
     * Get the tattoos for this artist
     */
    public function tattoos()
    {
        return $this->hasMany(InkJinTattoo::class, 'author_username', 'username');
    }
}

