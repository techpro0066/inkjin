<?php

namespace Tests\Unit;

use Tests\TestCase;

class AdminStripeBalanceLowEmailTest extends TestCase
{
    public function test_template_renders_the_new_admin_stripe_alert_layout(): void
    {
        $html = view('emails.admin-stripe-balance-low', [
            'source' => 'manual_request',
            'requestedAmount' => 1250.50,
            'availableAmount' => 840.25,
            'currency' => 'EUR',
            'artistName' => 'Julian Ink',
            'bookingId' => 42,
            'bookingReference' => 'INK-000042',
            'dashboardUrl' => 'https://example.test/admin',
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('Manual payout request', $html);
        $this->assertStringContainsString('€1,250.50', $html);
        $this->assertStringContainsString('€840.25', $html);
        $this->assertStringContainsString('Open admin dashboard', $html);
    }
}
