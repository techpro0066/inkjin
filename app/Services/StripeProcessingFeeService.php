<?php

namespace App\Services;

use App\Support\MoneyCurrency;
use Illuminate\Support\Facades\Log;
use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\PaymentIntent;

class StripeProcessingFeeService
{
    /**
     * Resolve Stripe's processing fee for a succeeded PaymentIntent.
     * Never blocks payment confirmation: retries briefly, then estimates if Stripe
     * has not attached balance_transaction.fee yet.
     */
    public function amountForPaymentIntent(PaymentIntent $intent): float
    {
        $resolved = $this->resolveFeeFromStripe($intent);
        if ($resolved !== null) {
            return $resolved;
        }

        $currency = MoneyCurrency::code($intent->currency ?: 'eur');
        $gross = MoneyCurrency::fromStripeAmount((int) ($intent->amount ?? 0), $currency);
        $estimated = $this->estimateFee($gross, $currency);

        Log::warning('Stripe processing fee unavailable; using estimate', [
            'payment_intent_id' => (string) ($intent->id ?? ''),
            'currency' => $currency,
            'gross' => $gross,
            'estimated_fee' => $estimated,
        ]);

        return $estimated;
    }

    private function resolveFeeFromStripe(PaymentIntent $intent): ?float
    {
        // Balance transactions can lag a moment after PaymentIntent succeeds.
        $attempts = 3;
        for ($i = 0; $i < $attempts; $i++) {
            if ($i > 0) {
                usleep(350000); // 350ms
                try {
                    $intent = PaymentIntent::retrieve((string) $intent->id, [
                        'expand' => ['latest_charge.balance_transaction'],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to re-fetch PaymentIntent for processing fee', [
                        'payment_intent_id' => (string) ($intent->id ?? ''),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $fee = $this->feeFromCharge($this->resolveCharge($intent), (string) ($intent->id ?? ''));
            if ($fee !== null) {
                return $fee;
            }
        }

        return null;
    }

    private function resolveCharge(PaymentIntent $intent): ?Charge
    {
        $charge = $intent->latest_charge ?? null;
        if ($charge instanceof Charge) {
            return $charge;
        }

        if (is_string($charge) && $charge !== '') {
            try {
                return Charge::retrieve($charge, ['expand' => ['balance_transaction']]);
            } catch (\Throwable $e) {
                Log::warning('Failed to retrieve Stripe charge for processing fee', [
                    'payment_intent_id' => (string) ($intent->id ?? ''),
                    'charge_id' => $charge,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    private function feeFromCharge(?Charge $charge, string $paymentIntentId): ?float
    {
        if (! $charge) {
            return null;
        }

        $balanceTransaction = $charge->balance_transaction ?? null;
        if (is_string($balanceTransaction) && $balanceTransaction !== '') {
            try {
                $balanceTransaction = BalanceTransaction::retrieve($balanceTransaction);
            } catch (\Throwable $e) {
                Log::warning('Failed to retrieve Stripe balance transaction for processing fee', [
                    'payment_intent_id' => $paymentIntentId,
                    'balance_transaction_id' => $balanceTransaction,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        if (! $balanceTransaction || ! is_object($balanceTransaction)) {
            return null;
        }

        if (! is_numeric($balanceTransaction->fee ?? null)) {
            return null;
        }

        $currency = (string) ($balanceTransaction->currency ?? '');
        if ($currency === '') {
            $currency = (string) ($charge->currency ?? '');
        }
        if ($currency === '') {
            return null;
        }

        return MoneyCurrency::fromStripeAmount((int) $balanceTransaction->fee, $currency);
    }

    /**
     * Same formula used elsewhere for projected Stripe fees (1.5% + 0.25).
     */
    public function estimateFee(float $amount, ?string $currency = null): float
    {
        $amount = max(0, (float) $amount);
        if ($amount <= 0) {
            return 0.0;
        }

        return MoneyCurrency::round($amount * 0.015 + 0.25, $currency);
    }
}
