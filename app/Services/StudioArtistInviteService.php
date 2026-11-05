<?php

namespace App\Services;

use App\Mail\StudioArtistInviteMail;
use App\Models\Studio;
use App\Models\StudioInvite;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserStudio;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class StudioArtistInviteService
{
    /**
     * @return array{0: ?User, 1: string} [matchedUser, normalizedEmail]
     */
    public function resolveRecipient(string $identifier): array
    {
        $raw = trim($identifier);
        if ($raw === '') {
            throw ValidationException::withMessages([
                'identifier' => 'Enter an email or Bookpay username.',
            ]);
        }

        if (str_starts_with($raw, '@')) {
            $raw = substr($raw, 1);
        }

        if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($raw);
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            return [$user, $email];
        }

        $username = strtolower($raw);
        $detail = UserDetail::query()
            ->whereRaw('LOWER(user_name) = ?', [$username])
            ->first();
        $user = $detail?->user;

        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => 'No Bookpay artist found with that username. Use their email instead.',
            ]);
        }

        $email = strtolower(trim((string) $user->email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'identifier' => 'That artist has no valid email on file.',
            ]);
        }

        return [$user, $email];
    }

    /**
     * @param  array{relationship?: string|null, payout_mode?: string, revenue_split?: int|null}  $terms
     */
    public function send(Studio $studio, User $invitedBy, string $identifier, array $terms = [], bool $isResend = false): StudioInvite
    {
        if (! StudioInvite::tableReady()) {
            throw ValidationException::withMessages([
                'identifier' => 'Invites are not available yet.',
            ]);
        }

        [$user, $email] = $this->resolveRecipient($identifier);

        if (UserStudio::tableReady()) {
            $alreadyActive = UserStudio::query()
                ->where('studio_id', $studio->id)
                ->where('status', UserStudio::STATUS_ACTIVE)
                ->where(function ($q) use ($user, $email) {
                    if ($user) {
                        $q->where('user_id', $user->id);
                    } else {
                        $q->whereRaw('0 = 1');
                    }
                })
                ->exists();

            if ($alreadyActive) {
                throw ValidationException::withMessages([
                    'identifier' => 'That artist is already linked to your studio.',
                ]);
            }

            $joinPending = $user
                ? UserStudio::query()
                    ->where('studio_id', $studio->id)
                    ->where('user_id', $user->id)
                    ->where('status', UserStudio::STATUS_JOIN_PENDING)
                    ->exists()
                : false;

            if ($joinPending) {
                throw ValidationException::withMessages([
                    'identifier' => 'That artist already has a join request waiting for you.',
                ]);
            }
        }

        $payoutMode = ($terms['payout_mode'] ?? 'split') === 'direct' ? 'direct' : 'split';
        $relationship = in_array($terms['relationship'] ?? null, UserStudio::RELATIONSHIP_TYPES, true)
            ? $terms['relationship']
            : 'resident';
        $split = null;
        if ($payoutMode === 'split') {
            $split = max(0, min(100, (int) ($terms['revenue_split'] ?? 60)));
        }

        $invite = StudioInvite::query()
            ->where('studio_id', $studio->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', StudioInvite::STATUS_PENDING)
            ->orderByDesc('id')
            ->first();

        if (! $invite) {
            $invite = new StudioInvite([
                'studio_id' => $studio->id,
                'invited_by_user_id' => $invitedBy->id,
                'email' => $email,
            ]);
        }

        $invite->fill([
            'invited_by_user_id' => $invitedBy->id,
            'user_id' => $user?->id,
            'token' => StudioInvite::newToken(),
            'relationship' => $relationship,
            'payout' => $payoutMode === 'split',
            'revenue_split' => $split,
            'status' => StudioInvite::STATUS_PENDING,
            'expires_at' => now()->addDays(30),
            'accepted_at' => null,
            'declined_at' => null,
            'cancelled_at' => null,
        ]);
        $invite->save();

        $this->sendEmail($invite, $isResend);

        return $invite->fresh(['studio', 'user']);
    }

    public function resend(StudioInvite $invite): StudioInvite
    {
        $invite->markExpiredIfNeeded();
        if ($invite->status !== StudioInvite::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'invite' => 'This invitation is no longer pending.',
            ]);
        }

        $invite->token = StudioInvite::newToken();
        $invite->expires_at = now()->addDays(30);
        $invite->save();

        $this->sendEmail($invite->fresh(['studio', 'user']), true);

        return $invite;
    }

    public function cancel(StudioInvite $invite): StudioInvite
    {
        if ($invite->status !== StudioInvite::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'invite' => 'This invitation is no longer pending.',
            ]);
        }

        $invite->status = StudioInvite::STATUS_CANCELLED;
        $invite->cancelled_at = now();
        $invite->save();

        return $invite;
    }

    public function accept(StudioInvite $invite, User $artist): UserStudio
    {
        $invite->markExpiredIfNeeded();
        if (! $invite->isPending()) {
            throw ValidationException::withMessages([
                'invite' => 'This invitation is no longer available.',
            ]);
        }

        $email = strtolower(trim((string) $artist->email));
        $inviteEmail = strtolower(trim((string) $invite->email));
        $matchesUser = $invite->user_id && (int) $invite->user_id === (int) $artist->id;
        $matchesEmail = $email !== '' && $email === $inviteEmail;

        if (! $matchesUser && ! $matchesEmail) {
            throw ValidationException::withMessages([
                'invite' => 'Sign in with the invited account ('.$inviteEmail.') to accept.',
            ]);
        }

        if (! UserStudio::tableReady()) {
            throw ValidationException::withMessages([
                'invite' => 'Studio links are not available yet.',
            ]);
        }

        $alreadyActive = UserStudio::query()
            ->where('user_id', $artist->id)
            ->where('studio_id', $invite->studio_id)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->first();
        if ($alreadyActive) {
            $invite->status = StudioInvite::STATUS_ACCEPTED;
            $invite->accepted_at = now();
            $invite->user_id = $artist->id;
            $invite->save();
            $this->mirrorStudioOntoUserDetail($artist, $alreadyActive, $invite->studio);
            $this->forgetToken();
            $this->clearSkippedInOnboarding();

            return $alreadyActive;
        }

        $studio = $invite->studio;
        $link = UserStudio::findReusableForUser((int) $artist->id, null, (int) $invite->studio_id)
            ?? new UserStudio(['user_id' => $artist->id]);

        $link->fill([
            'studio_id' => $invite->studio_id,
            'status' => UserStudio::STATUS_ACTIVE,
            'connection_type' => UserStudio::CONNECTION_ON_BOOKPAY,
            'relationship' => $invite->relationship,
            'workspace_type' => $this->mapStudioWorkspaceType($studio?->studio_type),
            'studio_name' => $studio?->name,
            'studio_address' => $studio?->address,
            'street_name' => $studio?->street_name,
            'street_number' => $studio?->street_number,
            'city' => $studio?->city,
            'state' => $studio?->state,
            'postal_code' => $studio?->postal_code,
            'country' => $studio?->country,
            'google_maps_link' => $studio?->google_maps_link,
            'payout' => (bool) $invite->payout,
            'revenue_split' => $invite->payout ? $invite->revenue_split : null,
            'proposed_revenue_split' => null,
        ]);
        if (Schema::hasColumn('user_studios', 'joined_via')) {
            $link->joined_via = UserStudio::JOINED_VIA_INVITE;
        }
        $link->save();

        $invite->status = StudioInvite::STATUS_ACCEPTED;
        $invite->accepted_at = now();
        $invite->user_id = $artist->id;
        $invite->save();

        $this->mirrorStudioOntoUserDetail($artist, $link, $studio);
        $this->forgetToken();
        $this->clearSkippedInOnboarding();

        return $link;
    }

    public function rememberToken(?string $token): void
    {
        $token = trim((string) $token);
        if ($token === '') {
            return;
        }
        session([StudioInvite::SESSION_TOKEN_KEY => $token]);
    }

    public function forgetToken(): void
    {
        session()->forget(StudioInvite::SESSION_TOKEN_KEY);
    }

    public function markSkippedInOnboarding(): void
    {
        session([StudioInvite::SESSION_SKIPPED_KEY => true]);
        $this->forgetToken();
    }

    public function clearSkippedInOnboarding(): void
    {
        session()->forget(StudioInvite::SESSION_SKIPPED_KEY);
    }

    public function wasSkippedInOnboarding(): bool
    {
        return (bool) session(StudioInvite::SESSION_SKIPPED_KEY);
    }

    public function pendingInviteForUser(?User $user, ?string $token = null): ?StudioInvite
    {
        if (! StudioInvite::tableReady()) {
            return null;
        }

        $token = trim((string) ($token ?: session(StudioInvite::SESSION_TOKEN_KEY)));
        if ($token !== '') {
            $byToken = StudioInvite::query()
                ->with('studio')
                ->where('token', $token)
                ->where('status', StudioInvite::STATUS_PENDING)
                ->first();
            if ($byToken) {
                $byToken->markExpiredIfNeeded();
                if ($byToken->isPending()) {
                    if (! $user) {
                        return $byToken;
                    }
                    $email = strtolower(trim((string) $user->email));
                    if ($email === '' || $email === strtolower(trim((string) $byToken->email))) {
                        return $byToken;
                    }
                }
            }
        }

        if (! $user) {
            return null;
        }

        return $this->pendingInvitesForUser($user)->first();
    }

    /**
     * All pending studio invites for a signed-in artist (by user id or email).
     *
     * @return \Illuminate\Support\Collection<int, StudioInvite>
     */
    public function pendingInvitesForUser(User $user): \Illuminate\Support\Collection
    {
        if (! StudioInvite::tableReady()) {
            return collect();
        }

        $email = strtolower(trim((string) $user->email));
        if ($email === '' && ! $user->id) {
            return collect();
        }

        $invites = StudioInvite::query()
            ->with('studio')
            ->where('status', StudioInvite::STATUS_PENDING)
            ->where(function ($q) use ($user, $email) {
                $q->where('user_id', $user->id);
                if ($email !== '') {
                    $q->orWhereRaw('LOWER(email) = ?', [$email]);
                }
            })
            ->orderByDesc('id')
            ->get();

        return $invites
            ->each(fn (StudioInvite $invite) => $invite->markExpiredIfNeeded())
            ->filter(fn (StudioInvite $invite) => $invite->isPending())
            ->values();
    }

    public function activeInviteLinkedStudio(User $user): ?UserStudio
    {
        if (! UserStudio::tableReady()) {
            return null;
        }

        return UserStudio::query()
            ->with('studio')
            ->where('user_id', $user->id)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->where('connection_type', UserStudio::CONNECTION_ON_BOOKPAY)
            ->when(Schema::hasColumn('user_studios', 'joined_via'), function ($q) {
                $q->where('joined_via', UserStudio::JOINED_VIA_INVITE);
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function presentInviteForOnboarding(StudioInvite $invite): array
    {
        $studio = $invite->studio;
        $name = trim((string) ($studio?->name ?? 'Studio'));
        $address = $this->formatStudioAddress($studio);
        $relationshipLabels = [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
        ];
        $isSplit = (bool) $invite->payout && $invite->revenue_split !== null;
        $artistPct = $isSplit ? (int) $invite->revenue_split : null;

        return [
            'token' => $invite->token,
            'studio_name' => $name,
            'studio_initials' => strtoupper(substr(preg_replace('/\s+/', '', $name) ?: 'ST', 0, 2)),
            'address' => $address,
            'maps_url' => trim((string) ($studio?->google_maps_link ?? '')) ?: (
                $address !== '' ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address) : null
            ),
            'relationship' => $invite->relationship,
            'relationship_label' => $relationshipLabels[$invite->relationship ?? ''] ?? '—',
            'pay_label' => $isSplit
                ? 'Artist '.$artistPct.'% · Studio '.(100 - $artistPct).'%'
                : 'Workstation rent (no split)',
            'managed_by' => $name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentLockedStudio(UserStudio $link): array
    {
        $studio = $link->studio;
        $name = trim((string) ($studio?->name ?: $link->studio_name ?: 'Studio'));
        $address = $this->formatStudioAddress($studio);
        if ($address === '') {
            $address = trim((string) ($link->studio_address ?? ''));
        }
        $relationshipLabels = [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
        ];

        return [
            'studio_name' => $name,
            'studio_initials' => strtoupper(substr(preg_replace('/\s+/', '', $name) ?: 'ST', 0, 2)),
            'address' => $address,
            'maps_url' => trim((string) ($link->google_maps_link ?: $studio?->google_maps_link ?: '')) ?: null,
            'relationship' => $link->relationship,
            'relationship_label' => $relationshipLabels[$link->relationship ?? ''] ?? '—',
            'managed_by' => $name,
            'locked' => true,
        ];
    }

    private function mirrorStudioOntoUserDetail(User $artist, UserStudio $link, ?Studio $studio): void
    {
        $detail = $artist->userDetail ?? UserDetail::create(['user_id' => $artist->id]);
        $table = $detail->getTable();
        $candidates = [
            'studio_name' => $studio?->name ?: $link->studio_name,
            'studio_address' => $studio?->address ?: $link->studio_address,
            'street_name' => $studio?->street_name ?: $link->street_name,
            'street_number' => $studio?->street_number ?: $link->street_number,
            'city' => $studio?->city ?: $link->city,
            'state' => $studio?->state ?: $link->state,
            'postal_code' => $studio?->postal_code ?: $link->postal_code,
            'country' => $studio?->country ?: $link->country,
            'google_maps_link' => $studio?->google_maps_link ?: $link->google_maps_link,
            'workspace_type' => $link->workspace_type ?: $this->mapStudioWorkspaceType($studio?->studio_type),
            'studio_relationship_type' => $link->relationship,
            'studio_id' => $link->studio_id,
        ];

        $payload = [];
        foreach ($candidates as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (Schema::hasColumn($table, $column)) {
                $payload[$column] = $value;
            }
        }

        if ($payload === []) {
            return;
        }

        $detail->fill($payload);
        $detail->save();
    }

    private function mapStudioWorkspaceType(?string $studioType): string
    {
        $type = strtolower(trim((string) $studioType));

        return match (true) {
            in_array($type, ['private', 'private studio'], true) => 'private',
            in_array($type, ['home', 'home studio'], true) => 'home',
            in_array($type, ['collective'], true) => 'collective',
            default => 'shop',
        };
    }

    private function formatStudioAddress(?Studio $studio): string
    {
        if (! $studio) {
            return '';
        }

        if (trim((string) ($studio->address ?? '')) !== '') {
            return trim((string) $studio->address);
        }

        $street = trim(trim((string) ($studio->street_number ?? '')).' '.trim((string) ($studio->street_name ?? '')));

        return implode(', ', array_values(array_filter([
            $street !== '' ? $street : null,
            trim((string) ($studio->city ?? '')),
            trim((string) ($studio->state ?? '')),
            trim((string) ($studio->postal_code ?? '')),
            trim((string) ($studio->country ?? '')),
        ], fn ($p) => $p !== null && $p !== '')));
    }

    public function decline(StudioInvite $invite, ?User $artist = null): StudioInvite
    {
        $invite->markExpiredIfNeeded();
        if ($invite->status !== StudioInvite::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'invite' => 'This invitation is no longer pending.',
            ]);
        }

        if ($artist) {
            $email = strtolower(trim((string) $artist->email));
            $inviteEmail = strtolower(trim((string) $invite->email));
            $matchesUser = $invite->user_id && (int) $invite->user_id === (int) $artist->id;
            $matchesEmail = $email !== '' && $email === $inviteEmail;
            if (! $matchesUser && ! $matchesEmail) {
                throw ValidationException::withMessages([
                    'invite' => 'Sign in with the invited account to decline.',
                ]);
            }
            $invite->user_id = $artist->id;
        }

        $invite->status = StudioInvite::STATUS_DECLINED;
        $invite->declined_at = now();
        $invite->save();

        return $invite;
    }

    private function sendEmail(StudioInvite $invite, bool $isResend = false): void
    {
        // E3: email is for artists not yet on Bookpay. Existing artists see the invite in-app.
        if ($invite->user_id) {
            return;
        }

        try {
            $invite->loadMissing(['studio', 'user']);
            Mail::to($invite->email)->send(new StudioArtistInviteMail(
                invite: $invite,
                isResend: $isResend,
            ));
        } catch (Throwable $e) {
            Log::error('Failed to send studio artist invite email', [
                'invite_id' => $invite->id,
                'email' => $invite->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
