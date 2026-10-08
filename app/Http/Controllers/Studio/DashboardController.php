<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Models\UserStudio;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
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

        $stripeConnected = (bool) ($studio?->hasStripeConnect());
        $profileComplete = (string) ($user->on_boarding ?? '') === 'yes';
        $stripeConnectUrl = OnboardingController::nextIncompleteStepRoute($user, $studio);

        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        return view('studio.dashboard', [
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio?->email ?? $user->email,
            'studioInitials' => $initials,
            'ownerAvatarUrl' => ($imagePath = trim((string) ($studio?->image_url ?? ''))) !== ''
                ? asset(ltrim($imagePath, '/'))
                : null,
            'artists' => $artists,
            'primaryArtistName' => $primaryArtistName,
            'stripeConnected' => $stripeConnected,
            'stripeConnectUrl' => $stripeConnectUrl,
            'profileComplete' => $profileComplete,
            'activeNav' => 'home',
        ]);
    }
}
