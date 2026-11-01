<?php

namespace App\Services;

use App\Models\ArtistPayout;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserDetail;

class ArtistPaymentsService
{
    public function __construct(
        private readonly ArtistPayoutService $payoutService,
    ) {}

    /**
     * @return array{
     *     payments: array<int, array<string, mixed>>,
     *     stats: array<string, mixed>
     * }
     */
    public function buildForArtist(int $artistUserId): array
    {
        $artist = User::query()->with('userDetail.studio')->find($artistUserId);
        $userDetail = $artist?->userDetail;

        $bookings = Booking::query()
            ->where('artist_user_id', $artistUserId)
            ->whereIn('payment_status', ['paid', 'refunded', 'failed'])
            ->with(['user', 'tattoo', 'artistPayout', 'balanceCollections'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $payments = [];
        $availableBalance = 0.0;
        $pendingTotal = 0.0;
        $pendingCount = 0;
        $totalEarned = 0.0;
        $firstPaidAt = null;

        $pendingSchedule = [];

        foreach ($bookings as $booking) {
            $payment = $this->formatPayment($booking, $userDetail);
            $payments[] = $payment;

            if ($userDetail && $payment['status'] === 'Pending') {
                $pendingInfo = $this->payoutService->payoutPendingInfo($booking, $userDetail);
                if ($pendingInfo) {
                    $pendingSchedule[] = [
                        'booking_id' => $booking->id,
                        'reference' => $booking->referenceLabel(),
                        'client' => $payment['client'],
                        'service' => $payment['service'],
                        'amount' => $pendingInfo['amount'],
                        'available_at' => $pendingInfo['available_at'],
                        'available_label' => $pendingInfo['available_label'],
                        'reason' => $pendingInfo['reason'],
                        'sort_key' => $pendingInfo['sort_key'],
                    ];
                }
            }

            if ($booking->created_at && ($firstPaidAt === null || $booking->created_at->lt($firstPaidAt))) {
                $firstPaidAt = $booking->created_at->copy();
            }

            if (in_array($payment['status'], ['Completed', 'Pending', 'Available'], true)) {
                $totalEarned += $payment['net'];
            }

            if ($payment['status'] === 'Available') {
                $availableBalance += $payment['remaining'];
            } elseif ($payment['status'] === 'Pending') {
                $pendingTotal += $payment['remaining'] > 0 ? $payment['remaining'] : $payment['net'];
                $pendingCount++;
            }
        }

        // Prefer service calculation so Available matches request-payout eligibility.
        if ($userDetail) {
            $availableBalance = $this->payoutService->availableBalanceForArtist($artistUserId, $userDetail);
        }

        usort($pendingSchedule, fn (array $a, array $b) => strcmp($a['sort_key'], $b['sort_key']));

        $sinceLabel = $firstPaidAt
            ? 'Since '.$firstPaidAt->format('M Y')
            : 'No payments yet';

        $payoutAccountConnected = $userDetail
            ? $this->payoutService->isArtistPaymentReady($userDetail)
            : false;

        $payoutModeRaw = $userDetail?->activePayout()?->payout_mode ?? 'automatic';
        $payoutMode = in_array($payoutModeRaw, ['manual', 'automatic'], true)
            ? (string) $payoutModeRaw
            : 'automatic';

        $paymentType = $userDetail
            ? $userDetail->resolvedPayoutPaymentType()
            : 'artist_account';
        $payoutTypeLabel = $paymentType === 'studio_account'
            ? 'Studio payouts'
            : 'Artist payouts';

        $nextPayoutMeta = $payoutMode === 'automatic'
            ? 'Paid automatically to your bank'
            : ($availableBalance > 0.009
                ? 'Ready to withdraw to your bank'
                : 'No balance ready to withdraw yet');

        $nextArrivalLabel = null;
        if ($availableBalance > 0.009) {
            $nextArrivalLabel = $payoutMode === 'automatic'
                ? 'Paid out automatically when funds become available'
                : 'Available now · request a payout when you want';
        } elseif ($pendingSchedule !== []) {
            $nextArrivalLabel = $pendingSchedule[0]['available_label'] ?? null;
        }

        return [
            'payments' => $payments,
            'stats' => [
                'available_balance' => round($availableBalance, 2),
                'pending_total' => round($pendingTotal, 2),
                'pending_count' => $pendingCount,
                'pending_schedule' => $pendingSchedule,
                'total_earned' => round($totalEarned, 2),
                'since_label' => $sinceLabel,
                'currency_symbol' => '€',
                'payout_account_connected' => $payoutAccountConnected,
                'payout_mode' => $payoutMode,
                'payout_type_label' => $payoutTypeLabel,
                'next_payout_meta' => $nextPayoutMeta,
                'next_arrival_label' => $nextArrivalLabel,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPayment(Booking $booking, ?UserDetail $userDetail): array
    {
        $user = $booking->user;
        $name = $user
            ? trim($user->first_name.' '.$user->last_name)
            : 'Client #'.$booking->user_id;

        $breakdown = $userDetail
            ? $this->payoutService->earningBreakdownForArtist($booking, $userDetail)
            : [
                'deposit' => max(0, round((float) ($booking->deposit_amount ?? 0), 2)),
                'balance_platform' => 0.0,
                'balance_cash' => 0.0,
                'balance_pending' => 0.0,
                'booking_fee' => max(0, (float) ($booking->platform_fee ?? 0)),
                'gross' => max(0, round((float) ($booking->deposit_amount ?? 0), 2)),
                'net' => max(0, round((float) ($booking->deposit_amount ?? 0), 2)),
            ];

        $amount = $breakdown['gross'];
        $fee = $breakdown['booking_fee'];
        $net = $breakdown['net'];

        $payout = $booking->artistPayout;
        if ($payout && $payout->isCompleted() && $booking->pay_artist) {
            $net = (float) $payout->amount;
            $fee = max(0, round($amount - $net, 2));
        }

        $remaining = 0.0;
        if ($userDetail && $booking->payment_status === 'paid' && ! $booking->pay_artist) {
            $remaining = $this->payoutService->remainingPayoutAmount($booking, $userDetail);
        }

        $status = $this->resolvePaymentStatus($booking, $payout, $userDetail, $remaining);
        $paidAt = $booking->created_at?->format('Y-m-d') ?? '';

        $artistPercent = 100;
        $paymentType = 'artist_account';
        $studioGets = 0.0;
        if ($userDetail) {
            $artistPercent = $this->payoutService->resolvePayoutArtistPercent($booking, $userDetail);
            $paymentType = $this->payoutService->resolvePayoutPaymentType($booking, $userDetail);
            $studioGets = $this->payoutService->computeStudioPayoutAmount($booking, $userDetail);
        }
        $hasSplit = $paymentType === 'studio_account' && $artistPercent < 100 && $studioGets > 0.009;

        $statusLabel = match ($status) {
            'Available' => 'On the way',
            'Completed' => 'Paid out',
            default => $status,
        };

        $collectedKind = $this->collectedKindLabel($breakdown);
        $balanceDue = max(0, round((float) ($breakdown['balance_pending'] ?? 0), 2));
        if ($balanceDue > 0.009) {
            $collectedKind .= ' · €'.number_format($balanceDue, 2).' due';
        }

        $studioName = trim((string) ($userDetail?->resolvedStudioName() ?? ''));
        $breakdownRows = $this->buildBreakdownRows(
            amount: round($amount, 2),
            fee: round($fee, 2),
            net: round($net, 2),
            studioGets: round($studioGets, 2),
            hasSplit: $hasSplit,
            artistPercent: $artistPercent,
            studioName: $studioName,
        );

        return [
            'id' => $booking->id,
            'client' => $name !== '' ? $name : 'Unknown client',
            'initials' => $this->initials($name),
            'service' => $booking->displayTitle(),
            'reference' => $booking->referenceLabel(),
            'date' => $paidAt,
            'amount' => round($amount, 2),
            'fee' => round($fee, 2),
            'studio_gets' => round($studioGets, 2),
            'net' => round($net, 2),
            'remaining' => round($remaining, 2),
            'status' => $status,
            'status_label' => $statusLabel,
            'collected_kind' => $collectedKind,
            'has_split' => $hasSplit,
            'artist_percent' => $artistPercent,
            'deposit' => $breakdown['deposit'],
            'balance_platform' => $breakdown['balance_platform'],
            'balance_cash' => $breakdown['balance_cash'],
            'balance_pending' => $breakdown['balance_pending'],
            'breakdown_title' => $booking->displayTitle().' · '.$booking->referenceLabel(),
            'breakdown_rows' => $breakdownRows,
        ];
    }

    /**
     * @param  array<string, mixed>  $breakdown
     */
    private function collectedKindLabel(array $breakdown): string
    {
        $deposit = (float) ($breakdown['deposit'] ?? 0);
        $balancePlatform = (float) ($breakdown['balance_platform'] ?? 0);
        $balanceCash = (float) ($breakdown['balance_cash'] ?? 0);

        if ($deposit > 0.009 && ($balancePlatform > 0.009 || $balanceCash > 0.009)) {
            return 'Deposit + balance';
        }
        if ($balancePlatform > 0.009 || $balanceCash > 0.009) {
            return 'Balance';
        }
        if ($deposit > 0.009) {
            return 'Deposit';
        }

        return 'Payment';
    }

    /**
     * @return list<array{0: string, 1: string|float}>
     */
    private function buildBreakdownRows(
        float $amount,
        float $fee,
        float $net,
        float $studioGets,
        bool $hasSplit,
        int $artistPercent,
        string $studioName,
    ): array {
        $rows = [
            ['Client paid', $amount],
        ];

        if ($fee > 0.009) {
            $rows[] = ['Booking fee (deducted from your share)', -$fee];
        }

        if ($hasSplit) {
            $studioLabel = $studioName !== '' ? $studioName : 'studio';
            $rows[] = ['Split with '.$studioLabel, 'You '.$artistPercent.'% · Studio '.(100 - $artistPercent).'%'];
            $rows[] = [$studioLabel.' gets', $studioGets];
        } else {
            $rows[] = ['Split', 'None · booked at your own place'];
        }

        $rows[] = ['You get', $net];

        return $rows;
    }

    private function resolvePaymentStatus(
        Booking $booking,
        ?ArtistPayout $payout,
        ?UserDetail $userDetail,
        float $remaining,
    ): string {
        if ($booking->payment_status === 'refunded' || $booking->refund_amount > 0) {
            return 'Refunded';
        }

        if ($booking->payment_status === 'failed') {
            return 'Failed';
        }

        if ($payout?->status === ArtistPayout::STATUS_FAILED && $remaining <= 0 && ! $booking->pay_artist) {
            return 'Failed';
        }

        if ($booking->pay_artist) {
            return 'Completed';
        }

        if ($booking->payment_status === 'paid' && $userDetail) {
            if ($remaining > 0 && $this->payoutService->isBookingPayoutEligible($booking)) {
                return 'Available';
            }

            return 'Pending';
        }

        return 'Pending';
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : '?';
    }
}
