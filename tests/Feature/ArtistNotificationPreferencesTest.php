<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtistNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_can_save_disabled_notifications_and_load_them_back(): void
    {
        $artist = User::factory()->create(['role' => 'artist']);

        $this->actingAs($artist)
            ->postJson(route('settings.notifications.update'), [
                'not_allowed_notification' => ['new_bookings', 'studio_answers'],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(
            ['new_bookings', 'studio_answers'],
            $artist->userDetail->fresh()->not_allowed_notification
        );

        $this->get(route('settings.notifications'))
            ->assertOk()
            ->assertSee('data-notification="new_bookings" aria-checked="false"', false)
            ->assertSee('data-notification="studio_answers" aria-checked="false"', false);
    }

    public function test_artist_can_clear_all_disabled_notifications(): void
    {
        $artist = User::factory()->create(['role' => 'artist']);
        UserDetail::create([
            'user_id' => $artist->id,
            'not_allowed_notification' => ['new_requests'],
        ]);

        $this->actingAs($artist)
            ->postJson(route('settings.notifications.update'), [
                'not_allowed_notification' => [],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame([], $artist->userDetail->fresh()->not_allowed_notification);
    }

    public function test_artist_cannot_save_unknown_notification_keys(): void
    {
        $artist = User::factory()->create(['role' => 'artist']);

        $this->actingAs($artist)
            ->postJson(route('settings.notifications.update'), [
                'not_allowed_notification' => ['unknown'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('not_allowed_notification.0');
    }

}
