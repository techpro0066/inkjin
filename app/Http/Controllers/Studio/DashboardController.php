<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Models\UserStudio;
use App\Services\GuestSpotStudioInviteService;
use App\Support\StudioPaymentLinkModalData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GuestSpotStudioInviteService $guestSpotInvites): View
    {
        $user = $request->user();
        $email = strtolower(trim((string) ($user->email ?? '')));

        $studio = $user?->id
            ? Studio::query()->where('user_id', $user->id)->first()
            : null;
        if (! $studio && $email !== '') {
            $studio = Studio::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($studio && empty($studio->user_id) && $user?->id) {
                $studio->user_id = $user->id;
                $studio->save();
            }
        }

        $studioName = trim((string) ($studio?->name ?? $user->userDetail?->resolvedStudioName() ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        $artists = collect();
        if ($studio && UserStudio::tableReady()) {
            $artists = UserStudio::query()
                ->with(['user.userDetail'])
                ->where('studio_id', $studio->id)
                ->where('payout', true)
                ->whereIn('status', [
                    UserStudio::STATUS_ACTIVE,
                    UserStudio::STATUS_JOIN_PENDING,
                ])
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();
        }

        $primaryLink = $artists->first();
        $primaryDetail = $primaryLink?->user?->userDetail;
        $primaryArtistName = $primaryDetail
            ? $primaryDetail->publicDisplayName()
            : null;
        if ($primaryArtistName === '' || $primaryArtistName === 'Artist') {
            $fallback = trim(($primaryLink?->user?->first_name ?? '').' '.($primaryLink?->user?->last_name ?? ''));
            $primaryArtistName = $fallback !== '' ? $fallback : null;
        }

        $pendingGuestSpotInvites = collect();
        if ($studio) {
            $pendingGuestSpotInvites = $guestSpotInvites->pendingForStudio($studio)->map(function ($spot) use ($guestSpotInvites) {
                $spot = $guestSpotInvites->prepareInvite($spot);
                $detail = $spot->user?->userDetail;
                $name = trim((string) ($detail?->display_name ?? ''));
                if ($name === '' || $name === 'Artist') {
                    $fallback = trim(($spot->user?->first_name ?? '').' '.($spot->user?->last_name ?? ''));
                    $name = $fallback !== '' ? $fallback : 'Artist';
                }
                $artistPercent = $spot->artist_percent !== null
                    ? max(0, min(100, (int) $spot->artist_percent))
                    : 60;
                $dates = '';
                if ($spot->from_date && $spot->to_date) {
                    $dates = $spot->from_date->format('M j').' – '.$spot->to_date->format('M j, Y');
                }
                $avatar = trim((string) ($detail?->avatar ?? ''));

                return [
                    'id' => $spot->id,
                    'artist_name' => $name,
                    'artist_initials' => $detail
                        ? $detail->publicDisplayInitials()
                        : strtoupper(substr(preg_replace('/\s+/', '', $name) ?: 'AR', 0, 2)),
                    'artist_avatar' => $avatar !== '' ? asset(ltrim($avatar, '/')) : null,
                    'dates' => $dates,
                    'artist_percent' => $artistPercent,
                    'studio_percent' => 100 - $artistPercent,
                    'invite_url' => $guestSpotInvites->inviteUrl($spot),
                ];
            });
        }

        $stripeConnected = (bool) ($studio?->hasStripeConnect());
        $profileComplete = (string) ($user->on_boarding ?? '') === 'yes';
        $stripeConnectUrl = OnboardingController::nextIncompleteStepRoute($user, $studio);

        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        $studioUsername = trim((string) ($studio?->username ?? ''));
        $publicPageHost = $studioUsername !== '' ? 'inkjin.com/@'.$studioUsername : '';
        $publicPageUrl = $studioUsername !== ''
            ? route('public.artist', ['username' => $studioUsername])
            : '';
        $quickLinkRows = $studioUsername !== '' ? [
            ['suffix' => '', 'hash' => ''],
            ['suffix' => '/artists', 'hash' => '#artists'],
            ['suffix' => '/portfolio', 'hash' => '#work'],
            ['suffix' => '/guest-artists', 'hash' => '#guest-artists'],
            ['suffix' => '/find-us', 'hash' => '#findus'],
        ] : [];

        $studioArtistsForModal = $studio
            ? StudioPaymentLinkModalData::artists($studio)
            : collect();
        $paymentLinkModal = StudioPaymentLinkModalData::createGate(
            $profileComplete,
            $studio,
            $studioArtistsForModal
        );

        return view('studio.dashboard', array_merge($paymentLinkModal, [
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio?->email ?? $user->email,
            'studioInitials' => $initials,
            'ownerAvatarUrl' => ($imagePath = trim((string) ($studio?->image_url ?? ''))) !== ''
                ? asset(ltrim($imagePath, '/'))
                : null,
            'artists' => $artists,
            'primaryArtistName' => $primaryArtistName,
            'pendingGuestSpotInvites' => $pendingGuestSpotInvites,
            'stripeConnected' => $stripeConnected,
            'stripeConnectUrl' => $stripeConnectUrl,
            'profileComplete' => $profileComplete,
            'activeNav' => 'home',
            'studioArtistsForModal' => $studioArtistsForModal,
            'currencySymbol' => '€',
            'paymentLinkModalReloadOnDone' => false,
            'publicPageUrl' => $publicPageUrl,
            'publicPageHost' => $publicPageHost,
            'quickLinkRows' => $quickLinkRows,
        ]));
    }
}
