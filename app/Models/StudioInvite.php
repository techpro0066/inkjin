<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StudioInvite extends Model
{
    public const SESSION_TOKEN_KEY = 'studio_artist_invite_token';

    public const SESSION_SKIPPED_KEY = 'studio_artist_invite_skipped';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $table = 'studio_invites';

    protected $fillable = [
        'studio_id',
        'invited_by_user_id',
        'email',
        'user_id',
        'token',
        'relationship',
        'payout',
        'revenue_split',
        'status',
        'expires_at',
        'accepted_at',
        'declined_at',
        'cancelled_at',
    ];

    protected $casts = [
        'payout' => 'boolean',
        'revenue_split' => 'integer',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public static function tableReady(): bool
    {
        return Schema::hasTable('studio_invites');
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function markExpiredIfNeeded(): void
    {
        if ($this->status === self::STATUS_PENDING
            && $this->expires_at
            && $this->expires_at->isPast()
        ) {
            $this->status = self::STATUS_EXPIRED;
            $this->save();
        }
    }

    public static function newToken(): string
    {
        return Str::random(48);
    }

    public function acceptUrl(): string
    {
        return route('studio.artist-invite.show', ['token' => $this->token]);
    }
}
