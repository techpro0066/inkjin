<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Services\CancellationService;
use App\Services\CancellationWindowReminderService;
use Carbon\Carbon;
use Tests\TestCase;

class CancellationWindowReminderTest extends TestCase
{
    public function test_it_calculates_the_cancellation_deadline_for_the_booking_window(): void
    {
        Carbon::setTestNow('2026-09-01 09:00:00');

        $booking = new Booking([
            'booking_date' => '2026-09-30',
            'start_time_utc' => '12:00',
            'cancellation_window_hours' => 168,
        ]);

        $service = new CancellationWindowReminderService(new CancellationService());
        $deadline = $service->cancellationDeadline($booking);

        $this->assertNotNull($deadline);
        $this->assertSame('2026-09-23 12:00:00', $deadline->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-21 12:00:00', $deadline->copy()->subHours(48)->format('Y-m-d H:i:s'));
    }
}
