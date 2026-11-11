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

        $outstandingArtistFee = $userDetail
            ? $this->payoutService->outstandingArtistPlatformFee($artistUserId, $userDetail)
            : 0.0;

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
                'outstanding_artist_fee' => round($outstandingArtistFee, 2),
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
        $fullBookingFee = max(0, round((float) $breakdown['booking_fee'], 2));
        $net = $breakdown['net'];

        $artistPercent = 100;
        $paymentType = 'artist_account';
        $studioGets = 0.0;
        if ($userDetail) {
            $artistPercent = $this->payoutService->resolvePayoutArtistPercent($booking, $userDetail);
            $paymentType = $this->payoutService->resolvePayoutPaymentType($booking, $userDetail);
            $studioGets = $this->payoutService->computeStudioPayoutAmount($booking, $userDetail);
        }
        $hasSplit = $paymentType === 'studio_account' && $artistPercent < 100;

        // With a studio split the booking fee is shared by % — show only the artist's part.
        $fee = $hasSplit
            ? round($fullBookingFee * ($artistPercent / 100), 2)
            : $fullBookingFee;

        $payout = $booking->artistPayout;
        if ($payout && $payout->isCompleted() && $booking->pay_artist) {
            $net = (float) $payout->amount;
            // Keep $fee as the artist's booking-fee share (do not use gross − net; that includes studio share).
        }

        $remaining = 0.0;
        if ($userDetail && $booking->payment_status === 'paid' && ! $booking->pay_artist) {
            $remaining = $this->payoutService->remainingPayoutAmount($booking, $userDetail);
        }

        $status = $this->resolvePaymentStatus($booking, $payout, $userDetail, $remaining);
        $paidAt = $booking->created_at?->format('Y-m-d') ?? '';

        // Studio column: still hide €0 studio share rows in the table sense via has_split UI.
        $hasSplitDisplay = $hasSplit && $studioGets > 0.009;

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

        $moneyUi = $this->payoutService->bookingDetailsMoneyBreakdown($booking, $userDetail);
        $breakdownRows = $this->buildBreakdownRowsFromMoneyUi(
            moneyUi: $moneyUi,
            amount: round($amount, 2),
            net: round($net, 2),
            artistFee: round($fee, 2),
        );

        // Artist-direct: Studio gets is —. Split rows keep studio share.
        $tableStudioGets = $hasSplitDisplay
            ? round((float) ($moneyUi['studio_gets_amount'] ?? $studioGets), 2)
            : 0.0;
        $tableFee = round($fee, 2);

        return [
            'id' => $booking->id,
            'client' => $name !== '' ? $name : 'Unknown client',
            'initials' => $this->initials($name),
            'service' => $booking->displayTitle(),
            'reference' => $booking->referenceLabel(),
            'date' => $paidAt,
            'amount' => round($amount, 2),
            'fee' => $tableFee,
            'studio_gets' => $tableStudioGets,
            'net' => round($net, 2),
            'remaining' => round($remaining, 2),
            'status' => $status,
            'status_label' => $statusLabel,
            'collected_kind' => $collectedKind,
            'has_split' => $hasSplitDisplay,
            'artist_percent' => $artistPercent,
            'deposit' => $breakdown['deposit'],
            'balance_platform' => $breakdown['balance_platform'],
            'balance_cash' => $breakdown['balance_cash'],
            'balance_pending' => $breakdown['balance_pending'],
            'breakdown_title' => $booking->displayTitle().' · '.$booking->referenceLabel(),
            'breakdown_rows' => $breakdownRows,
            'breakdown_footnote' => (string) ($moneyUi['footnote'] ?? ''),
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
     * Money modal rows. Artist-direct: Client paid → Split None → You get.
     * Revenue split: full fee / share / studio lines (matches booking money UI).
     *
     * @param  array<string, mixed>  $moneyUi
     * @return list<array{0: string, 1: string|float}>
     */
    private function buildBreakdownRowsFromMoneyUi(
        array $moneyUi,
        float $amount,
        float $net,
        float $artistFee,
    ): array {
        $hasSplit = (bool) ($moneyUi['has_split'] ?? false);

        // Artist direct: Client paid → Split None → You get (no Stripe / client booking-fee lines).
        if (! $hasSplit) {
            $rows = [
                ['Client paid', $amount],
            ];
            if ($artistFee > 0.009) {
                $rows[] = ['Booking fee (deducted from your share)', -$artistFee];
            }
            $rows[] = ['Split', 'None · booked at your own place'];
            $rows[] = ['You get', $net];

            return $rows;
        }

        $rows = [
            ['Client paid', $amount],
        ];

        $clientBookingFee = (string) ($moneyUi['booking_fee'] ?? '');
        $clientBookingFeeNote = trim((string) ($moneyUi['booking_fee_note'] ?? ''));
        if ($clientBookingFee !== '' && str_contains(strtolower($clientBookingFeeNote), 'client')) {
            $label = 'Service Booking Fee';
            if ($clientBookingFeeNote !== '') {
                $label .= ' ('.$clientBookingFeeNote.')';
            }
            $rows[] = [$label, $clientBookingFee];
        }

        $stripeFee = (string) ($moneyUi['stripe_fee'] ?? '');
        if ($stripeFee !== '') {
            $stripeLabel = 'Stripe fee';
            $stripeNote = trim((string) ($moneyUi['stripe_fee_note'] ?? ''));
            if ($stripeNote !== '') {
                $stripeLabel .= ' ('.$stripeNote.')';
            }
            $rows[] = [$stripeLabel, $stripeFee];
        }

        $rows[] = [
            (string) ($moneyUi['split_label'] ?? 'Split'),
            (string) ($moneyUi['split_value'] ?? ''),
        ];

        $yourShare = (string) ($moneyUi['your_share'] ?? '');
        if ($yourShare !== '') {
            $shareLabel = (string) ($moneyUi['your_share_label'] ?? 'Your share');
            $shareNote = trim((string) ($moneyUi['your_share_note'] ?? ''));
            if ($shareNote !== '' && preg_match('/^(\d+)%/', $shareNote, $m)) {
                $shareLabel .= ' ('.$m[1].'%)';
            }
            $rows[] = [$shareLabel, $yourShare];
        }

        $yourStripe = (string) ($moneyUi['your_stripe'] ?? '');
        if ($yourStripe !== '') {
            $rows[] = [
                (string) ($moneyUi['your_stripe_label'] ?? 'Your part of the Stripe fee'),
                $yourStripe,
            ];
        }

        $yourBookingFee = (string) ($moneyUi['your_booking_fee'] ?? '');
        if ($yourBookingFee !== '') {
            $ybLabel = 'Your part of the booking fee';
            $ybNote = trim((string) ($moneyUi['your_booking_fee_note'] ?? ''));
            if ($ybNote !== '') {
                $ybLabel .= ' ('.$ybNote.')';
            }
            $rows[] = [$ybLabel, $yourBookingFee];
        }

        $studioGetsAmount = (float) ($moneyUi['studio_gets_amount'] ?? 0);
        if ($studioGetsAmount > 0.009) {
            $studioLabel = (string) ($moneyUi['split_label'] ?? 'Split with studio');
            $studioName = trim(str_replace('Split with ', '', $studioLabel));
            if ($studioName === '' || $studioName === 'Split') {
                $studioName = 'Studio';
            }
            $rows[] = [$studioName.' gets', $studioGetsAmount];
        }

        $youGet = (float) ($moneyUi['you_get_amount'] ?? $net);
        $rows[] = ['You get', round($youGet, 2)];

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
