<?php

namespace Tests\Unit;

use App\Models\ArtistReferral;
use App\Models\Booking;
use App\Models\User;
use Tests\TestCase;

class AdminArtistReferralRewardEmailTest extends TestCase
{
    public function test_template_renders_the_new_admin_referral_layout(): void
    {
        $referrer = new User(['first_name' => 'Alex', 'last_name' => 'Moreau', 'email' => 'referrer@example.com']);
        $referred = new User(['first_name' => 'Julian', 'last_name' => 'Ink', 'email' => 'referred@example.com']);
        $referral = new ArtistReferral([
            'reward_amount' => 25,
        ]);
        $referral->setRelation('referrer', $referrer);
        $referral->setRelation('referred', $referred);

        $booking = new Booking();
        $booking->id = 42;

        $html = view('emails.admin-artist-referral-reward', [
            'referral' => $referral,
            'booking' => $booking,
            'qualifyReason' => 'First booking completed',
            'referralsUrl' => 'https://example.test/admin/referrals',
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('Reward amount', $html);
        $this->assertStringContainsString('View referrals', $html);
    }
}
