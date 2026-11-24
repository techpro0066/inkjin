<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlansControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_plan_with_currency_prices(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = $this->planPayload();

        $this->actingAs($admin)
            ->postJson(route('admin.plans.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('plan.name', 'Studio Pro')
            ->assertJsonPath('plan.prices.USD', 103)
            ->assertJsonPath('plan.count', 0);

        $plan = Plan::query()->where('slug', 'studio-pro')->firstOrFail();
        $this->assertSame(12, count($plan->prices));
        $this->assertSame(['Custom domain', 'Calendar sync'], $plan->features);
        $this->assertSame([['label' => 'Monthly', 'value' => '€89']], $plan->terms);

        $updated = $payload;
        $updated['status'] = 'Off';
        $updated['prices']['USD'] = 105;

        $this->putJson(route('admin.plans.update', $plan), $updated)
            ->assertOk()
            ->assertJsonPath('plan.status', 'Off')
            ->assertJsonPath('plan.prices.USD', 105);

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => 'Off',
            'slug' => 'studio-pro',
        ]);
    }

    public function test_admin_can_add_a_plan_feature_to_the_catalog(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.plans.features.store'), ['name' => ' Google Reviews '])
            ->assertCreated()
            ->assertJsonPath('feature', 'Google Reviews');

        $this->assertDatabaseHas('plan_features', ['name' => 'Google Reviews']);
    }

    /**
     * @return array<string, mixed>
     */
    private function planPayload(): array
    {
        return [
            'name' => 'Studio Pro',
            'status' => 'On',
            'audience' => 'Studio',
            'visibility' => 'Public',
            'badge' => 'RECOMMENDED',
            'prices' => [
                'EUR' => 89,
                'CAD' => 144,
                'CHF' => 84,
                'CZK' => 2240,
                'DKK' => 664,
                'GBP' => 77,
                'HUF' => 35200,
                'NOK' => 1041,
                'PLN' => 378,
                'RON' => 445,
                'SEK' => 1006,
                'USD' => 103,
            ],
            'commission' => 3,
            'bookingFee' => '0',
            'includedArtists' => 8,
            'extraArtist' => 15,
            'trialDays' => 60,
            'trialFallback' => 'Studio Standard',
            'cutoffDate' => null,
            'afterCutoff' => 'Studio Standard',
            'layout' => 'card',
            'tagline' => 'For studios that want more bookings and less admin.',
            'priceNote' => null,
            'listTitle' => 'Everything in Standard, plus',
            'includedItems' => ['Pro for every artist in your studio'],
            'buttonLabel' => 'Start 60-day free trial',
            'buttonNote' => 'No card needed.',
            'termsIntro' => null,
            'terms' => [['label' => 'Monthly', 'value' => '€89']],
            'termsNote' => null,
            'trialButton' => null,
            'features' => ['Custom domain', 'Calendar sync'],
        ];
    }
}
