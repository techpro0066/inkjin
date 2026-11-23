<?php

namespace Tests\Unit;

use Tests\TestCase;

class ArtistWelcomeEmailTest extends TestCase
{
    public function test_welcome_email_uses_reference_layout_and_three_setup_steps(): void
    {
        $html = view('emails.artist-welcome', [
            'artistFirstName' => 'Julian',
            'dashboardUrl' => 'https://example.test/dashboard',
            'payoutsUrl' => 'https://example.test/payouts',
            'remainingLabel' => 'three things',
            'showPayoutStep' => true,
            'showWorkingHoursStep' => true,
            'showOpenBooksStep' => true,
            'publicPageUrl' => 'inkjin.com/@julianink',
            'showFounding' => true,
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('Finish payouts', $html);
        $this->assertStringContainsString('Set your working hours', $html);
        $this->assertStringContainsString('Open your books', $html);
        $this->assertStringContainsString('You&rsquo;re a Founding artist', $html);
    }

    public function test_completed_payout_and_open_books_leave_fewer_steps(): void
    {
        $html = view('emails.artist-welcome', [
            'artistFirstName' => 'Julian',
            'dashboardUrl' => 'https://example.test/dashboard',
            'payoutsUrl' => 'https://example.test/payouts',
            'remainingLabel' => 'one thing',
            'showPayoutStep' => false,
            'showWorkingHoursStep' => false,
            'showOpenBooksStep' => true,
            'publicPageUrl' => 'inkjin.com/@julianink',
            'showFounding' => false,
        ])->render();

        $this->assertStringNotContainsString('Finish payouts', $html);
        $this->assertStringNotContainsString('Set your working hours', $html);
        $this->assertStringContainsString('Open your books', $html);
        $this->assertStringNotContainsString('Founding artist', $html);
    }
}
