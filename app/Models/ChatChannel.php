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
        'booking_id',
        'booking_request_id',
        'custom_request_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_user_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class);
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

    public function scopeForPair($query, int $clientId, int $artistId)
    {
        return $query->where('client_user_id', $clientId)
            ->where('artist_user_id', $artistId);
    }

    public static function channelIdForBooking(int $clientId, int $artistId, int $bookingId): string
    {
        return 'u'.$clientId.'-a'.$artistId.'-b'.$bookingId;
    }

    public static function channelIdForBookingRequest(int $clientId, int $artistId, int $requestId): string
    {
        return 'u'.$clientId.'-a'.$artistId.'-br'.$requestId;
    }

    public static function channelIdForCustomRequest(int $clientId, int $artistId, int $requestId): string
    {
        return 'u'.$clientId.'-a'.$artistId.'-cr'.$requestId;
    }

    public function pairKey(): string
    {
        return $this->client_user_id.'-'.$this->artist_user_id;
    }

    public function isChatAllowed(): bool
    {
        $this->loadMissing(['booking', 'bookingRequest', 'customRequest']);

        if ($this->booking) {
            return $this->booking->isOpenForChat();
        }

        if ($this->bookingRequest) {
            return $this->bookingRequest->isOpenForChat();
        }

        if ($this->customRequest) {
            return $this->customRequest->isOpenForChat();
        }

        return false;
    }

    public function chatLockedReason(): ?string
    {
        $this->loadMissing(['booking', 'bookingRequest', 'customRequest']);

        if ($this->booking) {
            return $this->booking->chatLockedReason();
        }

        if ($this->bookingRequest) {
            return $this->bookingRequest->chatLockedReason();
        }

        if ($this->customRequest) {
            return $this->customRequest->chatLockedReason();
        }

        return 'This chat is read-only. You cannot send new messages.';
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
