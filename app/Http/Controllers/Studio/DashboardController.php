<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $email = strtolower(trim((string) ($user->email ?? '')));

        $studio = $email !== ''
            ? Studio::query()->whereRaw('LOWER(email) = ?', [$email])->first()
            : null;

        $studioName = trim((string) ($studio?->name ?? $user->userDetail?->studio_name ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        $artists = collect();
        if ($studio) {
            $artists = UserDetail::query()
                ->with('user')
                ->where('studio_id', $studio->id)
                ->where('payment_type', 'studio_account')
                ->whereIn('payment_status', ['pending', 'approved'])
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();
        }

        $primaryArtist = $artists->first();
        $primaryArtistName = $primaryArtist
            ? $primaryArtist->publicDisplayName()
            : null;
        if ($primaryArtistName === '' || $primaryArtistName === 'Artist') {
            $primaryArtistName = null;
        }

        $stripeConnected = (bool) ($studio?->hasStripeConnect());
        $profileComplete = (string) ($user->on_boarding ?? '') === 'yes';

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
            'profileComplete' => $profileComplete,
            'activeNav' => 'home',
        ]);
    }
}
