<?php

namespace Tests\Unit;

use App\Models\CustomRequest;
use App\Models\User;
use App\Models\UserDetail;
use Tests\TestCase;

class CustomRequestDeclinedUserEmailTest extends TestCase
{
    public function test_client_decline_notification_uses_reference_layout(): void
    {
        $request = new CustomRequest([
            'reason_decline' => 'I am fully booked',
            'is_guest' => false,
        ]);
        $request->id = 43;
        $request->setRelation('user', new User([
            'first_name' => 'Louis',
            'last_name' => 'Hall',
        ]));
        $artist = new User([
            'first_name' => 'Julian',
            'last_name' => 'Ink',
        ]);
        $detail = new UserDetail([
            'display_name' => 'Julian Ink',
        ]);
        $detail->setRelation('user', $artist);
        $artist->setRelation('userDetail', $detail);
        $request->setRelation('artist', $artist);

        $html = view('emails.custom-request-declined-user', [
            'customRequest' => $request,
            'dashboardUrl' => 'https://example.test/dashboard',
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('Hi Louis Hall', $html);
        $this->assertStringContainsString('#CR-000043', $html);
        $this->assertStringContainsString('I am fully booked', $html);
    }
}
