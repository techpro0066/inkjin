<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserStudio;
use Illuminate\Validation\ValidationException;

class StudioArtistLimit
{
    public const MAX_LINKED_ARTISTS = 6;

    public function ensureCanLink(
        int $studioId,
        ?int $artistUserId = null,
        string $errorField = 'artist',
        ?int $exceptUserStudioId = null,
    ): void {
        if (! UserStudio::tableReady()) {
            return;
        }

        if ($artistUserId !== null) {
            if (User::query()->whereKey($artistUserId)->where('role', 'studio')->exists()) {
                return;
            }

            $alreadyLinked = UserStudio::query()
                ->where('studio_id', $studioId)
                ->where('user_id', $artistUserId)
                ->where('status', UserStudio::STATUS_ACTIVE)
                ->when($exceptUserStudioId !== null, fn ($query) => $query->where('id', '!=', $exceptUserStudioId))
                ->exists();

            if ($alreadyLinked) {
                return;
            }
        }

        $artistCount = UserStudio::query()
            ->where('studio_id', $studioId)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->whereHas('user', fn ($query) => $query->where('role', 'artist'))
            ->when($exceptUserStudioId !== null, fn ($query) => $query->where('id', '!=', $exceptUserStudioId))
            ->distinct()
            ->count('user_id');

        if ($artistCount >= self::MAX_LINKED_ARTISTS) {
            throw ValidationException::withMessages([
                $errorField => 'This studio can link up to 6 artists. Guest artists do not count toward this limit.',
            ]);
        }
    }
}
