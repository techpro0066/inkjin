<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBankDetail extends Model
{
    protected $fillable = [
        'user_id',
        'account_holder_name',
        'bank_name',
        'account_number',
        'swift_bic',
        'bank_currency',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

