<?php

namespace Tests\Unit;

use App\Models\ArtistPayout;
use App\Services\ArtistPayoutService;
use Tests\TestCase;

class RecordedArtistPayoutAmountTest extends TestCase
{
    public function test_it_uses_the_recorded_artist_amount_when_available(): void
    {
        $payout = new ArtistPayout([
            'amount' => 192.71,
            'artist_amount' => 96.35,
            'studio_amount' => 96.36,
        ]);

        $this->assertSame(
            96.35,
            app(ArtistPayoutService::class)->recordedArtistPayoutAmount($payout)
        );
    }

    public function test_it_excludes_recorded_studio_amount_when_artist_amount_is_missing(): void
    {
        $payout = new ArtistPayout([
            'amount' => 192.71,
            'studio_amount' => 96.36,
        ]);

        $this->assertSame(
            96.35,
            app(ArtistPayoutService::class)->recordedArtistPayoutAmount($payout)
        );
    }
}
