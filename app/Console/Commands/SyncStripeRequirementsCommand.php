<?php

namespace App\Console\Commands;

use App\Services\StripeRequirementSyncService;
use Illuminate\Console\Command;

class SyncStripeRequirementsCommand extends Command
{
    protected $signature = 'stripe:sync-requirements';

    protected $description = 'Check ready Stripe Connect accounts for new requirements and update payout status';

    public function handle(StripeRequirementSyncService $sync): int
    {
        $stats = $sync->syncReadyArtistAccounts();

        $this->info(sprintf(
            'Stripe requirements sync: checked=%d flagged=%d cleared=%d failed=%d',
            $stats['checked'],
            $stats['flagged'],
            $stats['cleared'],
            $stats['failed'],
        ));

        return self::SUCCESS;
    }
}
