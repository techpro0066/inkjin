<?php

namespace App\Services;

use App\Mail\ArtistRevenueSplitAnsweredMail;
use App\Mail\ArtistRevenueSplitRequestMail;
use App\Models\Studio;
use App\Models\User;
use App\Models\UserStudio;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RevenueSplitChangeService
{
    public function __construct(
        private readonly ArtistStudioJoinRequestNotifier $studioNotifier,
    ) {
    }

    /**
     * Artist asks studio for a new active-link revenue split.
     * Keeps current revenue_split until the studio accepts.
     */
    public function proposeByArtist(User $artist, UserStudio $link, int $artistPercent): UserStudio
    {
        $artistPercent = max(1, min(99, $artistPercent));

        if ((int) $link->user_id !== (int) $artist->id) {
            throw ValidationException::withMessages([
                'user_studio_id' => 'This studio link does not belong to you.',
            ]);
        }

        if ((int) $link->status !== UserStudio::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'user_studio_id' => 'You can only ask for a new split on an active studio link.',
            ]);
        }

        if (! $link->isRevenueSplitPayout()) {
            throw ValidationException::withMessages([
                'user_studio_id' => 'This studio is not on a revenue split.',
            ]);
        }

        $current = (int) $link->revenue_split;
        if ($artistPercent === $current) {
            throw ValidationException::withMessages([
                'artist_percent' => 'Choose a split that is different from the current one.',
            ]);
        }

        if (! Schema::hasColumn($link->getTable(), 'proposed_revenue_split')) {
            throw ValidationException::withMessages([
                'artist_percent' => 'Split requests are not available yet.',
            ]);
        }

        $link->proposed_revenue_split = $artistPercent;
        if (Schema::hasColumn($link->getTable(), 'split_proposed_by')) {
            $link->split_proposed_by = UserStudio::SPLIT_PROPOSED_BY_ARTIST;
        }
        if (Schema::hasColumn($link->getTable(), 'split_proposed_at')) {
            $link->split_proposed_at = now();
        }
        $link->save();

        $link->loadMissing('studio');
        $studio = $link->studio;
        if ($studio instanceof Studio) {
            $this->notifyStudio($artist, $link, $studio);
        }

        return $link->fresh() ?? $link;
    }

    public function acceptByStudio(Studio $studio, UserStudio $link): UserStudio
    {
        $this->assertStudioOwnsOpenArtistProposal($studio, $link);

        $before = (int) $link->revenue_split;
        $proposed = (int) $link->proposed_revenue_split;
        $link->revenue_split = max(0, min(100, $proposed));
        $this->clearProposalFields($link);
        $link->save();

        $fresh = $link->fresh() ?? $link;
        $this->notifyArtist($fresh, true, $before, $proposed);

        return $fresh;
    }

    public function declineByStudio(Studio $studio, UserStudio $link): UserStudio
    {
        $this->assertStudioOwnsOpenArtistProposal($studio, $link);

        $before = (int) $link->revenue_split;
        $proposed = (int) $link->proposed_revenue_split;
        $this->clearProposalFields($link);
        $link->save();

        $fresh = $link->fresh() ?? $link;
        $this->notifyArtist($fresh, false, $before, $proposed);

        return $fresh;
    }

    public function hasOpenArtistProposal(UserStudio $link): bool
    {
        if ((int) $link->status !== UserStudio::STATUS_ACTIVE) {
            return false;
        }

        if ($link->proposed_revenue_split === null) {
            return false;
        }

        if (! Schema::hasColumn($link->getTable(), 'split_proposed_by')) {
            return true;
        }

        return ($link->split_proposed_by ?? null) === UserStudio::SPLIT_PROPOSED_BY_ARTIST;
    }

    private function assertStudioOwnsOpenArtistProposal(Studio $studio, UserStudio $link): void
    {
        if ((int) $link->studio_id !== (int) $studio->id) {
            throw ValidationException::withMessages([
                'user_studio_id' => 'Artist link not found.',
            ]);
        }

        if (! $this->hasOpenArtistProposal($link)) {
            throw ValidationException::withMessages([
                'user_studio_id' => 'There is no open split request for this artist.',
            ]);
        }
    }

    private function clearProposalFields(UserStudio $link): void
    {
        $link->proposed_revenue_split = null;
        if (Schema::hasColumn($link->getTable(), 'split_proposed_by')) {
            $link->split_proposed_by = null;
        }
        if (Schema::hasColumn($link->getTable(), 'split_proposed_at')) {
            $link->split_proposed_at = null;
        }
    }

    private function notifyArtist(
        UserStudio $link,
        bool $accepted,
        int $beforeArtistPercent,
        int $proposedArtistPercent,
    ): void {
        $link->loadMissing(['user', 'studio']);
        $email = trim((string) ($link->user?->email ?? ''));
        if ($email === '') {
            return;
        }

        try {
            Mail::to($email)->send(new ArtistRevenueSplitAnsweredMail(
                userStudio: $link,
                accepted: $accepted,
                beforeArtistPercent: $beforeArtistPercent,
                proposedArtistPercent: $proposedArtistPercent,
                appliesFrom: now(),
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send artist revenue split answered email', [
                'user_studio_id' => $link->id,
                'accepted' => $accepted,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyStudio(User $artist, UserStudio $link, Studio $studio): void
    {
        $email = $this->studioNotifier->resolveStudioEmail($studio);
        if ($email === null) {
            Log::warning('Artist revenue split request email skipped — studio has no email', [
                'studio_id' => $studio->id,
                'user_studio_id' => $link->id,
                'artist_id' => $artist->id,
            ]);

            return;
        }

        $currentArtist = (int) $link->revenue_split;
        $proposedArtist = (int) $link->proposed_revenue_split;

        try {
            Mail::to($email)->send(new ArtistRevenueSplitRequestMail(
                userStudio: $link,
                currentArtistPercent: $currentArtist,
                proposedArtistPercent: $proposedArtist,
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send artist revenue split request email', [
                'studio_id' => $studio->id,
                'user_studio_id' => $link->id,
                'artist_id' => $artist->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
