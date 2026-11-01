<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waitlist extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const SIGNUP_MODE_NAME_EMAIL = 'name_email';

    public const SIGNUP_MODE_REQUEST_FORM = 'request_form';

    protected $fillable = [
        'user_id',
        'status',
        'name',
        'email',
        'custom_request_id',
        'guest_spot_id',
    ];

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class, 'custom_request_id');
    }

    public function guestSpot(): BelongsTo
    {
        return $this->belongsTo(GuestSpot::class, 'guest_spot_id');
    }

    public function scopeBooksOnly($query)
    {
        return $query->whereNull('guest_spot_id');
    }

    public function scopeGuestOnly($query)
    {
        return $query->whereNotNull('guest_spot_id');
    }
}
