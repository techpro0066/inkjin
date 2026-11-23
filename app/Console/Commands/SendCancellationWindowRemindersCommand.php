<?php

namespace App\Console\Commands;

use App\Services\CancellationWindowReminderService;
use Illuminate\Console\Command;

class SendCancellationWindowRemindersCommand extends Command
{
    protected $signature = 'bookings:send-cancellation-window-reminders';

    protected $description = 'Email clients 48 hours before their free-cancellation window closes';

    public function handle(CancellationWindowReminderService $reminders): int
    {
        $result = $reminders->sendDue();

        $this->info(sprintf(
            'Cancellation-window reminders: %d sent, %d failed.',
            $result['sent'],
            $result['failed']
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
