<?php

namespace App\Http\Controllers;

use App\Models\GuestSpot;
use App\Models\Studio;
use App\Services\GuestSpotStudioInviteService;
use App\Services\StripeConnectService;
use App\Support\StripeConnectCountries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class GuestSpotStudioInviteAcceptController extends Controller
{
    public function __construct(
        private readonly GuestSpotStudioInviteService $invites,
    ) {
    }

    public function show(string $token, StripeConnectService $stripeConnect): View
    {
        $guestSpot = $this->invites->findByToken($token);
        if (! $guestSpot) {
            return view('guest-spot.studio-invite-result', [
                'status' => 'missing',
                'artistName' => 'Artist',
            ]);
        }

        $guestSpot->loadMissing(['user.userDetail']);

        if (($guestSpot->payout_mode ?: 'direct') !== 'split'
            || ($guestSpot->guest_studio_invite_status ?? null) !== GuestSpot::INVITE_PENDING
        ) {
            $status = ($guestSpot->guest_studio_invite_status ?? null) === GuestSpot::INVITE_ACCEPTED
                ? 'accepted'
                : 'unavailable';

            return view('guest-spot.studio-invite-result', [
                'status' => $status,
                'artistName' => $this->artistName($guestSpot),
            ]);
        }

        $email = strtolower(trim((string) ($guestSpot->guest_studio_invite_email ?? '')));
        $onBookpay = $this->invites->isStudioOnBookpay($email);
        $user = auth()->user();
        $needsAuth = $onBookpay && (! $user || ! $this->invites->matchesInviteEmail($user, $email));
        $wrongAccount = (bool) ($user
            && ($user->role ?? '') === 'studio'
            && ! $this->invites->matchesInviteEmail($user, $email));

        if ($needsAuth && ! $wrongAccount) {
            session(['url.intended' => route('guest-spot.studio-invite.show', ['token' => $token])]);
        }

        $matchedStudio = $this->invites->findStudioByInviteEmail($email);
        $studioAlreadyConnected = (bool) ($matchedStudio?->hasStripeConnect());

        return view('guest-spot.studio-invite-accept', $this->viewData($guestSpot, [
            'needsAuth' => $needsAuth,
            'wrongAccount' => $wrongAccount,
            'onBookpay' => $onBookpay,
            'showSignup' => ! $onBookpay,
            'studioAlreadyConnected' => $studioAlreadyConnected,
            'stripeConnectConfigured' => $stripeConnect->isConfigured(),
            'stripePublishableKey' => config('services.stripe.key'),
            'stripeConnectLocale' => $stripeConnect->connectLocale(),
            'stripeSupportedCountries' => StripeConnectCountries::supportedForSelect(),
            'acceptUrl' => route('guest-spot.studio-invite.accept', ['token' => $token]),
            'declineUrl' => route('guest-spot.studio-invite.decline', ['token' => $token]),
            'stripeSessionUrl' => route('guest-spot.studio-invite.stripe.session', ['token' => $token]),
            'stripeCompleteUrl' => route('guest-spot.studio-invite.stripe.complete', ['token' => $token]),
        ]));
    }

    public function accept(Request $request, string $token, StripeConnectService $stripeConnect): RedirectResponse|View|JsonResponse
    {
        $guestSpot = $this->invites->findByToken($token);
        if (! $guestSpot) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Invite not found.'], 404);
            }

            return view('guest-spot.studio-invite-result', [
                'status' => 'missing',
                'artistName' => 'Artist',
            ]);
        }

        $email = strtolower(trim((string) ($guestSpot->guest_studio_invite_email ?? '')));
        $onBookpay = $this->invites->isStudioOnBookpay($email);

        $password = null;
        if (! $onBookpay) {
            try {
                $validated = $request->validate([
                    'password' => ['required', 'string', 'min:8', 'max:255'],
                    'terms' => ['accepted'],
                ], [
                    'password.min' => 'Use at least 8 characters',
                    'terms.accepted' => 'Tick the box to continue',
                ]);
            } catch (ValidationException $e) {
                if ($request->expectsJson()) {
                    throw $e;
                }

                return redirect()
                    ->route('guest-spot.studio-invite.show', ['token' => $token])
                    ->withErrors($e->errors())
                    ->withInput();
            }
            $password = (string) $validated['password'];
        } elseif (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sign in to accept this invite.',
                    'redirect' => route('login', [
                        'redirect' => route('guest-spot.studio-invite.show', ['token' => $token]),
                    ]),
                ], 401);
            }

            return redirect()->guest(route('login', [
                'redirect' => route('guest-spot.studio-invite.show', ['token' => $token]),
            ]));
        }

        try {
            $guestSpot = $this->invites->accept($guestSpot, $request->user(), $password);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                throw $e;
            }

            return redirect()
                ->route('guest-spot.studio-invite.show', ['token' => $token])
                ->withErrors($e->errors())
                ->withInput();
        }

        $guestSpot->loadMissing('studio');
        $studio = $guestSpot->studio;
        $alreadyConnected = (bool) ($studio?->hasStripeConnect());

        if ($request->expectsJson()) {
            if ($alreadyConnected || ! $stripeConnect->isConfigured()) {
                return response()->json([
                    'success' => true,
                    'csrf_token' => csrf_token(),
                    'message' => 'Guest spot invitation accepted.',
                    'redirect' => route('studio.dashboard'),
                ]);
            }

            return response()->json([
                'success' => true,
                'csrf_token' => csrf_token(),
                'next' => 'stripe',
                'message' => 'Account created. Split confirmed',
            ]);
        }

        $user = auth()->user();
        if ($user && ($user->role ?? '') === 'studio') {
            if (! $alreadyConnected && $stripeConnect->isConfigured()
                && (string) ($user->on_boarding ?? '') !== 'yes'
            ) {
                return redirect()
                    ->route('studio.onboarding.payouts')
                    ->with('success', 'Guest spot invitation accepted. Connect Stripe to get your share.');
            }

            return redirect()
                ->route('studio.dashboard')
                ->with('success', 'Guest spot invitation accepted.');
        }

        return view('guest-spot.studio-invite-result', [
            'status' => 'accepted',
            'artistName' => $this->artistName($guestSpot->fresh(['user.userDetail'])),
        ]);
    }

    public function decline(Request $request, string $token): View|RedirectResponse|JsonResponse
    {
        $guestSpot = $this->invites->findByToken($token);
        if (! $guestSpot) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Invite not found.'], 404);
            }

            return view('guest-spot.studio-invite-result', [
                'status' => 'missing',
                'artistName' => 'Artist',
            ]);
        }

        try {
            $this->invites->decline($guestSpot, $request->user());
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                throw $e;
            }

            return redirect()
                ->route('guest-spot.studio-invite.show', ['token' => $token])
                ->withErrors($e->errors());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'title' => 'Invitation declined',
                'message' => 'We let '.$this->artistName($guestSpot).' know. They keep being paid directly.',
            ]);
        }

        return view('guest-spot.studio-invite-result', [
            'status' => 'declined',
            'artistName' => $this->artistName($guestSpot),
        ]);
    }

    public function createStripeSession(
        Request $request,
        string $token,
        StripeConnectService $stripeConnect,
    ): JsonResponse {
        $guestSpot = $this->invites->findByToken($token);
        if (! $guestSpot || ($guestSpot->guest_studio_invite_status ?? null) !== GuestSpot::INVITE_ACCEPTED) {
            return response()->json([
                'success' => false,
                'message' => 'Accept the invitation before connecting Stripe.',
            ], 422);
        }

        $studio = $this->resolveAcceptedStudio($request, $guestSpot);
        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio record was not found.'], 404);
        }

        if (! $stripeConnect->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Stripe is not configured.'], 500);
        }

        $supportedCodes = array_map(
            fn (array $country) => $country['code'],
            StripeConnectCountries::supportedForSelect()
        );

        $validated = $request->validate([
            'business_type' => ['required', 'string', Rule::in(['individual', 'company'])],
            'country' => ['required', 'string', 'size:2', Rule::in($supportedCodes)],
            'industry' => ['required', 'string', Rule::in(['tattoo_studio', 'tattoo_beauty', 'other'])],
        ], [
            'business_type.required' => 'Please select whether you are an individual or a business.',
            'country.required' => 'Please select your country.',
            'industry.required' => 'Please select what best describes you.',
        ]);

        $setup = [
            'business_type' => $validated['business_type'],
            'country' => strtoupper($validated['country']),
            'industry' => $validated['industry'],
        ];

        $studio->fill([
            'business_type' => $setup['business_type'],
            'country' => $setup['country'],
        ]);
        if (empty($studio->user_id) && $request->user()?->id) {
            $studio->user_id = $request->user()->id;
        }
        $studio->save();

        try {
            $session = $stripeConnect->createStudioOwnerOnboardingSession($studio, $setup);

            return response()->json([
                'success' => true,
                'client_secret' => $session['client_secret'],
                'account_id' => $session['account_id'],
                'collection_options' => $session['collection_options'],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Guest spot invite Stripe session failed', [
                'guest_spot_id' => $guestSpot->id,
                'studio_id' => $studio->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not start Stripe onboarding: '.$e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Guest spot invite Stripe session failed', [
                'guest_spot_id' => $guestSpot->id,
                'studio_id' => $studio->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Could not start Stripe onboarding.'], 500);
        }
    }

    public function completeStripe(
        Request $request,
        string $token,
        StripeConnectService $stripeConnect,
    ): JsonResponse {
        $guestSpot = $this->invites->findByToken($token);
        if (! $guestSpot || ($guestSpot->guest_studio_invite_status ?? null) !== GuestSpot::INVITE_ACCEPTED) {
            return response()->json([
                'success' => false,
                'message' => 'Accept the invitation before connecting Stripe.',
            ], 422);
        }

        $studio = $this->resolveAcceptedStudio($request, $guestSpot);
        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio record was not found.'], 404);
        }

        $validated = $request->validate([
            'account_id' => ['required', 'string', 'regex:/^acct_[a-zA-Z0-9]+$/'],
        ]);

        try {
            $stripeConnect->finalizeStudioOwnerOnboarding($studio, $validated['account_id']);
            $studio->refresh();

            $user = $request->user();
            if ($user && (string) ($user->on_boarding ?? '') !== 'yes') {
                $user->on_boarding = 'yes';
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Stripe payout setup completed successfully.',
                'redirect' => route('studio.dashboard'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Could not complete Stripe setup.',
            ], 422);
        }
    }

    /**
     * Dashboard: accept a pending guest-spot split for the logged-in studio.
     */
    public function acceptFromDashboard(Request $request, GuestSpot $guestSpot): RedirectResponse
    {
        $this->assertStudioOwnsPending($request, $guestSpot);
        $back = $this->studioGuestBackRoute($request);

        try {
            $this->invites->accept($guestSpot, $request->user(), null);
        } catch (ValidationException $e) {
            return redirect()
                ->route($back)
                ->withErrors($e->errors());
        }

        $guestSpot->refresh();
        $studio = $guestSpot->studio;
        if ($studio && ! $studio->hasStripeConnect()) {
            return redirect()
                ->route('studio.onboarding.payouts')
                ->with('success', 'Guest spot split confirmed. Connect Stripe to get your share.');
        }

        return redirect()
            ->route($back)
            ->with('success', 'Guest spot split confirmed.');
    }

    public function declineFromDashboard(Request $request, GuestSpot $guestSpot): RedirectResponse
    {
        $this->assertStudioOwnsPending($request, $guestSpot);
        $back = $this->studioGuestBackRoute($request);

        try {
            $this->invites->decline($guestSpot, $request->user());
        } catch (ValidationException $e) {
            return redirect()
                ->route($back)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route($back)
            ->with('success', 'Guest spot invitation declined.');
    }

    private function studioGuestBackRoute(Request $request): string
    {
        return 'studio.artists.guests';
    }

    private function resolveAcceptedStudio(Request $request, GuestSpot $guestSpot): ?Studio
    {
        $guestSpot->loadMissing('studio');
        $studio = $guestSpot->studio;
        $user = $request->user();

        if (! $user || ($user->role ?? '') !== 'studio') {
            return null;
        }

        if ($studio && (int) ($studio->user_id ?? 0) === (int) $user->id) {
            return $studio;
        }

        $email = strtolower(trim((string) ($guestSpot->guest_studio_invite_email ?? '')));
        if ($this->invites->matchesInviteEmail($user, $email)) {
            return $studio ?: $this->invites->findStudioByInviteEmail($email);
        }

        return null;
    }

    private function assertStudioOwnsPending(Request $request, GuestSpot $guestSpot): void
    {
        abort_unless($request->user() && ($request->user()->role ?? '') === 'studio', 403);
        abort_unless(
            ($guestSpot->guest_studio_invite_status ?? null) === GuestSpot::INVITE_PENDING
            && ($guestSpot->payout_mode ?: 'direct') === 'split',
            404
        );

        $email = strtolower(trim((string) ($request->user()->email ?? '')));
        $inviteEmail = strtolower(trim((string) ($guestSpot->guest_studio_invite_email ?? '')));
        $ok = $email !== '' && $email === $inviteEmail;

        if (! $ok && $guestSpot->studio_id) {
            $ok = Studio::query()
                ->where('id', $guestSpot->studio_id)
                ->where('user_id', $request->user()->id)
                ->exists();
        }

        abort_unless($ok, 403);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function viewData(GuestSpot $guestSpot, array $extra = []): array
    {
        $detail = $guestSpot->user?->userDetail;
        $artistName = $this->artistName($guestSpot);
        $artistFirst = explode(' ', trim($artistName))[0] ?: $artistName;

        $username = trim((string) ($detail?->user_name ?? ''));
        $city = trim((string) ($detail?->city ?? $guestSpot->city ?? ''));
        $country = trim((string) ($detail?->country ?? ''));
        $location = implode(', ', array_values(array_filter([$city, $country])));

        $styles = is_array($detail?->tattoo_styles) ? $detail->tattoo_styles : [];
        $tattooingSince = $styles['tattooing_since'] ?? null;
        $primaryStyle = $styles['primary_style'] ?? null;
        $otherStyles = isset($styles['other_styles']) && is_array($styles['other_styles'])
            ? array_values(array_filter($styles['other_styles']))
            : [];
        $styleLabels = array_values(array_filter(array_merge(
            $primaryStyle ? [(string) $primaryStyle] : [],
            array_map(fn ($s) => (string) $s, $otherStyles)
        )));

        $metaBits = array_values(array_filter([
            $username !== '' ? '@'.$username : null,
            $location !== '' ? $location : null,
            $tattooingSince ? 'Tattooing since '.$tattooingSince : null,
        ]));

        $avatarPath = trim((string) ($detail?->avatar ?? ''));
        $artistPercent = $guestSpot->artist_percent !== null
            ? max(0, min(100, (int) $guestSpot->artist_percent))
            : 60;

        $dateRangeLabel = '';
        if ($guestSpot->from_date && $guestSpot->to_date) {
            $fromLabel = $guestSpot->from_date->format('M j');
            $toLabel = $guestSpot->to_date->format('M j, Y');
            if ($guestSpot->from_date->format('Y') !== $guestSpot->to_date->format('Y')) {
                $fromLabel = $guestSpot->from_date->format('M j, Y');
            }
            $dateRangeLabel = $fromLabel.' to '.$toLabel;
        }

        $where = trim((string) ($guestSpot->studio_name ?? ''));
        $whereCity = trim((string) ($guestSpot->studio_city ?? $guestSpot->city ?? ''));
        if ($whereCity !== '') {
            $where = $where !== '' ? $where.', '.$whereCity : $whereCity;
        }

        return array_merge([
            'guestSpot' => $guestSpot,
            'token' => $guestSpot->guest_studio_invite_token,
            'artistName' => $artistName,
            'artistFirst' => $artistFirst,
            'artistInitials' => $detail
                ? $detail->publicDisplayInitials()
                : strtoupper(substr(preg_replace('/\s+/', '', $artistName) ?: 'AR', 0, 2)),
            'artistAvatarUrl' => $avatarPath !== '' ? asset(ltrim($avatarPath, '/')) : null,
            'artistMeta' => implode(' · ', $metaBits),
            'artistStyleLine' => $styleLabels !== []
                ? implode(', ', array_map(fn ($s) => ucwords(str_replace('-', ' ', $s)), $styleLabels))
                : null,
            'studioNameValue' => trim((string) ($guestSpot->studio_name ?? '')) ?: 'Studio',
            'studioEmail' => $guestSpot->guest_studio_invite_email,
            'artistPercent' => $artistPercent,
            'studioPercent' => 100 - $artistPercent,
            'dateRangeLabel' => $dateRangeLabel,
            'whereLabel' => $where,
        ], $extra);
    }

    private function artistName(GuestSpot $guestSpot): string
    {
        $detail = $guestSpot->user?->userDetail;
        $name = trim((string) ($detail?->display_name ?? ''));
        if ($name === '' || $name === 'Artist') {
            $user = $guestSpot->user;
            $fallback = trim(($user?->first_name ?? '').' '.($user?->last_name ?? ''));
            $name = $fallback !== '' ? $fallback : trim((string) ($user?->name ?? 'Artist'));
        }

        return $name !== '' ? $name : 'Artist';
    }
}
