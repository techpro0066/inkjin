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

        $payoutMode = in_array(($userDetail?->payout_mode ?? 'manual'), ['manual', 'automatic'], true)
            ? (string) ($userDetail?->payout_mode ?? 'manual')
            : 'manual';

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

        return [
            'id' => $booking->id,
            'client' => $name !== '' ? $name : 'Unknown client',
            'initials' => $this->initials($name),
            'service' => $booking->displayTitle(),
            'reference' => $booking->referenceLabel(),
            'date' => $paidAt,
            'amount' => round($amount, 2),
            'fee' => round($fee, 2),
            'net' => $net,
            'remaining' => round($remaining, 2),
            'status' => $status,
            'deposit' => $breakdown['deposit'],
            'balance_platform' => $breakdown['balance_platform'],
            'balance_cash' => $breakdown['balance_cash'],
            'balance_pending' => $breakdown['balance_pending'],
        ];
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
