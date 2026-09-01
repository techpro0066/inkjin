<?php

namespace App\Console\Commands;

use App\Services\ArtistReferralRewardService;
use Illuminate\Console\Command;

class ProcessArtistReferralRewardsCommand extends Command
{
    protected $signature = 'artist-referrals:process-rewards';

    protected $description = 'Notify admin when referred artists qualify for referrer rewards';

    public function handle(ArtistReferralRewardService $referralRewards): int
    {
        $stats = $referralRewards->processPendingReferrals();

        $this->info(sprintf(
            'Artist referral rewards: %d processed, %d notified, %d skipped.',
            $stats['processed'],
            $stats['notified'],
            $stats['skipped'],
        ));

        return self::SUCCESS;
    }
}
