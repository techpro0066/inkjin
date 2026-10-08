<?php

namespace App\Services;

use App\Mail\ArtistStudioJoinRequestMail;
use App\Models\Studio;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserStudio;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class ArtistStudioJoinRequestNotifier
{
    public const RELATIONSHIP_LABELS = [
        'co_owner' => 'Co-owner',
        'resident' => 'Resident',
        'collective_member' => 'Collective Member',
        'apprentice' => 'Apprentice',
        'other' => 'Other (Contract Artist, Freelancer)',
    ];

    public function send(User $artist, UserStudio $link, Studio $studio, bool $isResend = false): bool
    {
        $studio->loadMissing('user');

        $email = $this->resolveStudioEmail($studio);
        if ($email === null) {
            Log::warning('Artist studio join request email skipped — studio has no email', [
                'studio_id' => $studio->id,
                'user_studio_id' => $link->id,
                'artist_id' => $artist->id,
            ]);

            return false;
        }

        $artistName = trim(($artist->first_name ?? '').' '.($artist->last_name ?? ''));
        if ($artistName === '') {
            $artistName = $artist->user_name ?? $artist->email ?? 'Artist';
        }

        $relationship = $link->relationship;
        $relationshipLabel = $relationship && isset(self::RELATIONSHIP_LABELS[$relationship])
            ? self::RELATIONSHIP_LABELS[$relationship]
            : null;

        $artistPercent = $link->payout && $link->revenue_split !== null
            ? (int) $link->revenue_split
            : null;

        $hasStudioAccount = $this->studioHasAccount($studio, $email);
        $actionUrl = $hasStudioAccount
            ? route('studio.dashboard')
            : $this->invitationUrl($artist, $studio, $link);

        if ($actionUrl === null) {
            return false;
        }

        try {
            Mail::to($email)->send(new ArtistStudioJoinRequestMail(
                studioName: trim((string) ($studio->name ?: 'Studio')),
                artistName: $artistName,
                actionUrl: $actionUrl,
                hasStudioAccount: $hasStudioAccount,
                relationshipLabel: $relationshipLabel,
                artistPercent: $artistPercent,
                isResend: $isResend,
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send artist studio join request email', [
                'studio_id' => $studio->id,
                'user_studio_id' => $link->id,
                'artist_id' => $artist->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Studio already has a Bookpay login (linked user or matching account email).
     */
    public function studioHasAccount(Studio $studio, ?string $resolvedEmail = null): bool
    {
        $studio->loadMissing('user');

        if (! empty($studio->user_id) || $studio->user !== null) {
            return true;
        }

        $email = $resolvedEmail ?? $this->resolveStudioEmail($studio);
        if ($email === null) {
            return false;
        }

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('role', 'studio')
            ->exists();
    }

    public function resolveStudioEmail(Studio $studio): ?string
    {
        $studio->loadMissing('user');

        foreach ([
            $studio->user?->email ?? null,
            $studio->email ?? null,
            $studio->contact_email ?? null,
        ] as $candidate) {
            $email = strtolower(trim((string) $candidate));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return null;
    }

    /**
     * Same signed invite URL as onboarding payout: studio.payout-info.show.
     */
    private function invitationUrl(User $artist, Studio $studio, UserStudio $link): ?string
    {
        $userDetail = $artist->userDetail;
        if (! $userDetail) {
            $userDetail = UserDetail::query()->firstOrCreate(['user_id' => (int) $artist->id]);
        }

        $this->prepareUserDetailForInvite($userDetail, $studio, $link);

        return URL::temporarySignedRoute(
            'studio.payout-info.show',
            now()->addDays(30),
            ['userDetail' => $userDetail->id]
        );
    }

    /**
     * payout-info.show requires payment_type=studio_account.
     * Studio id lives on user_studios after migration (user_details.studio_id may be dropped).
     */
    private function prepareUserDetailForInvite(UserDetail $userDetail, Studio $studio, UserStudio $link): void
    {
        $table = $userDetail->getTable();

        if (Schema::hasColumn($table, 'payment_type')) {
            $userDetail->payment_type = 'studio_account';
        }
        if (Schema::hasColumn($table, 'payment_status')
            && (string) ($userDetail->payment_status ?? '') !== 'approved') {
            $userDetail->payment_status = 'pending';
        }
        // Legacy column — only when migration has not dropped it yet.
        if (Schema::hasColumn($table, 'studio_id')) {
            $userDetail->setAttribute('studio_id', $studio->id);
        }
        if (Schema::hasColumn($table, 'studio_name')) {
            $userDetail->studio_name = $studio->name ?: ($link->studio_name ?: $userDetail->studio_name);
        }
        if (Schema::hasColumn($table, 'studio_relationship_type') && $link->relationship) {
            $userDetail->studio_relationship_type = $link->relationship;
        }
        if (Schema::hasColumn($table, 'studio_revenue_artist_percent') && $link->revenue_split !== null) {
            $userDetail->studio_revenue_artist_percent = (int) $link->revenue_split;
        }

        if ($userDetail->isDirty()) {
            $userDetail->save();
        }

        // Ensure the pending join row keeps the studio so signed invite pages can resolve it.
        if ((int) ($link->studio_id ?? 0) !== (int) $studio->id) {
            $link->studio_id = $studio->id;
            $link->save();
        }
    }
}
