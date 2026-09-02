<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Email OTP / verification for public booking flows.
 * Uses Cache (not session) so reverse-proxied pages on inkjin.com can call
 * bookpay.inkjin.com APIs without a shared session cookie.
 */
class PublicBookingEmailVerificationService
{
    private const OTP_TTL_SECONDS = 600;

    private const VERIFIED_TTL_SECONDS = 600;

    private const COOLDOWN_SECONDS = 60;

    public function cooldownRemainingSeconds(string $email): int
    {
        $until = (int) Cache::get($this->cooldownKey($email), 0);
        $remaining = $until - now()->timestamp;

        return max(0, $remaining);
    }

    public function storeOtp(string $email, string $code): int
    {
        $email = $this->normalizeEmail($email);
        $now = now()->timestamp;

        Cache::put($this->otpKey($email), [
            'code' => $code,
            'expires_at' => $now + self::OTP_TTL_SECONDS,
        ], self::OTP_TTL_SECONDS);

        Cache::put($this->cooldownKey($email), $now + self::COOLDOWN_SECONDS, self::COOLDOWN_SECONDS);

        return self::COOLDOWN_SECONDS;
    }

    /**
     * @return array{code: string, expires_at: int}|null
     */
    public function getOtp(string $email): ?array
    {
        $payload = Cache::get($this->otpKey($email));

        return is_array($payload) ? $payload : null;
    }

    public function forgetOtp(string $email): void
    {
        Cache::forget($this->otpKey($this->normalizeEmail($email)));
    }

    /**
     * @param  array{user_id: int, verified_until: int, is_new_user?: bool}  $payload
     */
    public function markVerified(string $email, array $payload): void
    {
        $email = $this->normalizeEmail($email);
        Cache::put($this->verifiedKey($email), $payload, self::VERIFIED_TTL_SECONDS);
        $this->forgetOtp($email);
    }

    /**
     * @return array{user_id: int, verified_until: int, is_new_user?: bool}|null
     */
    public function getVerified(string $email): ?array
    {
        $payload = Cache::get($this->verifiedKey($this->normalizeEmail($email)));
        if (! is_array($payload) || empty($payload['user_id'])) {
            return null;
        }

        if (now()->timestamp > (int) ($payload['verified_until'] ?? 0)) {
            $this->forgetVerified($email);

            return null;
        }

        return $payload;
    }

    public function forgetVerified(string $email): void
    {
        Cache::forget($this->verifiedKey($this->normalizeEmail($email)));
    }

    public function otpTtlSeconds(): int
    {
        return self::OTP_TTL_SECONDS;
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function otpKey(string $email): string
    {
        return 'booking_otp:'.$this->normalizeEmail($email);
    }

    private function cooldownKey(string $email): string
    {
        return 'booking_otp_cooldown:'.$this->normalizeEmail($email);
    }

    private function verifiedKey(string $email): string
    {
        return 'booking_verified_email:'.$this->normalizeEmail($email);
    }
}
