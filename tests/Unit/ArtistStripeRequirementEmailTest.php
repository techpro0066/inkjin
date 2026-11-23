<?php

namespace Tests\Unit;

use Tests\TestCase;

class ArtistStripeRequirementEmailTest extends TestCase
{
    public function test_template_renders_the_artist_stripe_requirement_layout(): void
    {
        $html = view('emails.artist-stripe-requirement', [
            'firstName' => 'Julian',
            'actionUrl' => 'https://example.test/payment',
            'isStudioPayout' => false,
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('Hi Julian', $html);
        $this->assertStringContainsString('Fix Stripe account', $html);
        $this->assertStringContainsString('held payouts', $html);
    }

    public function test_template_renders_the_studio_payout_variant(): void
    {
        $html = view('emails.artist-stripe-requirement', [
            'firstName' => 'Julian',
            'actionUrl' => 'https://example.test/payment',
            'isStudioPayout' => true,
        ])->render();

        $this->assertStringContainsString('studio&rsquo;s payout account', $html);
    }
}
