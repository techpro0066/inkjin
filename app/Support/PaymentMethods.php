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

    public static function normalizePhone(?string $phone): string
    {
        $phone = trim((string) $phone);
        $phone = str_replace(['＋', "\u{FF0B}"], '+', $phone);
        $phone = preg_replace('/[\s\-().]/', '', $phone) ?? '';

        if (str_starts_with($phone, '00')) {
            $phone = '+'.substr($phone, 2);
        }

        return $phone;
    }

    /**
     * Greek client: E.164 phone prefix +30 (Bookpay / Viva IRIS rules).
     */
    public static function isGreekClientPhone(?string $phone): bool
    {
        $normalized = self::normalizePhone($phone);

        if ($normalized === '') {
            return false;
        }

        if (str_starts_with($normalized, '+30')) {
            return true;
        }

        return (bool) preg_match('/^30\d{10}$/', $normalized);
    }

    /**
     * Prefer the phone entered at checkout over a stale account phone.
     */
    public static function checkoutPhoneForIris(?string $checkoutPhone, ?string $accountPhone = null): ?string
    {
        foreach ([$checkoutPhone, $accountPhone] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return null;
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
