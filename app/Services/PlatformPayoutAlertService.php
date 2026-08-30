<?php

namespace App\Services;

use App\Mail\AdminStripeBalanceLowMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PlatformPayoutAlertService
{
    public function __construct(
        private readonly StripeConnectService $stripeConnect,
    ) {}

    public function platformAvailableAmount(string $currency = 'EUR'): ?float
    {
        $summary = $this->stripeConnect->getPlatformBalanceSummary();
        if (! $summary || ! ($summary['configured'] ?? false) || ! empty($summary['error'])) {
            return null;
        }

        if (strtoupper((string) ($summary['currency'] ?? 'EUR')) !== strtoupper($currency)) {
            return null;
        }

        return round((float) ($summary['available'] ?? 0), 2);
    }

    public function hasSufficientPlatformBalance(float $amount, string $currency = 'EUR'): bool
    {
        $available = $this->platformAvailableAmount($currency);
        if ($available === null) {
            return true;
        }

        return $available + 0.001 >= round($amount, 2);
    }

    /**
     * @param  array{
     *     source: string,
     *     requested_amount: float,
     *     available_amount: float,
     *     currency: string,
     *     artist_name?: string|null,
     *     booking_id?: int|null,
     *     booking_reference?: string|null
     * }  $context
     */
    public function notifyInsufficientBalance(array $context): void
    {
        $email = $this->resolveAdminEmail();
        if ($email === null) {
            Log::warning('Stripe platform balance too low for payout but no admin email is configured.', $context);

            return;
        }

        $cacheKey = 'stripe_balance_low:'.md5(json_encode([
            $context['source'] ?? '',
            round((float) ($context['requested_amount'] ?? 0), 2),
            round((float) ($context['available_amount'] ?? 0), 2),
            strtoupper((string) ($context['currency'] ?? 'EUR')),
        ]));

        if (! Cache::add($cacheKey, 1, now()->addHour())) {
            return;
        }

        try {
            Mail::to($email)->send(new AdminStripeBalanceLowMail(
                source: (string) ($context['source'] ?? 'payout'),
                requestedAmount: round((float) ($context['requested_amount'] ?? 0), 2),
                availableAmount: round((float) ($context['available_amount'] ?? 0), 2),
                currency: strtoupper((string) ($context['currency'] ?? 'EUR')),
                artistName: $context['artist_name'] ?? null,
                bookingId: isset($context['booking_id']) ? (int) $context['booking_id'] : null,
                bookingReference: $context['booking_reference'] ?? null,
                dashboardUrl: route('admin.dashboard'),
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send admin Stripe balance low alert', [
                'admin_email' => $email,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function resolveAdminEmail(): ?string
    {
        $configured = trim((string) config('inkjin.admin_email', ''));
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_EMAIL)) {
            return $configured;
        }

        $adminEmail = User::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->orderBy('id')
            ->value('email');

        $adminEmail = trim((string) $adminEmail);

        return $adminEmail !== '' && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)
            ? $adminEmail
            : null;
    }
}
