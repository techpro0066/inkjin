<?php

namespace App\Services;

use App\Models\Studio;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserStudio;
use Illuminate\Support\Facades\Schema;

class ArtistDirectStudioInviteService
{
    public function send(
        User $artist,
        UserDetail $userDetail,
        string $name,
        string $email,
        ?UserStudio $row = null,
        bool $setArtistPaymentType = false,
        bool $preserveStudioName = false
    ): bool {
        if (! UserStudio::tableReady()) {
            return false;
        }

        $previousStudioName = $userDetail->studio_name;
        if ($setArtistPaymentType && Schema::hasColumn($userDetail->getTable(), 'payment_type')) {
            $userDetail->payment_type = 'artist_account';
        }

        $studio = Studio::query()->firstOrCreate(
            ['email' => $email],
            ['name' => $name !== '' ? $name : 'Studio']
        );

        if ($name !== '' && $name !== 'Studio' && (blank($studio->name) || $studio->name === 'Studio')) {
            $studio->name = $name;
            $studio->save();
        } elseif ($name !== '' && blank($studio->email)) {
            $studio->email = $email;
            $studio->save();
        }

        if (empty($studio->email)) {
            $studio->email = $email;
            $studio->save();
        }

        if (! $row) {
            $row = UserStudio::query()
                ->where('user_id', (int) $artist->id)
                ->where('studio_id', (int) $studio->id)
                ->orderByDesc('id')
                ->first();

            if (! $row) {
                $row = UserStudio::activeForUser((int) $artist->id);
                if ($row && ! empty($row->studio_id) && (int) $row->studio_id !== (int) $studio->id) {
                    $row = null;
                }
            }

            if (! $row) {
                $row = new UserStudio([
                    'user_id' => (int) $artist->id,
                ]);
            }
        }

        $row->studio_id = $studio->id;
        $row->studio_name = $name !== '' ? $name : ($studio->name ?: 'Studio');
        $row->payout = false;
        $row->revenue_split = null;
        $row->status = UserStudio::STATUS_ACTIVE;
        $row->connection_type = UserStudio::CONNECTION_NOT_CONNECTED;
        if (Schema::hasColumn($row->getTable(), 'joined_via')) {
            $row->joined_via = UserStudio::JOINED_VIA_INVITE;
        }
        $row->save();

        $sent = app(ArtistStudioJoinRequestNotifier::class)->send($artist, $row, $studio, false);

        if ($preserveStudioName
            && Schema::hasColumn($userDetail->getTable(), 'studio_name')
            && $userDetail->studio_name !== $previousStudioName) {
            $userDetail->studio_name = $previousStudioName;
            $userDetail->save();
        }

        return $sent;
    }
}
