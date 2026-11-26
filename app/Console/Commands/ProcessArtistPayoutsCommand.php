<?php

namespace App\Console\Commands;

use App\Services\ArtistPayoutService;
use Illuminate\Console\Command;

class ProcessArtistPayoutsCommand extends Command
{
    protected $signature = 'artist-payouts:process';

    protected $description = 'Transfer artist/studio payouts for paid bookings past the cancellation deadline (completion not required)';

    public function handle(ArtistPayoutService $artistPayoutService): int
    {
        $stats = $artistPayoutService->processEligibleBookings();

        $this->info(sprintf(
            'Artist payouts: %d processed, %d skipped, %d failed.',
            $stats['processed'],
            $stats['skipped'],
            $stats['failed'],
        ));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
