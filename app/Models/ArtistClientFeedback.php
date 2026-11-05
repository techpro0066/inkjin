<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtistClientFeedback extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'booking_id',
        'artist_id',
        'client_user_id',
        'studio_id',
        'rating',
        'answers',
        'status',
        'email_sent_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'answers' => 'array',
        'email_sent_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    public function isPending(): bool
    {
        return (string) $this->status === self::STATUS_PENDING;
    }

    public function isReviewed(): bool
    {
        return (string) $this->status === self::STATUS_REVIEWED;
    }
}
