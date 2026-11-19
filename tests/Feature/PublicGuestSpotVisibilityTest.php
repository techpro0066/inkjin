<?php

namespace Tests\Feature;

use App\Models\GuestSpot;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicGuestSpotVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_spot_page_remains_available_when_hidden_from_artist_page(): void
    {
        $artist = User::factory()->create([
            'role' => 'artist',
            'on_boarding' => 'yes',
        ]);

        UserDetail::create([
            'user_id' => $artist->id,
            'user_name' => 'guest-spot-artist',
            'display_guest_spots' => false,
        ]);

        $guestSpot = GuestSpot::create([
            'user_id' => $artist->id,
            'status' => 'available',
            'city' => 'Lahore',
            'country' => 'Pakistan',
            'from_date' => now()->addWeek()->toDateString(),
            'to_date' => now()->addWeeks(2)->toDateString(),
        ]);

        $response = $this->get(route('public.guest-spot', [
            'user_name' => 'guest-spot-artist',
            'guestSpotSlug' => $guestSpot->publicSlug(),
        ]));

        $response->assertOk();
        $response->assertViewIs('public.guest-spot');
    }
}
