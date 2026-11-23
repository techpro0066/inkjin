<?php

namespace Tests\Unit;

use App\Models\ArtistReferral;
use App\Models\User;
use Tests\TestCase;

class ArtistReferralRewardRejectedEmailTest extends TestCase
{
    public function test_template_renders_the_new_rejected_layout(): void
    {
        $referral = new ArtistReferral(['rejection_reason' => 'Booking did not qualify']);
        $referral->setRelation('referred', new User([
            'first_name' => 'Julian',
            'last_name' => 'Ink',
            'email' => 'julian@example.com',
        ]));
        $referral->setRelation('qualifiedBooking', null);

        $html = view('emails.artist-referral-reward-rejected', [
            'referral' => $referral,
            'referEarnUrl' => 'https://example.test/refer-earn',
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('not approved', $html);
        $this->assertStringContainsString('Booking did not qualify', $html);
    }
}
