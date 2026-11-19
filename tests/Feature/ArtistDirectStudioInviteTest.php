<?php

namespace Tests\Feature;

use App\Mail\ArtistStudioJoinRequestMail;
use App\Models\User;
use App\Models\UserStudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ArtistDirectStudioInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_a_direct_payment_workplace_can_send_a_bookpay_invite_without_a_split(): void
    {
        Mail::fake();
        $artist = User::factory()->create(['role' => 'artist']);

        $response = $this->actingAs($artist)->postJson(route('settings.studio.change.store'), [
            'mode' => 'add',
            'path' => 'not_on_bookpay',
            'payout_mode' => 'direct',
            'workspace_type' => 'shop',
            'relationship' => 'resident',
            'studio_name' => 'New Studio',
            'studio_address' => '12 Main Street',
            'city' => 'Lahore',
            'country' => 'Pakistan',
            'direct_invite_enabled' => 1,
            'direct_invite_studio_name' => 'New Studio',
            'direct_invite_studio_email' => 'studio@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Studio added and invitation sent.');

        $link = UserStudio::query()->where('user_id', $artist->id)->sole();
        $this->assertFalse($link->payout);
        $this->assertNull($link->revenue_split);
        $this->assertSame(UserStudio::STATUS_ACTIVE, $link->status);
        $this->assertSame(UserStudio::CONNECTION_NOT_CONNECTED, $link->connection_type);
        $this->assertNotNull($link->studio_id);

        Mail::assertSent(ArtistStudioJoinRequestMail::class);

        UserStudio::query()->create([
            'user_id' => $artist->id,
            'status' => UserStudio::STATUS_ACTIVE,
            'connection_type' => UserStudio::CONNECTION_NOT_ON_BOOKPAY,
            'studio_name' => 'Another Studio',
            'payout' => false,
        ]);

        $this->actingAs($artist)
            ->get(route('settings.studio'))
            ->assertOk()
            ->assertSee('Invitation sent')
            ->assertSee('js-studio-resend-workplace-invite', false);

        $this->postJson(route('settings.studio.resend-workplace-invite'), [
            'user_studio_id' => $link->id,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Invitation email resent.');

        Mail::assertSent(ArtistStudioJoinRequestMail::class, 2);
    }

    public function test_direct_invite_requires_an_email_when_enabled(): void
    {
        Mail::fake();
        $artist = User::factory()->create(['role' => 'artist']);

        $response = $this->actingAs($artist)->postJson(route('settings.studio.change.store'), [
            'mode' => 'add',
            'path' => 'not_on_bookpay',
            'payout_mode' => 'direct',
            'workspace_type' => 'shop',
            'relationship' => 'resident',
            'studio_name' => 'New Studio',
            'studio_address' => '12 Main Street',
            'direct_invite_enabled' => 1,
            'direct_invite_studio_name' => 'New Studio',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('direct_invite_studio_email');
        $this->assertDatabaseCount('user_studios', 0);
        Mail::assertNothingSent();
    }
}
