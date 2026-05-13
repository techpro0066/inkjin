<?php

namespace App\Support;

use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Support\Facades\Cache;

class EmailVerificationOtp
{
    public static function cacheKey(int $userId): string
    {
        return 'email_verification_otp:'.$userId;
    }

    /**
     * Store a new code and return the plain 4-digit value for the email body only.
     */
    public static function issueAndReturnPlainCode(MustVerifyEmailContract $user): string
    {
        if (app()->runningUnitTests()) {
            $plain = '4242';
        } else {
            $plain = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        $minutes = (int) config('auth.verification.expire', 60);
        Cache::put(
            self::cacheKey((int) $user->getKey()),
            hash('sha256', $plain),
            now()->addMinutes(max(1, $minutes))
        );

        return $plain;
    }

    /**
     * If the code matches, clears the OTP from cache and returns true.
     */
    public static function verify(MustVerifyEmailContract $user, string $code): bool
    {
        $key = self::cacheKey((int) $user->getKey());
        $stored = Cache::get($key);
        if (! is_string($stored) || strlen($stored) !== 64) {
            return false;
        }

        $digits = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($digits) !== 4) {
            return false;
        }

        $plain = str_pad($digits, 4, '0', STR_PAD_LEFT);
        if (! hash_equals($stored, hash('sha256', $plain))) {
            return false;
        }

        Cache::forget($key);

        return true;
    }
}
