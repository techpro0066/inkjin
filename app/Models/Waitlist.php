<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waitlist extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    protected $fillable = [
        'user_id',
        'status',
        'name',
        'email',
    ];

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
