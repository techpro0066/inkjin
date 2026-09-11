<?php

namespace App\Console\Commands;

use App\Services\BookingConsentService;
use Illuminate\Console\Command;

class SendConsentFormRemindersCommand extends Command
{
    protected $signature = 'consents:send-due';

    protected $description = 'Email clients consent forms ~24 hours before confirmed bookings (when send_automatically is on)';

    public function handle(BookingConsentService $consents): int
    {
        $result = $consents->sendDue();

        $this->info(sprintf(
            'Consent forms: %d sent, %d failed, %d skipped.',
            $result['sent'],
            $result['failed'],
            $result['skipped']
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
