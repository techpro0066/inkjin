<?php

namespace Tests\Unit;

use App\Support\MoneyCurrency;
use Tests\TestCase;

class MoneyCurrencyTest extends TestCase
{
    public function test_stripe_amount_uses_currency_minor_units(): void
    {
        $this->assertSame(1235, MoneyCurrency::toStripeAmount(12.345, 'EUR'));
        $this->assertSame(1235, MoneyCurrency::toStripeAmount(1234.6, 'JPY'));
        $this->assertSame(12346, MoneyCurrency::toStripeAmount(12.3456, 'BHD'));
        $this->assertSame(1235.0, MoneyCurrency::fromStripeAmount(1235, 'JPY'));
    }

    public function test_amounts_are_formatted_in_the_selected_currency(): void
    {
        $this->assertSame('£12.35', MoneyCurrency::format(12.345, 'GBP'));
        $this->assertSame('¥1,235', MoneyCurrency::format(1234.6, 'JPY'));
    }
}
