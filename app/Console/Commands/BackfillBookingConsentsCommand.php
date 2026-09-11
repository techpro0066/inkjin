<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingConsentService;
use Illuminate\Console\Command;

class BackfillBookingConsentsCommand extends Command
{
    protected $signature = 'consents:backfill {--send : Also send any due emails after backfill}';

    protected $description = 'Create missing consent_answers rows for confirmed upcoming bookings';

    public function handle(BookingConsentService $consents): int
    {
        $created = 0;
        $updated = 0;

        Booking::query()
            ->confirmed()
            ->where('booking_date', '>=', now()->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use ($consents, &$created, &$updated) {
                foreach ($bookings as $booking) {
                    $before = $booking->consentAnswer;
                    $row = $consents->syncForBooking($booking);
                    if (! $before && $row) {
                        $created++;
                    } elseif ($row) {
                        $updated++;
                    }
                }
            });

        $this->info(sprintf('Consent backfill: %d created, %d refreshed.', $created, $updated));

        if ($this->option('send')) {
            $result = $consents->sendDue();
            $this->info(sprintf(
                'Consent send: %d sent, %d failed, %d skipped.',
                $result['sent'],
                $result['failed'],
                $result['skipped']
            ));
        }

        return self::SUCCESS;
    }
}
