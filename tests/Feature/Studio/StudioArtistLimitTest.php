<?php

namespace Tests\Feature\Studio;

use App\Models\GuestSpot;
use App\Models\Studio;
use App\Models\User;
use App\Models\UserStudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StudioArtistLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_studio_cannot_link_a_seventh_artist_but_studio_accounts_do_not_count(): void
    {
        $studio = Studio::query()->create([
            'name' => 'Six Artist Studio',
            'email' => 'six-artists@example.test',
        ]);

        for ($index = 0; $index < 5; $index++) {
            $artist = User::factory()->create(['role' => 'artist']);
            UserStudio::query()->create([
                'user_id' => $artist->id,
                'studio_id' => $studio->id,
                'status' => UserStudio::STATUS_ACTIVE,
                'connection_type' => UserStudio::CONNECTION_ON_BOOKPAY,
                'relationship' => 'resident',
            ]);
        }

        $guestArtist = User::factory()->create(['role' => 'artist']);
        GuestSpot::query()->create([
            'user_id' => $guestArtist->id,
            'studio_id' => $studio->id,
            'status' => 'available',
            'city' => 'Amsterdam',
            'country' => 'Netherlands',
            'from_date' => now()->toDateString(),
            'to_date' => now()->addDay()->toDateString(),
        ]);

        $studioAccount = User::factory()->create(['role' => 'studio']);
        UserStudio::query()->create([
            'user_id' => $studioAccount->id,
            'studio_id' => $studio->id,
            'status' => UserStudio::STATUS_ACTIVE,
            'connection_type' => UserStudio::CONNECTION_ON_BOOKPAY,
        ]);

        $sixthArtist = User::factory()->create(['role' => 'artist']);
        UserStudio::query()->create([
            'user_id' => $sixthArtist->id,
            'studio_id' => $studio->id,
            'status' => UserStudio::STATUS_ACTIVE,
            'connection_type' => UserStudio::CONNECTION_ON_BOOKPAY,
            'relationship' => 'resident',
        ]);

        $seventhArtist = User::factory()->create(['role' => 'artist']);

        try {
            UserStudio::query()->create([
                'user_id' => $seventhArtist->id,
                'studio_id' => $studio->id,
                'status' => UserStudio::STATUS_ACTIVE,
                'connection_type' => UserStudio::CONNECTION_ON_BOOKPAY,
                'relationship' => 'resident',
            ]);
            $this->fail('Expected the seventh artist link to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'This studio can link up to 6 artists. Guest artists do not count toward this limit.',
                $exception->errors()['studio'][0],
            );
        }
    }
}
