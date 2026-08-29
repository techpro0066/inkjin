<?php

namespace App\Services;

use App\Models\ArtistPayout;
use App\Models\Booking;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class ArtistPayoutService
{
    public function __construct(
        private readonly CancellationService $cancellationService,
        private readonly StripeConnectService $stripeConnect,
        private readonly BookingCheckoutPricingService $pricing,
    ) {}

    /**
     * @return array{processed: int, skipped: int, failed: int}
     */
    public function processEligibleBookings(): array
    {
        $stats = ['processed' => 0, 'skipped' => 0, 'failed' => 0];

        Booking::query()
            ->where('status', 'confirmed')
            ->where('payment_status', 'paid')
            ->where('pay_artist', false)
            ->where(function ($query) {
                $query->whereDoesntHave('artistPayout')
                    ->orWhereHas('artistPayout', fn ($payout) => $payout->where('status', ArtistPayout::STATUS_FAILED));
            })
            ->whereNotNull('deposit_amount')
            ->where('deposit_amount', '>', 0)
            ->with(['artist.userDetail.studio', 'artistPayout'])
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use (&$stats) {
                foreach ($bookings as $booking) {
                    try {
                        if ($this->processBooking($booking)) {
                            $stats['processed']++;
                        } else {
                            $stats['skipped']++;
                        }
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        Log::error('Artist payout processing failed', [
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $stats;
    }

    public function processBooking(Booking $booking): bool
    {
        $booking->loadMissing(['artist.userDetail.studio', 'artistPayout']);

        if ($booking->pay_artist) {
            return false;
        }

        if (($booking->payment_provider ?? 'stripe') === 'viva_iris') {
            return false;
        }

        $existingPayout = $booking->artistPayout;
        if ($existingPayout && $existingPayout->isCompleted()) {
            return false;
        }

        if (! $this->hasPassedCancellationDeadline($booking)) {
            return false;
        }

        $userDetail = $booking->artist?->userDetail;
        if (! $userDetail || ! $this->isArtistPaymentReady($userDetail)) {
            return false;
        }

        $connectedAccountId = $this->resolveConnectedAccountId($userDetail);
        if (! $connectedAccountId) {
            return false;
        }

        $amount = $this->computeArtistPayoutAmount($booking, $userDetail);
        if ($amount <= 0) {
            return false;
        }

        $currency = strtoupper((string) ($booking->currency ?: 'EUR'));
        $amountCents = (int) round($amount * 100);
        if ($amountCents < 1) {
            return false;
        }

        $locked = DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedBooking || $lockedBooking->pay_artist) {
                return null;
            }

            $payout = $lockedBooking->artistPayout()->first();
            if ($payout && $payout->isCompleted()) {
                return null;
            }

            return $lockedBooking;
        });

        if (! $locked) {
            return false;
        }

        try {
            $transfer = $this->stripeConnect->transferToConnectedAccount(
                destinationAccountId: $connectedAccountId,
                amountCents: $amountCents,
                currency: $currency,
                sourceChargeId: $this->stripeConnect->resolveChargeIdFromPaymentIntent($locked->payment_intent_id),
                metadata: [
                    'booking_id' => (string) $locked->id,
                    'artist_user_id' => (string) $locked->artist_user_id,
                    'payment_intent_id' => (string) ($locked->payment_intent_id ?? ''),
                ],
                idempotencyKey: 'artist_payout_booking_'.$locked->id,
            );
        } catch (ApiErrorException $e) {
            $this->recordFailedPayout($locked, $connectedAccountId, $amount, $currency, $e->getMessage());

            throw $e;
        }

        $completed = false;

        DB::transaction(function () use ($locked, $amount, $transfer, &$completed) {
            $booking = Booking::query()
                ->whereKey($locked->id)
                ->lockForUpdate()
                ->first();

            if (! $booking || $booking->pay_artist) {
                return;
            }

            $payout = $booking->artistPayout()->first();
            if ($payout && $payout->isCompleted()) {
                return;
            }

            if ($payout) {
                $payout->update([
                    'amount' => $amount,
                    'stripe_transfer_id' => $transfer['id'],
                    'stripe_account_id' => $transfer['destination'],
                    'currency' => $transfer['currency'],
                    'status' => ArtistPayout::STATUS_COMPLETED,
                    'failure_reason' => null,
                ]);
            } else {
                ArtistPayout::create([
                    'booking_id' => $booking->id,
                    'amount' => $amount,
                    'stripe_transfer_id' => $transfer['id'],
                    'stripe_account_id' => $transfer['destination'],
                    'currency' => $transfer['currency'],
                    'status' => ArtistPayout::STATUS_COMPLETED,
                ]);
            }

            $booking->update([
                'pay_artist' => true,
                'deposit_released' => true,
                'deposit_released_at' => now(),
            ]);

            $completed = true;
        });

        return $completed;
    }

    public function computeArtistPayoutAmount(Booking $booking, UserDetail $userDetail): float
    {
        $bookingFee = $this->pricing->resolveBookingFee($userDetail);
        $artistFee = (float) $bookingFee['artist_fee'];

        return max(0, round((float) $booking->deposit_amount - $artistFee, 2));
    }

    public function hasPassedCancellationDeadline(Booking $booking): bool
    {
        if ($booking->cancellation_deadline) {
            return now()->gte($booking->cancellation_deadline);
        }

        if (! $booking->booking_date || ! $booking->start_time_utc) {
            return false;
        }

        $bookingDateTime = Carbon::parse(
            $booking->booking_date->format('Y-m-d').' '.$booking->start_time_utc
        );
        $windowHours = $this->cancellationService->effectiveCancellationWindowHours($booking);

        return now()->gte($bookingDateTime->copy()->subHours($windowHours));
    }

    public function isArtistPaymentReady(UserDetail $userDetail): bool
    {
        $paymentType = (string) ($userDetail->payment_type ?? '');

        if ($paymentType === 'artist_account') {
            if (! empty($userDetail->stripe_requirement)) {
                return false;
            }

            return $this->isStripeAccountPayoutReady($userDetail->stripe_account_id);
        }

        if ($paymentType === 'studio_account') {
            if (($userDetail->payment_status ?? '') !== 'approved') {
                return false;
            }

            $studio = $userDetail->studio;
            if (! $studio) {
                return false;
            }

            if (! empty($studio->stripe_requirement) || ! empty($userDetail->stripe_requirement)) {
                return false;
            }

            return $this->isStripeAccountPayoutReady($studio->resolveStripeAccountId());
        }

        return false;
    }

    /**
     * Whether the artist can accept online payments from clients (bookings, payment links, quotes).
     * Platform-settled (inkjin) artists can accept payments before personal Stripe is ready.
     */
    public function canAcceptClientPayments(UserDetail $userDetail): bool
    {
        $paymentType = (string) ($userDetail->payment_type ?? '');

        if ($paymentType === 'inkjin_account' || $paymentType === '') {
            return true;
        }

        return $this->isArtistPaymentReady($userDetail);
    }

    /**
     * Whether the artist dashboard should show the global “setup payouts” banner.
     * Uses local flags only (no live Stripe API) so it is safe on every page.
     */
    public function needsPayoutSetupBanner(?UserDetail $userDetail): bool
    {
        return $this->needsPayoutSetupReminder($userDetail);
    }

    /**
     * Whether the artist still needs to complete payout setup (local flags only).
     */
    public function needsPayoutSetupReminder(?UserDetail $userDetail): bool
    {
        if (! $userDetail) {
            return true;
        }

        $paymentType = (string) ($userDetail->payment_type ?? '');

        if ($paymentType === 'inkjin_account') {
            return false;
        }

        if ($paymentType === '' || $paymentType === 'artist_account') {
            if (! empty($userDetail->stripe_requirement)) {
                return true;
            }

            if (($userDetail->payment_status ?? '') !== 'approved') {
                return true;
            }

            return trim((string) ($userDetail->stripe_account_id ?? '')) === '';
        }

        if ($paymentType === 'studio_account') {
            if (($userDetail->payment_status ?? '') !== 'approved') {
                return true;
            }

            if (! empty($userDetail->stripe_requirement)) {
                return true;
            }

            return ! empty($userDetail->studio?->stripe_requirement);
        }

        return true;
    }

    public function publicBookingUnavailableMessage(): string
    {
        return 'This artist is not currently accepting online bookings. Please check back later.';
    }

    public function clientPaymentsBlockedMessage(UserDetail $userDetail): string
    {
        $paymentType = (string) ($userDetail->payment_type ?? '');

        if ($paymentType === 'studio_account' && ($userDetail->payment_status ?? '') !== 'approved') {
            return 'Your studio payout approval is still pending. Complete payout setup in Payment settings before accepting client payments.';
        }

        if ($paymentType === 'studio_account' && (! empty($userDetail->stripe_requirement) || ! empty($userDetail->studio?->stripe_requirement))) {
            return 'Your studio Stripe account has pending requirements. Ask your studio to complete the required information before accepting client payments.';
        }

        if ($paymentType === 'artist_account' && ($userDetail->payment_status ?? '') !== 'approved') {
            if (! empty($userDetail->stripe_requirement)) {
                return 'Your Stripe account has pending requirements. Complete payout setup in Payment settings before accepting client payments.';
            }
        }

        $accountId = $this->resolveStripeAccountIdForStatus($userDetail);
        if ($accountId !== null && $this->stripeConnect->isConfigured()) {
            try {
                $status = $this->stripeConnect->getOnboardingStatus($accountId);
                if (! empty($status['currently_due'])) {
                    return 'Your Stripe account has pending requirements. Complete payout setup in Payment settings before accepting client payments.';
                }
                if (empty($status['charges_enabled']) || empty($status['payouts_enabled'])) {
                    return 'Your Stripe account is not fully enabled yet. Complete payout setup in Payment settings before accepting client payments.';
                }
            } catch (\Throwable) {
                // Fall through to generic message.
            }
        }

        if ($paymentType === 'artist_account' && trim((string) ($userDetail->stripe_account_id ?? '')) === '') {
            return 'Connect and complete your Stripe payout setup in Payment settings before accepting client payments.';
        }

        return 'Complete payout setup in Payment settings before accepting client payments.';
    }

    private function resolveStripeAccountIdForStatus(UserDetail $userDetail): ?string
    {
        $paymentType = (string) ($userDetail->payment_type ?? '');

        if ($paymentType === 'artist_account') {
            $accountId = trim((string) ($userDetail->stripe_account_id ?? ''));

            return $accountId !== '' ? $accountId : null;
        }

        if ($paymentType === 'studio_account') {
            return $userDetail->studio?->resolveStripeAccountId();
        }

        return null;
    }

    public function resolveConnectedAccountId(UserDetail $userDetail): ?string
    {
        $paymentType = (string) ($userDetail->payment_type ?? '');

        if ($paymentType === 'artist_account') {
            $accountId = trim((string) ($userDetail->stripe_account_id ?? ''));

            return $accountId !== '' ? $accountId : null;
        }

        if ($paymentType === 'studio_account') {
            return $userDetail->studio?->resolveStripeAccountId();
        }

        return null;
    }

    private function isStripeAccountPayoutReady(?string $accountId): bool
    {
        if ($accountId === null || $accountId === '') {
            return false;
        }

        if (! $this->stripeConnect->isConfigured()) {
            return false;
        }

        try {
            return $this->stripeConnect->isPayoutReady($accountId);
        } catch (\Throwable $e) {
            Log::warning('Unable to verify Stripe payout readiness', [
                'stripe_account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function recordFailedPayout(
        Booking $booking,
        string $connectedAccountId,
        float $amount,
        string $currency,
        string $reason,
    ): void {
        ArtistPayout::query()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'amount' => $amount,
                'stripe_account_id' => $connectedAccountId,
                'currency' => $currency,
                'status' => ArtistPayout::STATUS_FAILED,
                'failure_reason' => $reason,
            ],
        );
    }
}
