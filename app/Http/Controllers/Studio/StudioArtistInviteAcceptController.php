<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\StudioInvite;
use App\Services\StudioArtistInviteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudioArtistInviteAcceptController extends Controller
{
    public function __construct(
        private readonly StudioArtistInviteService $invites,
    ) {
    }

    public function show(string $token): View
    {
        $invite = $this->findInvite($token);
        if (! $invite) {
            return view('studio.artist-invite-result', [
                'status' => 'missing',
                'studioName' => 'Studio',
            ]);
        }

        $invite->markExpiredIfNeeded();
        $invite->loadMissing('studio');

        if ($invite->status !== StudioInvite::STATUS_PENDING || ! $invite->isPending()) {
            return view('studio.artist-invite-result', [
                'status' => $invite->status === StudioInvite::STATUS_ACCEPTED ? 'accepted' : 'unavailable',
                'studioName' => $this->studioName($invite),
            ]);
        }

        $this->invites->rememberToken($token);

        if (! auth()->check()) {
            session(['url.intended' => route('studio.artist-invite.show', ['token' => $token])]);
        }

        return view('studio.artist-invite-accept', [
            'invite' => $invite,
            'studioName' => $this->studioName($invite),
            'relationshipLabel' => $this->relationshipLabel($invite),
            'artistPercent' => $invite->payout && $invite->revenue_split !== null
                ? (int) $invite->revenue_split
                : null,
            'needsAuth' => ! auth()->check(),
            'wrongAccount' => auth()->check() && ! $this->matchesInvite(auth()->user(), $invite),
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse|View
    {
        $invite = $this->findInvite($token);
        if (! $invite) {
            return view('studio.artist-invite-result', [
                'status' => 'missing',
                'studioName' => 'Studio',
            ]);
        }

        if (! $request->user()) {
            return redirect()->guest(route('login', [
                'redirect' => route('studio.artist-invite.show', ['token' => $token]),
            ]));
        }

        try {
            $this->invites->accept($invite, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route('studio.artist-invite.show', ['token' => $token])
                ->withErrors($e->errors());
        }

        $user = $request->user();
        if ($user && (string) ($user->on_boarding ?? '') !== 'yes' && ($user->role ?? '') === 'artist') {
            // Stay on their current onboarding step (don't jump to Studio).
            return redirect()
                ->route('onboarding.index')
                ->with('success', 'Invite accepted. Your studio details are locked from '.$this->studioName($invite).'.');
        }

        return view('studio.artist-invite-result', [
            'status' => 'accepted',
            'studioName' => $this->studioName($invite->fresh('studio')),
        ]);
    }

    public function decline(Request $request, string $token): View|RedirectResponse
    {
        $invite = $this->findInvite($token);
        if (! $invite) {
            return view('studio.artist-invite-result', [
                'status' => 'missing',
                'studioName' => 'Studio',
            ]);
        }

        try {
            $this->invites->decline($invite, $request->user());
            $this->invites->forgetToken();
        } catch (ValidationException $e) {
            return redirect()
                ->route('studio.artist-invite.show', ['token' => $token])
                ->withErrors($e->errors());
        }

        return view('studio.artist-invite-result', [
            'status' => 'declined',
            'studioName' => $this->studioName($invite->fresh('studio')),
        ]);
    }

    private function findInvite(string $token): ?StudioInvite
    {
        if (! StudioInvite::tableReady() || $token === '') {
            return null;
        }

        return StudioInvite::query()->where('token', $token)->first();
    }

    private function studioName(?StudioInvite $invite): string
    {
        if (! $invite) {
            return 'Studio';
        }

        $invite->loadMissing('studio');

        return trim((string) ($invite->studio?->name ?: 'Studio'));
    }

    private function relationshipLabel(StudioInvite $invite): ?string
    {
        $labels = [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
        ];

        return $labels[$invite->relationship ?? ''] ?? null;
    }

    private function matchesInvite($user, StudioInvite $invite): bool
    {
        if (! $user) {
            return false;
        }

        if ($invite->user_id && (int) $invite->user_id === (int) $user->id) {
            return true;
        }

        $email = strtolower(trim((string) ($user->email ?? '')));
        $inviteEmail = strtolower(trim((string) $invite->email));

        return $email !== '' && $email === $inviteEmail;
    }
}
