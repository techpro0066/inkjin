<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformFee extends Model
{
    protected $table = 'platform_fee';

    public $timestamps = false;

    protected $fillable = [
        'country',
        'currency',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public static function amountForCurrency(?string $currency): float
    {
        $currency = strtoupper(trim((string) $currency));
        if ($currency === '') {
            $currency = 'EUR';
        }

        $amount = static::query()->where('currency', $currency)->value('amount');

        return $amount === null ? 10.0 : (float) $amount;
    }
}
