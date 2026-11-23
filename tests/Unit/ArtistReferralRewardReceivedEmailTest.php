<?php

namespace Tests\Unit;

use App\Models\ArtistReferral;
use App\Models\User;
use Tests\TestCase;

class ArtistReferralRewardReceivedEmailTest extends TestCase
{
    public function test_template_renders_the_new_referral_reward_layout(): void
    {
        $referral = new ArtistReferral(['reward_amount' => 25]);
        $referral->setRelation('referred', new User([
            'first_name' => 'Julian',
            'last_name' => 'Ink',
            'email' => 'julian@example.com',
        ]));
        $referral->setRelation('qualifiedBooking', null);

        $html = view('emails.artist-referral-reward-received', [
            'referral' => $referral,
            'referEarnUrl' => 'https://example.test/refer-earn',
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('$25.00', $html);
        $this->assertStringContainsString('View Refer &amp; Earn', $html);
    }
}
