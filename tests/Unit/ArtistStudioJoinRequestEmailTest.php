<?php

namespace Tests\Unit;

use Tests\TestCase;

class ArtistStudioJoinRequestEmailTest extends TestCase
{
    public function test_existing_studio_join_request_uses_reference_layout(): void
    {
        $html = view('emails.artist-studio-join-request', [
            'studioName' => 'Open Ink Studio',
            'artistName' => 'Alex Moreau',
            'artistInitials' => 'AM',
            'artistMeta' => '@alexmoreau · Athens',
            'artistAvatarUrl' => null,
            'actionUrl' => 'https://example.test/request',
            'hasStudioAccount' => true,
            'relationshipLabel' => 'Resident',
            'artistPercent' => 60,
        ])->render();

        $this->assertStringContainsString('width="520"', $html);
        $this->assertStringContainsString('Review request', $html);
        $this->assertStringContainsString('Alex Moreau 60% · Studio 40%', $html);
    }

    public function test_new_studio_rent_invitation_without_split(): void
    {
        $html = view('emails.artist-studio-join-request', [
            'studioName' => 'Black Anchor Tattoo',
            'artistName' => 'Julian Ink',
            'artistInitials' => 'JI',
            'artistMeta' => '@julianink · Athens',
            'artistAvatarUrl' => null,
            'actionUrl' => 'https://example.test/invite',
            'hasStudioAccount' => false,
            'relationshipLabel' => 'Resident',
            'artistPercent' => null,
        ])->render();

        $this->assertStringContainsString('rents a workstation', $html);
        $this->assertStringContainsString('See the invitation', $html);
    }

    public function test_new_studio_revenue_split_invitation(): void
    {
        $html = view('emails.artist-studio-join-request', [
            'studioName' => 'Black Anchor Tattoo',
            'artistName' => 'Julian Ink',
            'artistInitials' => 'JI',
            'artistMeta' => '@julianink · Athens',
            'artistAvatarUrl' => null,
            'actionUrl' => 'https://example.test/invite',
            'hasStudioAccount' => false,
            'relationshipLabel' => 'Resident',
            'artistPercent' => 60,
        ])->render();

        $this->assertStringContainsString('Your share of each booking', $html);
        $this->assertStringContainsString('See the invitation', $html);
    }
}
