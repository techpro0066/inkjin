<?php

namespace App\Console\Commands;

use App\Services\BookingAppointmentReminderService;
use Illuminate\Console\Command;

class SendAppointmentRemindersCommand extends Command
{
    protected $signature = 'bookings:send-appointment-reminders';

    protected $description = 'Email clients appointment reminders 72h and 24h before confirmed sessions';

    public function handle(BookingAppointmentReminderService $reminders): int
    {
        $result = $reminders->sendDue();

        $this->info(sprintf(
            'Appointment reminders: %d (72h), %d (24h), %d failed.',
            $result['sent_72h'],
            $result['sent_24h'],
            $result['failed']
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
