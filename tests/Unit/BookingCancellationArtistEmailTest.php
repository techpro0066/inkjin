<?php

namespace Tests\Unit;

use Tests\TestCase;

class BookingCancellationArtistEmailTest extends TestCase
{
    public function test_artist_cancellation_template_uses_reference_layout(): void
    {
        $html = view('emails.booking-cancellation-artist', [
            'artistName' => 'Julian Ink',
            'customerName' => 'Louis Hall',
            'customerEmail' => 'louis@example.com',
            'tattooTitle' => 'Dragon sleeve',
            'locationLabel' => 'Open Ink Studio',
            'bookingDate' => 'Wednesday, September 30, 2026',
            'bookingTime' => '12:00 PM - 8:00 PM',
            'cancelledAt' => 'Monday, September 21, 2026 10:00 AM',
            'cancellationReason' => 'Schedule changed',
            'bookingId' => 42,
            'isNoShow' => false,
            'cancellationType' => 'client',
            'depositForfeited' => 300,
            'refundAmount' => 0,
            'currencySymbol' => '€',
            'hasRefund' => false,
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('Hi Julian Ink', $html);
        $this->assertStringContainsString('Dragon sleeve', $html);
        $this->assertStringContainsString('Refund status', $html);
    }

    public function test_artist_no_show_variant_renders_no_show_notice(): void
    {
        $html = view('emails.booking-cancellation-artist', [
            'artistName' => 'Julian Ink',
            'customerName' => 'Louis Hall',
            'customerEmail' => 'louis@example.com',
            'tattooTitle' => 'Dragon sleeve',
            'locationLabel' => 'Open Ink Studio',
            'bookingDate' => 'Wednesday, September 30, 2026',
            'bookingTime' => '12:00 PM - 8:00 PM',
            'cancelledAt' => 'Wednesday, September 30, 2026 10:00 PM',
            'cancellationReason' => '',
            'bookingId' => 42,
            'isNoShow' => true,
            'cancellationType' => 'client',
            'depositForfeited' => 300,
            'refundAmount' => 0,
            'currencySymbol' => '€',
            'hasRefund' => false,
        ])->render();

        $this->assertStringContainsString('No-show', $html);
        $this->assertStringContainsString('did not attend', $html);
    }
}
