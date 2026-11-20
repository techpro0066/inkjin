<?php

namespace Tests\Unit;

use App\Services\StripeProcessingFeeService;
use RuntimeException;
use Stripe\PaymentIntent;
use Tests\TestCase;

class StripeProcessingFeeServiceTest extends TestCase
{
    public function test_it_reads_and_converts_the_expanded_balance_transaction_fee(): void
    {
        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_test',
            'latest_charge' => [
                'id' => 'ch_test',
                'balance_transaction' => [
                    'id' => 'txn_test',
                    'fee' => 123,
                    'currency' => 'eur',
                ],
            ],
        ]);

        $this->assertSame(
            1.23,
            app(StripeProcessingFeeService::class)->amountForPaymentIntent($intent)
        );
    }

    public function test_it_fails_when_stripe_does_not_return_a_balance_transaction(): void
    {
        $intent = PaymentIntent::constructFrom([
            'id' => 'pi_test',
            'latest_charge' => ['id' => 'ch_test'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stripe did not provide a processing fee for this payment.');

        app(StripeProcessingFeeService::class)->amountForPaymentIntent($intent);
    }
}
