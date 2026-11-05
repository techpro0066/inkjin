<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatChannel extends Model
{
    public const KIND_CLIENT_ARTIST = 'client_artist';

    public const KIND_STUDIO_ARTIST = 'studio_artist';

    protected $fillable = [
        'stream_channel_id',
        'channel_kind',
        'client_user_id',
        'studio_user_id',
        'artist_user_id',
        'booking_id',
        'booking_request_id',
        'custom_request_id',
        'client_email_pending_text',
        'client_email_pending_at',
        'client_email_send_after',
        'artist_email_pending_text',
        'artist_email_pending_at',
        'artist_email_send_after',
    ];

    protected function casts(): array
    {
        return [
            'client_email_pending_at' => 'datetime',
            'client_email_send_after' => 'datetime',
            'artist_email_pending_at' => 'datetime',
            'artist_email_send_after' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function studioUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'studio_user_id');
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
                ->orWhere('artist_user_id', $userId)
                ->orWhere('studio_user_id', $userId);
        });
    }

    public function scopeForClient($query, int $userId)
    {
        return $query->where('client_user_id', $userId)
            ->where('channel_kind', self::KIND_CLIENT_ARTIST);
    }

    public function scopeForArtist($query, int $userId)
    {
        return $query->where('artist_user_id', $userId);
    }

    public function scopeForPair($query, int $clientId, int $artistId)
    {
        return $query->where('channel_kind', self::KIND_CLIENT_ARTIST)
            ->where('client_user_id', $clientId)
            ->where('artist_user_id', $artistId);
    }

    public function scopeForStudioPair($query, int $studioUserId, int $artistId)
    {
        return $query->where('channel_kind', self::KIND_STUDIO_ARTIST)
            ->where('studio_user_id', $studioUserId)
            ->where('artist_user_id', $artistId);
    }

    public function scopeClientArtistForBooking($query, int $bookingId)
    {
        return $query->where('booking_id', $bookingId)
            ->where('channel_kind', self::KIND_CLIENT_ARTIST);
    }

    public function scopeStudioArtistForBooking($query, int $bookingId)
    {
        return $query->where('booking_id', $bookingId)
            ->where('channel_kind', self::KIND_STUDIO_ARTIST);
    }

    public static function channelIdForBooking(int $clientId, int $artistId, int $bookingId): string
    {
        return 'u'.$clientId.'-a'.$artistId.'-b'.$bookingId;
    }

    public static function channelIdForStudioBooking(int $studioUserId, int $artistId, int $bookingId): string
    {
        return 's'.$studioUserId.'-a'.$artistId.'-b'.$bookingId;
    }

    public static function channelIdForBookingRequest(int $clientId, int $artistId, int $requestId): string
    {
        return 'u'.$clientId.'-a'.$artistId.'-br'.$requestId;
    }

    public static function channelIdForCustomRequest(int $clientId, int $artistId, int $requestId): string
    {
        return 'u'.$clientId.'-a'.$artistId.'-cr'.$requestId;
    }

    public function isStudioArtist(): bool
    {
        return ($this->channel_kind ?: self::KIND_CLIENT_ARTIST) === self::KIND_STUDIO_ARTIST;
    }

    public function pairKey(): string
    {
        if ($this->isStudioArtist()) {
            return 's'.$this->studio_user_id.'-a'.$this->artist_user_id;
        }

        return 'c'.$this->client_user_id.'-a'.$this->artist_user_id;
    }

    public function isChatAllowed(): bool
    {
        $this->loadMissing(['booking', 'bookingRequest', 'customRequest']);

        if ($this->booking) {
            return $this->booking->isOpenForChat();
        }

        if ($this->isStudioArtist()) {
            return false;
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

        if ($this->isStudioArtist()) {
            return 'This chat is read-only. You cannot send new messages.';
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
        if ($this->isStudioArtist()) {
            if ((int) $this->studio_user_id === $userId) {
                return (int) $this->artist_user_id;
            }
            if ((int) $this->artist_user_id === $userId) {
                return (int) $this->studio_user_id;
            }

            return null;
        }

        if ((int) $this->client_user_id === $userId) {
            return (int) $this->artist_user_id;
        }

        if ((int) $this->artist_user_id === $userId) {
            return (int) $this->client_user_id;
        }

        return null;
    }
}
