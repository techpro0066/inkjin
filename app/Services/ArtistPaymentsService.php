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
            ->with(['user', 'tattoo', 'artistPayout'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $payments = [];
        $availableBalance = 0.0;
        $pendingTotal = 0.0;
        $pendingCount = 0;
        $totalEarned = 0.0;
        $firstPaidAt = null;

        foreach ($bookings as $booking) {
            $payment = $this->formatPayment($booking, $userDetail);
            $payments[] = $payment;

            if ($booking->created_at && ($firstPaidAt === null || $booking->created_at->lt($firstPaidAt))) {
                $firstPaidAt = $booking->created_at->copy();
            }

            if ($payment['status'] === 'Completed') {
                $availableBalance += $payment['net'];
                $totalEarned += $payment['net'];
            } elseif ($payment['status'] === 'Pending') {
                $pendingTotal += $payment['net'];
                $pendingCount++;
                $totalEarned += $payment['net'];
            } elseif ($payment['status'] === 'Refunded') {
                // excluded from earned totals
            }
        }

        $sinceLabel = $firstPaidAt
            ? 'Since '.$firstPaidAt->format('M Y')
            : 'No payments yet';

        $payoutAccountConnected = $userDetail
            ? $this->payoutService->isArtistPaymentReady($userDetail)
            : false;

        return [
            'payments' => $payments,
            'stats' => [
                'available_balance' => round($availableBalance, 2),
                'pending_total' => round($pendingTotal, 2),
                'pending_count' => $pendingCount,
                'total_earned' => round($totalEarned, 2),
                'since_label' => $sinceLabel,
                'currency_symbol' => '€',
                'payout_account_connected' => $payoutAccountConnected,
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

        $amount = (float) $booking->deposit_amount;
        $fee = $this->resolveArtistFee($booking, $userDetail);
        $net = max(0, round($amount - $fee, 2));

        $payout = $booking->artistPayout;
        if ($payout && $payout->isCompleted()) {
            $net = (float) $payout->amount;
            $fee = max(0, round($amount - $net, 2));
        }

        $status = $this->resolvePaymentStatus($booking, $payout);
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
            'status' => $status,
        ];
    }

    private function resolveArtistFee(Booking $booking, ?UserDetail $userDetail): float
    {
        if (! $userDetail) {
            return max(0, (float) $booking->platform_fee);
        }

        $net = $this->payoutService->computeArtistPayoutAmount($booking, $userDetail);

        return max(0, round((float) $booking->deposit_amount - $net, 2));
    }

    private function resolvePaymentStatus(Booking $booking, ?ArtistPayout $payout): string
    {
        if ($booking->payment_status === 'refunded' || $booking->refund_amount > 0) {
            return 'Refunded';
        }

        if ($booking->payment_status === 'failed') {
            return 'Failed';
        }

        if ($payout?->status === ArtistPayout::STATUS_FAILED) {
            return 'Failed';
        }

        if ($booking->pay_artist || $payout?->status === ArtistPayout::STATUS_COMPLETED) {
            return 'Completed';
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
