<?php

namespace App\Support;

use App\Models\Studio;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Schema;

class PaymentMethods
{
    /**
     * Artist (or studio-linked) payout bank country is Greece.
     * Source of truth is user_payouts; legacy user_details.payout_bank_country is fallback only.
     */
    public static function isGreekArtist(?UserDetail $artist): bool
    {
        if (! $artist) {
            return false;
        }

        return self::artistPayoutBankCountry($artist) === 'GR';
    }

    /**
     * Resolved ISO-2 payout bank country for IRIS / checkout.
     */
    public static function artistPayoutBankCountry(?UserDetail $artist): ?string
    {
        if (! $artist) {
            return null;
        }

        $payout = $artist->activePayout();
        $fromPayout = strtoupper(trim((string) ($payout?->payout_bank_country ?? '')));
        if (strlen($fromPayout) === 2) {
            return $fromPayout;
        }

        // Studio-account artists: fall back to the linked studio's country when set.
        if (($artist->resolvedPayoutPaymentType() === 'studio_account'
                || ($artist->payment_type ?? null) === 'studio_account')
            && ($studioId = (int) ($artist->resolvedStudioId() ?? 0)) > 0
        ) {
            $studioCountry = strtoupper(trim((string) (
                Studio::query()->whereKey($studioId)->value('country') ?? ''
            )));
            if (strlen($studioCountry) === 2) {
                return $studioCountry;
            }
        }

        if (Schema::hasColumn($artist->getTable(), 'payout_bank_country')) {
            $legacy = strtoupper(trim((string) ($artist->getAttribute('payout_bank_country') ?? '')));
            if (strlen($legacy) === 2) {
                return $legacy;
            }
        }

        return null;
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
