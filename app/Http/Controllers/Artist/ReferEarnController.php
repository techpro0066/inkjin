<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\ArtistReferral;
use App\Services\ArtistReferralRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReferEarnController extends Controller
{
    public function index(Request $request): View
    {
        $tz = config('app.timezone');

        $referrals = ArtistReferral::query()
            ->where('referrer_user_id', $request->user()->id)
            ->with(['referred.userDetail', 'qualifiedBooking'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ArtistReferral $referral) use ($tz) {
                $artist = $referral->referred;
                $name = trim(($artist?->first_name ?? '').' '.($artist?->last_name ?? ''));
                $username = trim((string) ($artist?->userDetail?->user_name ?? ''));

                if ($name === '') {
                    $name = $username !== '' ? $username : ($artist?->email ?? 'Artist');
                }

                $statusKey = ArtistReferralRewardService::artistDashboardStatusKeyForReferral($referral);
                $statusLabel = ArtistReferralRewardService::artistDashboardStatusLabelForReferral($referral);
                $amount = (float) ($referral->reward_amount ?: 0);

                if (in_array($statusKey, ['waiting_for_payout', 'earned'], true) && $amount <= 0) {
                    $amount = 20.0;
                }

                $firstBookingAt = $referral->qualified_at
                    ?? optional($referral->qualifiedBooking)->created_at;

                return [
                    'id' => $referral->id,
                    'artist_name' => $name,
                    'artist_short' => $this->shortArtistName($name, $username),
                    'artist_email' => (string) ($artist?->email ?? ''),
                    'artist_username' => $username,
                    'date' => optional($referral->created_at)->timezone($tz)->format('M j, Y'),
                    'date_full' => optional($referral->created_at)->timezone($tz)->format('M j, Y g:i A'),
                    'first_booking' => $firstBookingAt
                        ? Carbon::parse($firstBookingAt)->timezone($tz)->format('M j, Y')
                        : null,
                    'first_booking_full' => $firstBookingAt
                        ? Carbon::parse($firstBookingAt)->timezone($tz)->format('M j, Y g:i A')
                        : null,
                    'status' => $statusKey,
                    'status_label' => $statusLabel,
                    'amount' => $amount,
                    'fee_waived' => (bool) $referral->fee_waived,
                    'qualified_at' => optional($referral->qualified_at)->timezone($tz)?->format('M j, Y g:i A'),
                    'reward_paid_at' => optional($referral->reward_paid_at)->timezone($tz)?->format('M j, Y g:i A'),
                    'reward_paid_date' => optional($referral->reward_paid_at)->timezone($tz)?->format('M j, Y'),
                    'rejection_reason' => $referral->isRejected() ? (string) $referral->rejection_reason : null,
                    'rejected_at' => optional($referral->rejected_at)->timezone($tz)?->format('M j, Y g:i A'),
                ];
            })
            ->values();

        $earnings = $this->buildEarningsSummary($referrals);
        $counts = [
            'all' => $referrals->count(),
            'signed_up' => $referrals->where('status', 'signed_up')->count(),
            'earned' => $referrals->whereIn('status', ['waiting_for_payout', 'earned'])->count(),
        ];

        return view('artist.refer-earn.index', [
            'referrals' => $referrals,
            'earnings' => $earnings,
            'counts' => $counts,
            'activeNav' => 'referrals',
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $referrals
     * @return array{
     *     pending_total: float,
     *     on_its_way_total: float,
     *     paid_total: float,
     *     total_earned: float,
     *     pending_artists: int,
     *     on_its_way_artists: int,
     *     paid_artists: int,
     *     total_artists: int,
     *     items: Collection<int, array{
     *         artist_label: string,
     *         artist_name: string,
     *         subtitle: string,
     *         details: string,
     *         amount: float,
     *         state: string,
     *         status_label: string,
     *         date: string,
     *         sort_at: string
     *     }>
     * }
     */
    private function buildEarningsSummary(Collection $referrals): array
    {
        $onItsWayTotal = 0.0;
        $paidTotal = 0.0;
        $onItsWayArtists = 0;
        $paidArtists = 0;
        $pendingArtists = 0;
        $items = collect();

        foreach ($referrals as $referral) {
            $status = (string) ($referral['status'] ?? '');
            $amount = (float) ($referral['amount'] ?? 0);

            if ($status === 'rejected') {
                continue;
            }

            if ($status === 'earned') {
                $paidTotal += $amount;
                $paidArtists++;
                $paidAt = (string) ($referral['reward_paid_at'] ?? '');
                $paidDate = (string) ($referral['reward_paid_date'] ?? '—');

                $items->push([
                    'artist_label' => $this->earningsArtistLabel($referral),
                    'artist_name' => (string) ($referral['artist_name'] ?? 'Artist'),
                    'subtitle' => 'Paid '.$paidDate,
                    'details' => 'Sent to your payout account',
                    'amount' => $amount,
                    'state' => 'paid',
                    'status_label' => 'Paid',
                    'date' => $paidDate !== '—' ? $paidDate : (string) ($referral['date'] ?? '—'),
                    'sort_at' => $paidAt !== '' ? $paidAt : (string) ($referral['date_full'] ?? ''),
                ]);

                continue;
            }

            if ($status === 'waiting_for_payout') {
                $onItsWayTotal += $amount;
                $onItsWayArtists++;
                $qualifiedAt = (string) ($referral['qualified_at'] ?? '');
                $firstBooking = (string) ($referral['first_booking'] ?? ($referral['date'] ?? '—'));
                $arrivesBy = $qualifiedAt !== ''
                    ? Carbon::parse($qualifiedAt)->addDays(14)->format('M j')
                    : null;

                $items->push([
                    'artist_label' => $this->earningsArtistLabel($referral),
                    'artist_name' => (string) ($referral['artist_name'] ?? 'Artist'),
                    'subtitle' => 'On its way',
                    'details' => $arrivesBy ? 'Arrives by '.$arrivesBy : 'Paid automatically',
                    'amount' => $amount,
                    'state' => 'on_its_way',
                    'status_label' => 'On its way',
                    'date' => $firstBooking,
                    'sort_at' => $qualifiedAt !== '' ? $qualifiedAt : (string) ($referral['date_full'] ?? ''),
                ]);

                continue;
            }

            if ($status === 'signed_up') {
                $pendingArtists++;
            }
        }

        return [
            'pending_total' => 0.0,
            'on_its_way_total' => $onItsWayTotal,
            'paid_total' => $paidTotal,
            'total_earned' => $onItsWayTotal + $paidTotal,
            'pending_artists' => $pendingArtists,
            'on_its_way_artists' => $onItsWayArtists,
            'paid_artists' => $paidArtists,
            'total_artists' => $onItsWayArtists + $paidArtists,
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
        return $this->shortArtistName(
            (string) ($referral['artist_name'] ?? ''),
            (string) ($referral['artist_username'] ?? '')
        );
    }

    private function shortArtistName(string $fullName, string $username = ''): string
    {
        $fullName = trim($fullName);
        $parts = preg_split('/\s+/', $fullName) ?: [];

        if (count($parts) >= 2) {
            $first = $parts[0];
            $lastInitial = strtoupper(substr($parts[count($parts) - 1], 0, 1));

            return $first.' '.$lastInitial.'.';
        }

        if ($fullName !== '') {
            return $fullName;
        }

        $username = trim($username);

        return $username !== '' ? '@'.$username : 'Artist';
    }
}
