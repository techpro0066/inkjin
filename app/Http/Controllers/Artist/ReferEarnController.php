<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\ArtistReferral;
use App\Services\ArtistReferralRewardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferEarnController extends Controller
{
    public function index(Request $request): View
    {
        $referrals = ArtistReferral::query()
            ->where('referrer_user_id', $request->user()->id)
            ->with(['referred.userDetail'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ArtistReferral $referral) {
                $artist = $referral->referred;
                $name = trim(($artist?->first_name ?? '').' '.($artist?->last_name ?? ''));
                $username = trim((string) ($artist?->userDetail?->user_name ?? ''));

                if ($name === '') {
                    $name = $username !== '' ? $username : ($artist?->email ?? 'Artist');
                }

                return [
                    'id' => $referral->id,
                    'artist_name' => $name,
                    'artist_email' => (string) ($artist?->email ?? ''),
                    'artist_username' => $username,
                    'date' => optional($referral->created_at)->timezone(config('app.timezone'))->format('M j, Y'),
                    'date_full' => optional($referral->created_at)->timezone(config('app.timezone'))->format('M j, Y g:i A'),
                    'status' => ArtistReferralRewardService::artistDashboardStatusKey($referral->status),
                    'status_label' => ArtistReferralRewardService::artistDashboardStatusLabel($referral->status),
                    'amount' => (float) $referral->reward_amount,
                    'fee_waived' => (bool) $referral->fee_waived,
                    'reward_paid_at' => optional($referral->reward_paid_at)->timezone(config('app.timezone'))?->format('M j, Y g:i A'),
                    'rejection_reason' => $referral->isRejected() ? (string) $referral->rejection_reason : null,
                    'rejected_at' => optional($referral->rejected_at)->timezone(config('app.timezone'))?->format('M j, Y g:i A'),
                ];
            })
            ->values();

        return view('artist.refer-earn.index', [
            'referrals' => $referrals,
        ]);
    }
}
