<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\ArtistReferral;
use App\Services\ArtistReferralRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
                    'qualified_at' => optional($referral->qualified_at)->timezone(config('app.timezone'))?->format('M j, Y g:i A'),
                    'reward_paid_at' => optional($referral->reward_paid_at)->timezone(config('app.timezone'))?->format('M j, Y g:i A'),
                    'rejection_reason' => $referral->isRejected() ? (string) $referral->rejection_reason : null,
                    'rejected_at' => optional($referral->rejected_at)->timezone(config('app.timezone'))?->format('M j, Y g:i A'),
                ];
            })
            ->values();

        $earnings = $this->buildEarningsSummary($referrals);

        return view('artist.refer-earn.index', [
            'referrals' => $referrals,
            'earnings' => $earnings,
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $referrals
     * @return array{
     *     pending_total: float,
     *     paid_total: float,
     *     items: Collection<int, array{
     *         artist_label: string,
     *         subtitle: string,
     *         amount: float,
     *         state: string,
     *         sort_at: string
     *     }>
     * }
     */
    private function buildEarningsSummary(Collection $referrals): array
    {
        $pendingTotal = 0.0;
        $paidTotal = 0.0;
        $items = collect();

        foreach ($referrals as $referral) {
            $status = (string) ($referral['status'] ?? '');
            $amount = (float) ($referral['amount'] ?? 0);
            $feeWaived = (bool) ($referral['fee_waived'] ?? false);

            if ($status === ArtistReferral::STATUS_REJECTED) {
                continue;
            }

            if ($status === ArtistReferral::STATUS_REWARDED) {
                $paidTotal += $amount;
                $paidAt = (string) ($referral['reward_paid_at'] ?? '');
                $paidDate = $paidAt !== '' ? \Illuminate\Support\Carbon::parse($paidAt)->format('M j') : '—';

                $items->push([
                    'artist_label' => $this->earningsArtistLabel($referral),
                    'subtitle' => 'Paid '.$paidDate,
                    'amount' => $amount,
                    'state' => 'paid',
                    'sort_at' => $paidAt !== '' ? $paidAt : (string) ($referral['date_full'] ?? ''),
                ]);

                continue;
            }

            if (! $feeWaived) {
                continue;
            }

            $pendingTotal += $amount;
            $qualifiedAt = (string) ($referral['qualified_at'] ?? '');
            $items->push([
                'artist_label' => $this->earningsArtistLabel($referral),
                'subtitle' => 'Awaiting settlement',
                'amount' => $amount,
                'state' => 'pending',
                'sort_at' => $qualifiedAt !== '' ? $qualifiedAt : (string) ($referral['date_full'] ?? ''),
            ]);
        }

        return [
            'pending_total' => $pendingTotal,
            'paid_total' => $paidTotal,
            'items' => $items
                ->sortByDesc(fn (array $item) => $item['sort_at'])
                ->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $referral
     */
    private function earningsArtistLabel(array $referral): string
    {
        $fullName = trim((string) ($referral['artist_name'] ?? ''));
        $parts = preg_split('/\s+/', $fullName) ?: [];

        if (count($parts) >= 2) {
            $first = $parts[0];
            $lastInitial = strtoupper(substr($parts[count($parts) - 1], 0, 1));

            return $first.' '.$lastInitial.'.';
        }

        if ($fullName !== '') {
            return $fullName;
        }

        $username = trim((string) ($referral['artist_username'] ?? ''));

        return $username !== '' ? '@'.$username : 'Artist';
    }
}
