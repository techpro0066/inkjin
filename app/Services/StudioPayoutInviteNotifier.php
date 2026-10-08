<?php

namespace App\Services;

use App\Mail\StudioPayoutInfoRequestMail;
use App\Models\Studio;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserStudio;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

/**
 * Studio payout invitation (same signed link / mail as onboarding payment settings).
 */
class StudioPayoutInviteNotifier
{
    public function send(User $artist, UserDetail $userDetail, Studio $studio, ?UserStudio $link = null): bool
    {
        $this->prepareUserDetail($userDetail, $studio, $link);

        $email = strtolower(trim((string) ($studio->email ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Studio payout invite skipped — studio has no email', [
                'studio_id' => $studio->id,
                'artist_id' => $artist->id,
            ]);

            return false;
        }

        $artistName = trim(($artist->first_name ?? '').' '.($artist->last_name ?? ''));
        if ($artistName === '') {
            $artistName = $artist->user_name ?? $artist->email ?? 'Artist';
        }

        $relationship = $link?->relationship ?? $userDetail->studio_relationship_type ?? null;

        $formUrl = URL::temporarySignedRoute(
            'studio.payout-info.show',
            now()->addDays(30),
            ['userDetail' => $userDetail->id]
        );
        $approveUrl = $formUrl;
        $declineUrl = URL::temporarySignedRoute(
            'studio.payout-artist-link.decline',
            now()->addDays(30),
            ['userDetail' => $userDetail->id]
        );

        try {
            Mail::to($email)->send(new StudioPayoutInfoRequestMail(
                $studio->name ?? 'Studio',
                $artistName,
                $formUrl,
                true,
                $approveUrl,
                $declineUrl,
                false,
                $this->relationshipPhrase($relationship),
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send studio payout invite email', [
                'studio_id' => $studio->id,
                'user_detail_id' => $userDetail->id,
                'artist_id' => $artist->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function prepareUserDetail(UserDetail $userDetail, Studio $studio, ?UserStudio $link): void
    {
        $table = $userDetail->getTable();

        if (Schema::hasColumn($table, 'payment_type')) {
            $userDetail->payment_type = 'studio_account';
        }
        if (Schema::hasColumn($table, 'payment_status')
            && (string) ($userDetail->payment_status ?? '') !== 'approved') {
            $userDetail->payment_status = 'pending';
        }
        if (Schema::hasColumn($table, 'studio_id')) {
            $userDetail->setAttribute('studio_id', $studio->id);
        }
        if (Schema::hasColumn($table, 'studio_name')) {
            $userDetail->studio_name = $studio->name ?: ($link?->studio_name ?: $userDetail->studio_name);
        }

        $relationship = $link?->relationship;
        if (Schema::hasColumn($table, 'studio_relationship_type') && $relationship) {
            $userDetail->studio_relationship_type = $relationship;
        }

        $split = $link?->revenue_split;
        if (Schema::hasColumn($table, 'studio_revenue_artist_percent') && $split !== null) {
            $userDetail->studio_revenue_artist_percent = (int) $split;
        }

        if ($userDetail->isDirty()) {
            $userDetail->save();
        }
    }

    private function relationshipPhrase(?string $relationshipType): string
    {
        return match ($relationshipType) {
            'co_owner' => 'co-owned studio',
            'resident' => 'resident studio',
            'collective_member' => 'collective studio',
            'apprentice' => 'apprentice studio',
            'other' => 'partner studio',
            default => 'studio',
        };
    }
}
