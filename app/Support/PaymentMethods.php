<?php

namespace App\Support;

use App\Models\UserDetail;

class PaymentMethods
{
    public static function isGreekArtist(?UserDetail $artist): bool
    {
        if (! $artist) {
            return false;
        }

        return strtoupper((string) $artist->payout_bank_country) === 'GR';
    }

    /**
     * Greek client: E.164 phone prefix +30 (Bookpay / Viva IRIS rules).
     */
    public static function isGreekClientPhone(?string $phone): bool
    {
        $normalized = preg_replace('/[\s\-()]/', '', (string) $phone);

        return str_starts_with($normalized, '+30');
    }

    /**
     * IRIS checkout when artist bank country is GR and client phone is +30.
     */
    public static function showIrisTab(?UserDetail $artist, ?string $clientPhone = null): bool
    {
        return self::isGreekArtist($artist)
            && self::isGreekClientPhone($clientPhone);
    }
}
