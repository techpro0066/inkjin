<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatChannel extends Model
{
    protected $fillable = [
        'stream_channel_id',
        'client_user_id',
        'artist_user_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_user_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('client_user_id', $userId)
                ->orWhere('artist_user_id', $userId);
        });
    }

    public function scopeForClient($query, int $userId)
    {
        return $query->where('client_user_id', $userId);
    }

    public function scopeForArtist($query, int $userId)
    {
        return $query->where('artist_user_id', $userId);
    }

    public static function channelIdForPair(int $clientId, int $artistId): string
    {
        return 'u'.$clientId.'-a'.$artistId;
    }

    public function isChatAllowed(): bool
    {
        return Booking::hasOpenChatBetween($this->client_user_id, $this->artist_user_id);
    }

    public function latestOpenBooking(): ?Booking
    {
        return Booking::latestOpenBetween($this->client_user_id, $this->artist_user_id);
    }

    public function otherPartyUserIdFor(int $userId): ?int
    {
        if ($this->client_user_id === $userId) {
            return $this->artist_user_id;
        }

        if ($this->artist_user_id === $userId) {
            return $this->client_user_id;
        }

        return null;
    }
}
