<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Studio extends Model
{
    protected $fillable = [
        'name',
        'email',
        'account_holder_name',
        'bank_name',
        'account_number',
        'swift_bic',
        'bank_currency',
    ];

    /**
     * Artists (user details) associated with this studio.
     */
    public function userDetails(): HasMany
    {
        return $this->hasMany(UserDetail::class);
    }

    /**
     * Whether the studio already has a full bank profile on file (used for new artists on the same studio email).
     */
    public function hasStoredBankDetails(): bool
    {
        $nonEmpty = static fn ($v): bool => $v !== null && trim((string) $v) !== '';

        return $nonEmpty($this->account_holder_name)
            && $nonEmpty($this->bank_name)
            && $nonEmpty($this->account_number)
            && $nonEmpty($this->swift_bic)
            && $nonEmpty($this->bank_currency);
    }
}

