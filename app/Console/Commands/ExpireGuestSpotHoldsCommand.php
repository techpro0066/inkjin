<?php

namespace App\Console\Commands;

use App\Services\GuestSpotHoldService;
use Illuminate\Console\Command;

class ExpireGuestSpotHoldsCommand extends Command
{
    protected $signature = 'guest-spots:expire-holds';

    protected $description = 'Release expired guest spot quote holds so remaining capacity is available again';

    public function handle(GuestSpotHoldService $holds): int
    {
        $count = $holds->expireDueHolds();
        $this->info("Expired {$count} guest spot hold(s).");

        return self::SUCCESS;
    }
}
