<?php

namespace Tests\Unit;

use App\Models\Booking;
use Tests\TestCase;

class BookingCompletionCodeEmailTest extends TestCase
{
    public function test_completion_code_template_uses_reference_layout(): void
    {
        $booking = new Booking();
        $booking->id = 42;

        $html = view('emails.booking-completion-code', [
            'booking' => $booking,
            'code' => 'A7K2PX',
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('A7K2PX', $html);
        $this->assertStringContainsString('#INK-000042', $html);
        $this->assertStringContainsString('Do not share', $html);
    }
}
