<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRequest extends Model
{
    protected $table = 'booking_requests';

    protected $fillable = [
        'user_id',
        'artist_id',
        'tattoo_id',
        'status',
        'questions_answers',
        'consultation_details',
        'preferences',
        'preferred_days',
        'avoid_dates',
        'how_much_flexible',
        'urgency',
    ];

    protected $casts = [
        'questions_answers' => 'array',
        'preferences' => 'array',
        'preferred_days' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function tattoo(): BelongsTo
    {
        return $this->belongsTo(ArtistDesign::class, 'tattoo_id');
    }

    public function referenceLabel(): string
    {
        return '#REQ-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
