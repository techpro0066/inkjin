<?php

namespace Tests\Unit;

use App\Models\CustomRequest;
use App\Models\User;
use Tests\TestCase;

class CustomRequestDeclinedArtistEmailTest extends TestCase
{
    public function test_artist_decline_confirmation_uses_reference_layout(): void
    {
        $request = new CustomRequest([
            'reason_decline' => 'Unable to take this project',
            'is_guest' => false,
        ]);
        $request->id = 42;
        $request->setRelation('user', new User([
            'first_name' => 'Louis',
            'last_name' => 'Hall',
        ]));
        $request->setRelation('artist', null);

        $html = view('emails.custom-request-declined-artist', [
            'customRequest' => $request,
            'requestsUrl' => 'https://example.test/requests',
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('Louis', $html);
        $this->assertStringContainsString('#CR-000042', $html);
        $this->assertStringContainsString('Unable to take this project', $html);
    }
}
