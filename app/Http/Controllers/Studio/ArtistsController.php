<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Mail\ArtistStudioJoinRequestAnsweredMail;
use App\Models\GuestSpot;
use App\Models\Studio;
use App\Models\StudioInvite;
use App\Models\User;
use App\Models\UserStudio;
use App\Services\GuestSpotStudioInviteService;
use App\Services\RevenueSplitChangeService;
use App\Services\StudioArtistInviteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ArtistsController extends Controller
{
    public function __construct(
        private readonly StudioArtistInviteService $inviteService,
        private readonly GuestSpotStudioInviteService $guestSpotInvites,
        private readonly RevenueSplitChangeService $splitChanges,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);
        $ctx = $this->studioPageContext($user, $studio);
        $labels = $this->relationshipLabels();

        $artists = collect();
        if ($studio && UserStudio::tableReady()) {
            $links = UserStudio::query()
                ->with(['user.userDetail'])
                ->where('studio_id', $studio->id)
                ->where('status', UserStudio::STATUS_ACTIVE)
                ->orderBy('id')
                ->get();

            $artists = $links->map(function (UserStudio $link) use ($labels) {
                $base = $this->mapArtistBase($link, $labels);
                $isSplit = (bool) $link->payout && $link->revenue_split !== null;
                $artistPct = $isSplit ? max(0, min(100, (int) $link->revenue_split)) : 100;
                $studioPct = 100 - $artistPct;
                $styles = $this->formatTattooStyles($link->user?->userDetail?->tattoo_styles);
                $hasSplitRequest = $this->splitChanges->hasOpenArtistProposal($link);
                $proposedArtist = $hasSplitRequest
                    ? max(0, min(100, (int) $link->proposed_revenue_split))
                    : null;

                return array_merge($base, [
                    'is_split' => $isSplit,
                    'artist_percent' => $artistPct,
                    'studio_percent' => $studioPct,
                    'has_split_request' => $hasSplitRequest,
                    'proposed_artist_percent' => $proposedArtist,
                    'proposed_studio_percent' => $proposedArtist !== null ? 100 - $proposedArtist : null,
                    'pay_label' => $isSplit ? 'Revenue split' : 'Workstation rent',
                    'pay_detail' => $hasSplitRequest && $proposedArtist !== null
                        ? 'Asked: Artist '.$proposedArtist.'% · Studio '.(100 - $proposedArtist).'%'
                        : ($isSplit
                            ? 'Artist '.$artistPct.'% · Studio '.$studioPct.'%'
                            : 'Paid outside Bookpay'),
                    'status' => $hasSplitRequest ? 'Waiting for you' : 'Active',
                    'status_pill' => $hasSplitRequest ? 'a' : 'g',
                    'since' => optional($link->created_at)->format('d/m/Y') ?? '—',
                    'bookings_month' => 0,
                    'styles' => $styles,
                    'public_url' => $base['handle'] !== ''
                        ? route('public.artist', ['username' => $base['handle']])
                        : null,
                    'accept_split_url' => route('studio.artists.accept-split', $link->id),
                    'decline_split_url' => route('studio.artists.decline-split', $link->id),
                ]);
            })->values();
        }

        return view('studio.artists.index', array_merge($ctx, [
            'artists' => $artists,
            'artistCount' => $artists->count(),
            'artistsTab' => 'artists',
        ]));
    }

    public function joinRequests(Request $request): View
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);
        $ctx = $this->studioPageContext($user, $studio);
        $labels = $this->relationshipLabels();

        $requests = collect();
        if ($studio && UserStudio::tableReady()) {
            $links = UserStudio::query()
                ->with(['user.userDetail'])
                ->where('studio_id', $studio->id)
                ->where('status', UserStudio::STATUS_JOIN_PENDING)
                ->orderByDesc('id')
                ->get();

            $requests = $links->map(function (UserStudio $link) use ($labels) {
                $base = $this->mapArtistBase($link, $labels);
                $isSplit = (bool) $link->payout && $link->revenue_split !== null;
                $artistPct = $isSplit ? max(0, min(100, (int) $link->revenue_split)) : null;
                $studioPct = $isSplit ? 100 - (int) $artistPct : null;
                $askLabel = $isSplit
                    ? 'Revenue split · '.$base['name'].' '.$artistPct.'% · Studio '.$studioPct.'%'
                    : 'Workstation rent (no split)';
                $splitLabel = $isSplit
                    ? 'Artist '.$artistPct.'% · Studio '.$studioPct.'%'
                    : 'Workstation rent (no split)';

                return array_merge($base, [
                    'is_split' => $isSplit,
                    'artist_percent' => $artistPct,
                    'studio_percent' => $studioPct,
                    'ask_label' => $askLabel,
                    'split_label' => $splitLabel,
                    'sent' => optional($link->updated_at ?? $link->created_at)->format('d/m/Y') ?? '—',
                    'summary' => trim(
                        ($base['relationship'] !== '—' ? $base['relationship'] : 'Artist')
                        .' · '.$askLabel
                        .' · sent '.(optional($link->updated_at ?? $link->created_at)->format('d/m/Y') ?? '—')
                    ),
                ]);
            })->values();
        }

        return view('studio.artists.join-requests', array_merge($ctx, [
            'requests' => $requests,
            'artistsTab' => 'join-requests',
        ]));
    }

    public function guests(Request $request): View
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);
        $ctx = $this->studioPageContext($user, $studio);

        $studioCurrency = strtoupper(trim((string) ($studio?->currency ?? '')));
        if ($studioCurrency === '') {
            $studioCurrency = 'EUR';
        }

        $guests = collect();
        if ($studio && Schema::hasTable('guest_spots')) {
            $guests = $this->guestSpotInvites->forStudio($studio)->map(function (GuestSpot $spot) use ($studioCurrency) {
                $detail = $spot->user?->userDetail;
                $name = trim((string) ($detail?->display_name ?? ''));
                if ($name === '' || $name === 'Artist') {
                    $fallback = trim(($spot->user?->first_name ?? '').' '.($spot->user?->last_name ?? ''));
                    $name = $fallback !== '' ? $fallback : 'Artist';
                }
                $handle = trim((string) ($detail?->user_name ?? $spot->user?->user_name ?? ''));
                $city = trim((string) ($detail?->city ?? $spot->city ?? ''));
                $avatar = trim((string) ($detail?->avatar ?? ''));
                $initials = $detail
                    ? $detail->publicDisplayInitials()
                    : strtoupper(substr(preg_replace('/\s+/', '', $name) ?: 'AR', 0, 2));

                $artistPercent = $spot->artist_percent !== null
                    ? max(0, min(100, (int) $spot->artist_percent))
                    : (($spot->payout_mode ?? 'direct') === 'split' ? 60 : null);
                $studioPercent = $artistPercent !== null ? 100 - $artistPercent : null;

                $isSplit = ($spot->payout_mode ?: 'direct') === 'split';
                $payLabel = $isSplit && $artistPercent !== null
                    ? 'Artist '.$artistPercent.'% · Studio '.$studioPercent.'%'
                    : 'Direct payment';

                $dates = '—';
                if ($spot->from_date && $spot->to_date) {
                    $dates = $spot->from_date->format('d/m').' – '.$spot->to_date->format('d/m/Y');
                }

                $inviteStatus = $spot->guest_studio_invite_status;
                $toDate = $spot->to_date?->copy()?->endOfDay();
                $isPast = $toDate && $toDate->isPast();
                $isRejected = $inviteStatus === GuestSpot::INVITE_REJECTED;
                $isPending = $isSplit && $inviteStatus === GuestSpot::INVITE_PENDING && ! $isPast;
                $isConfirmed = $isSplit
                    && ($inviteStatus === GuestSpot::INVITE_ACCEPTED || (! $inviteStatus && ! $isPending))
                    && ! $isPast
                    && ! $isRejected;

                if ($isRejected) {
                    $statusKey = 'declined';
                    $statusLabel = 'Declined';
                    $statusPill = 'k';
                } elseif ($isPast) {
                    $statusKey = 'completed';
                    $statusLabel = 'Completed';
                    $statusPill = 'k';
                } elseif ($isPending) {
                    $statusKey = 'waiting';
                    $statusLabel = 'Waiting for you';
                    $statusPill = 'a';
                } elseif ($isConfirmed || (! $isSplit && ! $isPast)) {
                    $statusKey = 'confirmed';
                    $statusLabel = $isSplit ? 'Confirmed' : 'Live';
                    $statusPill = 'g';
                } else {
                    $statusKey = 'other';
                    $statusLabel = '—';
                    $statusPill = 'k';
                }

                $artistCurrency = strtoupper(trim((string) ($detail?->currency ?? '')));
                $currencyMismatch = $isPending
                    && $artistCurrency !== ''
                    && $studioCurrency !== ''
                    && $artistCurrency !== $studioCurrency;

                return [
                    'id' => $spot->id,
                    'name' => $name,
                    'handle' => $handle,
                    'city' => $city,
                    'subtitle' => trim(($handle !== '' ? '@'.$handle : '').($handle !== '' && $city !== '' ? ' · ' : '').$city),
                    'avatar' => $avatar !== '' ? asset(ltrim($avatar, '/')) : null,
                    'initials' => $initials !== '' ? $initials : 'AR',
                    'dates' => $dates,
                    'pay_label' => $payLabel,
                    'is_split' => $isSplit,
                    'artist_percent' => $artistPercent,
                    'studio_percent' => $studioPercent,
                    'status_key' => $statusKey,
                    'status_label' => $statusLabel,
                    'status_pill' => $statusPill,
                    'needs_action' => $isPending,
                    'currency_mismatch' => $currencyMismatch,
                    'artist_currency' => $artistCurrency !== '' ? $artistCurrency : null,
                    'studio_currency' => $studioCurrency,
                ];
            })->values();
        }

        return view('studio.artists.guests', array_merge($ctx, [
            'guests' => $guests,
            'studioCurrency' => $studioCurrency,
            'artistsTab' => 'guests',
            'guestCount' => $guests->count(),
        ]));
    }

    public function invites(Request $request): View
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);
        $ctx = $this->studioPageContext($user, $studio);
        $labels = $this->relationshipLabels();

        $invites = collect();
        if ($studio && StudioInvite::tableReady()) {
            $rows = StudioInvite::query()
                ->with(['user.userDetail'])
                ->where('studio_id', $studio->id)
                ->where('status', StudioInvite::STATUS_PENDING)
                ->orderByDesc('id')
                ->get();

            foreach ($rows as $row) {
                $row->markExpiredIfNeeded();
            }

            $invites = $rows->filter(fn (StudioInvite $row) => $row->status === StudioInvite::STATUS_PENDING)
                ->map(function (StudioInvite $invite) use ($labels) {
                    $artistUser = $invite->user;
                    $detail = $artistUser?->userDetail;
                    $name = $detail ? $detail->publicDisplayName() : '';
                    if ($name === '' || $name === 'Artist') {
                        $fallback = trim(($artistUser?->first_name ?? '').' '.($artistUser?->last_name ?? ''));
                        $name = $fallback !== '' ? $fallback : $invite->email;
                    }
                    $onBookpay = (bool) $invite->user_id;
                    $isSplit = (bool) $invite->payout && $invite->revenue_split !== null;
                    $artistPct = $isSplit ? (int) $invite->revenue_split : null;

                    return [
                        'id' => $invite->id,
                        'email' => $invite->email,
                        'name' => $name,
                        'on_bookpay' => $onBookpay,
                        'subtitle' => $onBookpay
                            ? 'On Bookpay · invite in their dashboard / email'
                            : 'Not on Bookpay yet · gets an email to join',
                        'relationship' => $labels[$invite->relationship ?? ''] ?? '—',
                        'offered' => $isSplit
                            ? 'Artist '.$artistPct.'% · Studio '.(100 - $artistPct).'%'
                            : 'Workstation rent (no split)',
                        'sent' => optional($invite->updated_at ?? $invite->created_at)->format('d/m/Y') ?? '—',
                        'status' => 'Waiting',
                    ];
                })
                ->values();
        }

        return view('studio.artists.invites', array_merge($ctx, [
            'invites' => $invites,
            'inviteCount' => $invites->count(),
            'artistsTab' => 'invites',
        ]));
    }

    public function sendInvite(Request $request): JsonResponse|RedirectResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio) {
            return $this->actionFailed($request, 'Studio not found.', 404);
        }

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'in:'.implode(',', UserStudio::RELATIONSHIP_TYPES)],
            'payout_mode' => ['nullable', 'string', 'in:split,direct'],
            'revenue_split' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $this->inviteService->send($studio, $request->user(), $validated['identifier'], [
                'relationship' => $validated['relationship'] ?? 'resident',
                'payout_mode' => $validated['payout_mode'] ?? 'split',
                'revenue_split' => $validated['revenue_split'] ?? 60,
            ]);
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first() ?: 'Could not send invite',
                ], 422);
            }

            throw $e;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Invitation sent',
                'reload' => true,
            ]);
        }

        return redirect()
            ->route('studio.artists.invites')
            ->with('success', 'Invitation sent');
    }

    public function resendInvite(Request $request, int $invite): JsonResponse|RedirectResponse
    {
        $studio = $this->resolveStudio($request->user());
        $row = $this->findStudioInvite($studio, $invite);
        if (! $row) {
            return $this->actionFailed($request, 'Invitation not found.', 404);
        }

        try {
            $this->inviteService->resend($row);
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first() ?: 'Could not resend invite',
                ], 422);
            }

            throw $e;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Invitation sent again',
            ]);
        }

        return redirect()
            ->route('studio.artists.invites')
            ->with('success', 'Invitation sent again');
    }

    public function cancelInvite(Request $request, int $invite): JsonResponse|RedirectResponse
    {
        $studio = $this->resolveStudio($request->user());
        $row = $this->findStudioInvite($studio, $invite);
        if (! $row) {
            return $this->actionFailed($request, 'Invitation not found.', 404);
        }

        try {
            $this->inviteService->cancel($row);
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first() ?: 'Could not cancel invite',
                ], 422);
            }

            throw $e;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Invitation cancelled',
                'reload' => true,
            ]);
        }

        return redirect()
            ->route('studio.artists.invites')
            ->with('success', 'Invitation cancelled');
    }

    public function accept(Request $request, int $userStudio): JsonResponse|RedirectResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio || ! UserStudio::tableReady()) {
            return $this->actionFailed($request, 'Studio not found.', 404);
        }

        $validated = $request->validate([
            'relationship' => ['nullable', 'string', 'in:'.implode(',', UserStudio::RELATIONSHIP_TYPES)],
        ]);

        $link = UserStudio::query()
            ->where('id', $userStudio)
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_JOIN_PENDING)
            ->first();

        if (! $link) {
            return $this->actionFailed($request, 'Join request not found.', 404);
        }

        if (! empty($validated['relationship'])) {
            $link->relationship = $validated['relationship'];
        }
        $link->status = UserStudio::STATUS_ACTIVE;
        if (Schema::hasColumn('user_studios', 'joined_via')) {
            $link->joined_via = UserStudio::JOINED_VIA_JOIN_REQUEST;
        }
        $link->save();

        $link->loadMissing(['user.userDetail', 'studio']);
        $this->sendJoinRequestAnsweredMail($link, true);

        $name = $this->mapArtistBase($link, $this->relationshipLabels())['name'];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $name.' is now one of your artists.',
                'user_studio_id' => $link->id,
                'reload' => true,
            ]);
        }

        return redirect()
            ->route('studio.artists.join-requests')
            ->with('success', $name.' is now one of your artists.');
    }

    public function decline(Request $request, int $userStudio): JsonResponse|RedirectResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio || ! UserStudio::tableReady()) {
            return $this->actionFailed($request, 'Studio not found.', 404);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $link = UserStudio::query()
            ->where('id', $userStudio)
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_JOIN_PENDING)
            ->first();

        if (! $link) {
            return $this->actionFailed($request, 'Join request not found.', 404);
        }

        $currentStudioName = $this->artistCurrentStudioName($link);

        $link->status = UserStudio::STATUS_INACTIVE;
        $link->payout = false;
        $link->save();

        $link->loadMissing(['user.userDetail', 'studio']);
        $this->sendJoinRequestAnsweredMail(
            $link,
            false,
            isset($validated['message']) ? trim((string) $validated['message']) : null,
            $currentStudioName,
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Request declined',
                'user_studio_id' => $link->id,
                'reload' => true,
            ]);
        }

        return redirect()
            ->route('studio.artists.join-requests')
            ->with('success', 'Request declined');
    }

    public function remove(Request $request, int $userStudio): JsonResponse|RedirectResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio || ! UserStudio::tableReady()) {
            return $this->actionFailed($request, 'Studio not found.', 404);
        }

        $link = UserStudio::query()
            ->where('id', $userStudio)
            ->where('studio_id', $studio->id)
            ->whereIn('status', [
                UserStudio::STATUS_ACTIVE,
                UserStudio::STATUS_SPLIT_PROPOSED,
            ])
            ->first();

        if (! $link) {
            return $this->actionFailed($request, 'Artist link not found.', 404);
        }

        $link->status = UserStudio::STATUS_INACTIVE;
        $link->payout = false;
        $link->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Artist removed',
                'user_studio_id' => $link->id,
                'reload' => true,
            ]);
        }

        return redirect()
            ->route('studio.artists')
            ->with('success', 'Artist removed');
    }

    public function acceptSplit(Request $request, int $userStudio): JsonResponse|RedirectResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio || ! UserStudio::tableReady()) {
            return $this->actionFailed($request, 'Studio not found.', 404);
        }

        $link = UserStudio::query()
            ->with(['user.userDetail'])
            ->where('id', $userStudio)
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->first();

        if (! $link) {
            return $this->actionFailed($request, 'Artist link not found.', 404);
        }

        try {
            $this->splitChanges->acceptByStudio($studio, $link);
        } catch (ValidationException $e) {
            return $this->actionFailed(
                $request,
                collect($e->errors())->flatten()->first() ?: 'Could not accept split request.',
                422
            );
        }

        $name = $this->mapArtistBase($link, $this->relationshipLabels())['name'];
        $message = 'New split with '.$name.' accepted. It applies to new bookings.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'user_studio_id' => $link->id,
                'reload' => true,
            ]);
        }

        return redirect()->route('studio.artists')->with('success', $message);
    }

    public function declineSplit(Request $request, int $userStudio): JsonResponse|RedirectResponse
    {
        $studio = $this->resolveStudio($request->user());
        if (! $studio || ! UserStudio::tableReady()) {
            return $this->actionFailed($request, 'Studio not found.', 404);
        }

        $link = UserStudio::query()
            ->with(['user.userDetail'])
            ->where('id', $userStudio)
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->first();

        if (! $link) {
            return $this->actionFailed($request, 'Artist link not found.', 404);
        }

        try {
            $this->splitChanges->declineByStudio($studio, $link);
        } catch (ValidationException $e) {
            return $this->actionFailed(
                $request,
                collect($e->errors())->flatten()->first() ?: 'Could not decline split request.',
                422
            );
        }

        $message = 'Split request declined. The current split stays.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'user_studio_id' => $link->id,
                'reload' => true,
            ]);
        }

        return redirect()->route('studio.artists')->with('success', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function studioPageContext(User $user, ?Studio $studio): array
    {
        $studioName = trim((string) ($studio?->name ?? $user->userDetail?->resolvedStudioName() ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        $artistCount = 0;
        $joinRequestCount = 0;
        $inviteCount = 0;
        $guestCount = 0;

        if ($studio && UserStudio::tableReady()) {
            $artistCount = UserStudio::query()
                ->where('studio_id', $studio->id)
                ->where('status', UserStudio::STATUS_ACTIVE)
                ->count();
            $joinRequestCount = UserStudio::query()
                ->where('studio_id', $studio->id)
                ->where('status', UserStudio::STATUS_JOIN_PENDING)
                ->count();
        }

        if ($studio && StudioInvite::tableReady()) {
            $inviteCount = StudioInvite::query()
                ->where('studio_id', $studio->id)
                ->where('status', StudioInvite::STATUS_PENDING)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->count();
        }

        if ($studio && Schema::hasTable('guest_spots')) {
            $guestCount = $this->guestSpotInvites->countForStudio($studio);
        }

        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        return [
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio?->email ?? $user->email,
            'studioInitials' => $initials,
            'ownerAvatarUrl' => ($imagePath = trim((string) ($studio?->image_url ?? ''))) !== ''
                ? asset(ltrim($imagePath, '/'))
                : null,
            'artistCount' => $artistCount,
            'joinRequestCount' => $joinRequestCount,
            'inviteCount' => $inviteCount,
            'guestCount' => $guestCount,
            'profileComplete' => (string) ($user->on_boarding ?? '') === 'yes',
            'activeNav' => 'artists',
            'relationshipOptions' => $this->relationshipLabels(),
        ];
    }

    private function findStudioInvite(?Studio $studio, int $inviteId): ?StudioInvite
    {
        if (! $studio || ! StudioInvite::tableReady()) {
            return null;
        }

        return StudioInvite::query()
            ->where('id', $inviteId)
            ->where('studio_id', $studio->id)
            ->first();
    }

    /**
     * @return array<string, string>
     */
    private function relationshipLabels(): array
    {
        return [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective member',
            'apprentice' => 'Apprentice',
            'other' => 'Other',
        ];
    }

    /**
     * @param  array<string, string>  $labels
     * @return array<string, mixed>
     */
    private function mapArtistBase(UserStudio $link, array $labels): array
    {
        $artistUser = $link->user;
        $detail = $artistUser?->userDetail;
        $name = $detail ? $detail->publicDisplayName() : '';
        if ($name === '' || $name === 'Artist') {
            $fallback = trim(($artistUser?->first_name ?? '').' '.($artistUser?->last_name ?? ''));
            $name = $fallback !== '' ? $fallback : ($detail?->user_name ?? $artistUser?->user_name ?? 'Artist');
        }
        $handle = trim((string) ($detail?->user_name ?? $artistUser?->user_name ?? ''));
        $avatar = trim((string) ($detail?->avatar ?? ''));
        $initials = $detail
            ? $detail->publicDisplayInitials()
            : strtoupper(substr(preg_replace('/\s+/', '', $name) ?: 'AR', 0, 2));

        return [
            'id' => $link->id,
            'name' => $name,
            'handle' => $handle,
            'avatar' => $avatar !== '' ? asset(ltrim($avatar, '/')) : null,
            'initials' => $initials !== '' ? $initials : 'AR',
            'relationship' => $labels[$link->relationship ?? ''] ?? '—',
            'relationship_key' => $link->relationship ?: 'resident',
        ];
    }

    private function formatTattooStyles(mixed $payload): string
    {
        if (! is_array($payload)) {
            return '';
        }

        $labels = [];

        if (array_key_exists('primary_style', $payload) || array_key_exists('other_styles', $payload)) {
            $primary = trim((string) ($payload['primary_style'] ?? ''));
            if ($primary !== '') {
                $labels[] = $primary;
            }
            foreach ((array) ($payload['other_styles'] ?? []) as $style) {
                if (! is_scalar($style)) {
                    continue;
                }
                $style = trim((string) $style);
                if ($style !== '') {
                    $labels[] = $style;
                }
            }
        } else {
            foreach ($payload as $style) {
                if (! is_scalar($style)) {
                    continue;
                }
                $style = trim((string) $style);
                if ($style !== '') {
                    $labels[] = $style;
                }
            }
        }

        return implode(', ', array_slice(array_values(array_unique($labels)), 0, 3));
    }

    private function resolveStudio(?User $user): ?Studio
    {
        if (! $user) {
            return null;
        }

        $studio = $user->id
            ? Studio::query()->where('user_id', $user->id)->first()
            : null;

        if (! $studio) {
            $email = strtolower(trim((string) ($user->email ?? '')));
            if ($email !== '') {
                $studio = Studio::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            }
        }

        return $studio;
    }

    private function actionFailed(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return redirect()
            ->route('studio.artists')
            ->with('error', $message);
    }

    private function sendJoinRequestAnsweredMail(
        UserStudio $link,
        bool $accepted,
        ?string $declineMessage = null,
        ?string $currentStudioName = null,
    ): void {
        $email = trim((string) ($link->user?->email ?? ''));
        if ($email === '') {
            return;
        }

        try {
            Mail::to($email)->send(new ArtistStudioJoinRequestAnsweredMail(
                userStudio: $link,
                accepted: $accepted,
                declineMessage: $declineMessage,
                currentStudioName: $currentStudioName,
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send join-request answered email to artist', [
                'user_studio_id' => $link->id,
                'accepted' => $accepted,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function artistCurrentStudioName(UserStudio $pendingLink): ?string
    {
        $userId = (int) ($pendingLink->user_id ?? 0);
        if ($userId < 1 || ! UserStudio::tableReady()) {
            return null;
        }

        $active = UserStudio::query()
            ->with('studio')
            ->where('user_id', $userId)
            ->where('status', UserStudio::STATUS_ACTIVE)
            ->where('id', '!=', $pendingLink->id)
            ->orderByDesc('id')
            ->first();

        if (! $active) {
            return null;
        }

        $name = trim((string) ($active->studio?->name ?? $active->studio_name ?? ''));

        return $name !== '' ? $name : null;
    }
}
