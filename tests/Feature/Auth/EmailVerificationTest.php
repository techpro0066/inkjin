<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => 'artist',
            'on_boarding' => 'no',
        ]);

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified_with_code(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => 'artist',
            'on_boarding' => 'no',
        ]);

        Event::fake();

        $user->sendEmailVerificationNotification();

        $response = $this->actingAs($user)->post('/email/verify-code', [
            'code' => '4242',
        ]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect();
        $this->assertStringContainsString('verified=1', $response->headers->get('Location') ?? '');
    }

    public function test_email_is_not_verified_with_invalid_code(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => 'artist',
            'on_boarding' => 'no',
        ]);

        $user->sendEmailVerificationNotification();

        $this->actingAs($user)->post('/email/verify-code', [
            'code' => '9999',
        ]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
