<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserDetail;
use App\Support\ArtistNotificationPreferences;
use Tests\TestCase;

class ArtistNotificationPreferenceHelperTest extends TestCase
{
    public function test_only_categories_listed_as_disabled_are_suppressed(): void
    {
        $artist = new User;
        $artist->setRelation('userDetail', new UserDetail([
            'not_allowed_notification' => [ArtistNotificationPreferences::NEW_BOOKINGS],
        ]));

        $this->assertFalse(ArtistNotificationPreferences::allows(
            $artist,
            ArtistNotificationPreferences::NEW_BOOKINGS
        ));
        $this->assertTrue(ArtistNotificationPreferences::allows(
            $artist,
            ArtistNotificationPreferences::NEW_REQUESTS
        ));
    }

    public function test_unconfigured_artist_categories_are_allowed_by_default(): void
    {
        $this->assertTrue(ArtistNotificationPreferences::allows(
            new User,
            ArtistNotificationPreferences::NEW_BOOKINGS
        ));
    }
}
