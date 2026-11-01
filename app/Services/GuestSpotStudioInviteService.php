<?php

namespace App\Services;

use App\Models\GuestSpot;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestSpotStudioInviteService
{
    /**
     * Ensure invite token + linked studio_id for a pending split invite.
     */
    public function prepareInvite(GuestSpot $guestSpot): GuestSpot
    {
        if (($guestSpot->payout_mode ?: 'direct') !== 'split') {
            return $guestSpot;
        }

        $email = strtolower(trim((string) ($guestSpot->guest_studio_invite_email ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $guestSpot;
        }

        $dirty = false;

        if (Schema::hasColumn('guest_spots', 'guest_studio_invite_token')
            && trim((string) ($guestSpot->guest_studio_invite_token ?? '')) === ''
        ) {
            $guestSpot->guest_studio_invite_token = $this->newToken();
            $dirty = true;
        }

        $studio = $this->findStudioByInviteEmail($email);
        if ($studio && (int) ($guestSpot->studio_id ?? 0) !== (int) $studio->id) {
            $guestSpot->studio_id = $studio->id;
            $dirty = true;
        }

        if ($dirty) {
            $guestSpot->save();
        }

        return $guestSpot->fresh() ?? $guestSpot;
    }

    public function inviteUrl(GuestSpot $guestSpot): string
    {
        $token = trim((string) ($guestSpot->guest_studio_invite_token ?? ''));
        if ($token === '') {
            $guestSpot = $this->prepareInvite($guestSpot);
            $token = trim((string) ($guestSpot->guest_studio_invite_token ?? ''));
        }

        if ($token === '') {
            return url('/login');
        }

        return route('guest-spot.studio-invite.show', ['token' => $token]);
    }

    /**
     * Studio already has a Bookpay studio login (or owned studio row).
     */
    public function isStudioOnBookpay(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user && ($user->role ?? '') === 'studio') {
            return true;
        }

        $studio = $this->findStudioByInviteEmail($email);

        return $studio !== null && (int) ($studio->user_id ?? 0) > 0;
    }

    public function findByToken(string $token): ?GuestSpot
    {
        $token = trim($token);
        if ($token === '' || ! Schema::hasColumn('guest_spots', 'guest_studio_invite_token')) {
            return null;
        }

        return GuestSpot::query()
            ->where('guest_studio_invite_token', $token)
            ->first();
    }

    /**
     * Scope guest spots that belong to this studio (by id or invite email).
     */
    public function applyStudioScope($query, Studio $studio)
    {
        $email = strtolower(trim((string) ($studio->email ?? '')));

        return $query->where(function ($q) use ($studio, $email) {
            $q->where('studio_id', $studio->id);
            if ($email !== '') {
                $q->orWhereRaw('LOWER(guest_studio_invite_email) = ?', [$email]);
            }
        });
    }

    /**
     * All guest spots linked to a studio (S02d Guest artists).
     *
     * @return \Illuminate\Support\Collection<int, GuestSpot>
     */
    public function forStudio(Studio $studio)
    {
        $query = GuestSpot::query()->with(['user.userDetail']);
        $this->applyStudioScope($query, $studio);

        return $query
            ->orderByRaw("CASE WHEN guest_studio_invite_status = ? THEN 0 ELSE 1 END", [GuestSpot::INVITE_PENDING])
            ->orderByDesc('from_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    /**
     * Pending guest-spot split invites for a studio dashboard.
     *
     * @return \Illuminate\Support\Collection<int, GuestSpot>
     */
    public function pendingForStudio(Studio $studio)
    {
        $query = GuestSpot::query()
            ->with(['user.userDetail'])
            ->where('payout_mode', 'split')
            ->where('guest_studio_invite_status', GuestSpot::INVITE_PENDING);

        $this->applyStudioScope($query, $studio);

        return $query->orderByDesc('updated_at')->limit(20)->get();
    }

    public function countForStudio(Studio $studio): int
    {
        $query = GuestSpot::query();
        $this->applyStudioScope($query, $studio);

        return (int) $query->count();
    }

    public function accept(GuestSpot $guestSpot, ?User $actor = null, ?string $password = null): GuestSpot
    {
        if (($guestSpot->payout_mode ?: 'direct') !== 'split') {
            throw ValidationException::withMessages([
                'invite' => 'This invitation is not a revenue-split guest spot.',
            ]);
        }

        if (($guestSpot->guest_studio_invite_status ?? null) !== GuestSpot::INVITE_PENDING) {
            throw ValidationException::withMessages([
                'invite' => 'This invitation is no longer pending.',
            ]);
        }

        $email = strtolower(trim((string) ($guestSpot->guest_studio_invite_email ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'This invite does not have a valid studio email.',
            ]);
        }

        if (User::emailBlockedForStudioInvite($email)) {
            throw ValidationException::withMessages([
                'email' => 'This email is already registered with a different Bookpay account.',
            ]);
        }

        $studio = $this->findStudioByInviteEmail($email);
        if (! $studio) {
            $studio = Studio::query()->create([
                'email' => $email,
                'name' => trim((string) ($guestSpot->studio_name ?? '')) ?: 'Studio',
                'address' => $guestSpot->studio_address,
                'street_name' => $guestSpot->street_name,
                'street_number' => $guestSpot->street_number,
                'city' => $guestSpot->studio_city,
                'state' => $guestSpot->studio_state,
                'postal_code' => $guestSpot->postal_code,
                'country' => $guestSpot->studio_country,
                'google_maps_link' => $guestSpot->google_maps_link,
                'offers_revenue_split' => true,
            ]);
        } else {
            $name = trim((string) ($guestSpot->studio_name ?? ''));
            if ($name !== '' && trim((string) ($studio->name ?? '')) === '') {
                $studio->name = $name;
                $studio->save();
            }
        }

        $studioUser = null;
        if ($actor && ($actor->role ?? '') === 'studio' && $this->matchesInviteEmail($actor, $email)) {
            $studioUser = $actor;
        } elseif ($this->isStudioOnBookpay($email)) {
            if (! $actor || ! $this->matchesInviteEmail($actor, $email)) {
                throw ValidationException::withMessages([
                    'invite' => 'Sign in with '.$email.' to accept this invite.',
                ]);
            }
            $studioUser = $actor;
        } else {
            if ($password === null || strlen($password) < 8) {
                throw ValidationException::withMessages([
                    'password' => 'Use at least 8 characters',
                ]);
            }
            $studioUser = $this->registerOrUpdateStudioUser($studio, $email, $password);
            Auth::login($studioUser);
        }

        if ((int) ($studio->user_id ?? 0) !== (int) $studioUser->id) {
            $studio->user_id = $studioUser->id;
            $studio->save();
        }

        $guestSpot->studio_id = $studio->id;
        $guestSpot->guest_studio_invite_status = GuestSpot::INVITE_ACCEPTED;
        $guestSpot->save();

        return $guestSpot->fresh(['user.userDetail', 'studio']) ?? $guestSpot;
    }

    public function decline(GuestSpot $guestSpot, ?User $actor = null): GuestSpot
    {
        if (($guestSpot->guest_studio_invite_status ?? null) !== GuestSpot::INVITE_PENDING) {
            throw ValidationException::withMessages([
                'invite' => 'This invitation is no longer pending.',
            ]);
        }

        $email = strtolower(trim((string) ($guestSpot->guest_studio_invite_email ?? '')));
        if ($actor && $email !== '' && ! $this->matchesInviteEmail($actor, $email)
            && ! ($actor->role === 'studio' && (int) ($guestSpot->studio_id ?? 0) > 0
                && Studio::query()->where('id', $guestSpot->studio_id)->where('user_id', $actor->id)->exists())
        ) {
            // Allow decline via signed public link without auth; only block wrong logged-in studio.
            if ($actor->role === 'studio') {
                throw ValidationException::withMessages([
                    'invite' => 'Sign in with '.$email.' to decline this invite.',
                ]);
            }
        }

        $guestSpot->guest_studio_invite_status = GuestSpot::INVITE_REJECTED;
        $guestSpot->save();

        return $guestSpot->fresh() ?? $guestSpot;
    }

    public function matchesInviteEmail(?User $user, string $email): bool
    {
        if (! $user) {
            return false;
        }

        $userEmail = strtolower(trim((string) ($user->email ?? '')));
        $email = strtolower(trim($email));

        return $userEmail !== '' && $userEmail === $email;
    }

    public function findStudioByInviteEmail(string $email): ?Studio
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return Studio::query()->whereRaw('LOWER(email) = ?', [$email])->first();
    }

    public function newToken(): string
    {
        return Str::random(48);
    }

    private function registerOrUpdateStudioUser(Studio $studio, string $email, string $password): User
    {
        $existing = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing) {
            if (($existing->role ?? '') !== 'studio') {
                throw ValidationException::withMessages([
                    'email' => 'This email is already registered with a different Bookpay account.',
                ]);
            }

            $existing->password = $password;
            $existing->must_set_password = false;
            if (empty($existing->email_verified_at)) {
                $existing->email_verified_at = now();
            }
            $existing->on_app = 1;
            if (empty($existing->studio_registration_source)) {
                $existing->studio_registration_source = 'guest_spot_invite';
            }
            $existing->save();

            app(MailcoachSubscriberService::class)
                ->queueSubscribeUser($existing, MailcoachSubscriberService::TAG_STUDIO);
            app(MailcoachSubscriberService::class)->queueSubscribeStudio($studio);

            return $existing->fresh();
        }

        $nameParts = preg_split('/\s+/', trim((string) ($studio->name ?? '')), 2) ?: [];
        $firstName = trim((string) ($nameParts[0] ?? ''));
        $lastName = trim((string) ($nameParts[1] ?? ''));

        $user = User::create([
            'first_name' => $firstName !== '' ? $firstName : 'Studio',
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
            'must_set_password' => false,
            'role' => 'studio',
            'studio_registration_source' => 'guest_spot_invite',
            'on_boarding' => 'no',
            'on_app' => 1,
            'app_id' => null,
            'email_verified_at' => now(),
        ]);

        app(MailcoachSubscriberService::class)
            ->queueSubscribeUser($user, MailcoachSubscriberService::TAG_STUDIO);
        app(MailcoachSubscriberService::class)->queueSubscribeStudio($studio);

        return $user;
    }
}
