<?php

namespace App\Services;

use App\Support\MoneyCurrency;
use RuntimeException;
use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\PaymentIntent;

class StripeProcessingFeeService
{
    public function amountForPaymentIntent(PaymentIntent $intent): float
    {
        $charge = $intent->latest_charge ?? null;
        if (is_string($charge)) {
            $charge = Charge::retrieve($charge, ['expand' => ['balance_transaction']]);
        }

        if (! $charge) {
            throw new RuntimeException('Stripe did not provide a charge for this payment.');
        }

        $balanceTransaction = $charge->balance_transaction ?? null;
        if (! $balanceTransaction && ! empty($charge->id)) {
            $charge = Charge::retrieve((string) $charge->id, [
                'expand' => ['balance_transaction'],
            ]);
            $balanceTransaction = $charge->balance_transaction ?? null;
        }

        if (is_string($balanceTransaction)) {
            $balanceTransaction = BalanceTransaction::retrieve($balanceTransaction);
        }

        if (! $balanceTransaction || ! is_numeric($balanceTransaction->fee ?? null)) {
            throw new RuntimeException('Stripe did not provide a processing fee for this payment.');
        }

        $currency = (string) ($balanceTransaction->currency ?? '');
        if ($currency === '') {
            throw new RuntimeException('Stripe did not provide the processing fee currency.');
        }

        return MoneyCurrency::fromStripeAmount((int) $balanceTransaction->fee, $currency);
    }
}
