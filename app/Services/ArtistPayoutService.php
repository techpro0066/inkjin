<?php

namespace App\Services;

use App\Exceptions\InsufficientPlatformBalanceException;
use App\Models\ArtistPayout;
use App\Models\BalanceCollection;
use App\Models\Booking;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class ArtistPayoutService
{
    public function __construct(
        private readonly CancellationService $cancellationService,
        private readonly StripeConnectService $stripeConnect,
        private readonly BookingCheckoutPricingService $pricing,
        private readonly PlatformPayoutAlertService $payoutAlert,
    ) {}

    /**
     * @return array{processed: int, skipped: int, failed: int}
     */
    public function processEligibleBookings(): array
    {
        $stats = ['processed' => 0, 'skipped' => 0, 'failed' => 0];

        Booking::query()
            ->whereIn('status', ['completed'])
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
                        $userDetail = $booking->artist?->userDetail;
                        if ($userDetail && $this->isManualPayoutMode($userDetail)) {
                            $stats['skipped']++;
                            continue;
                        }

                        if ($this->processBooking($booking)) {
                            $stats['processed']++;
                        } else {
                            $stats['skipped']++;
                        }
                    } catch (InsufficientPlatformBalanceException $e) {
                        $stats['failed']++;
                        Log::warning('Artist payout skipped: insufficient platform Stripe balance', [
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage(),
                        ]);
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

    public function processBooking(Booking $booking, bool $allowManualRequest = false): bool
    {
        $booking->loadMissing(['artist.userDetail.studio', 'artistPayout']);

        if ($booking->pay_artist) {
            return false;
        }

        if (! $this->isBookingPayoutEligible($booking)) {
            return false;
        }

        $userDetail = $booking->artist?->userDetail;
        if (! $userDetail || ! $this->isArtistPaymentReady($userDetail)) {
            return false;
        }

        if (! $allowManualRequest && $this->isManualPayoutMode($userDetail)) {
            return false;
        }

        $amount = $this->remainingPayoutAmount($booking, $userDetail);
        if ($amount <= 0) {
            return false;
        }

        return $this->transferBookingPayout($booking, $userDetail, $amount, $allowManualRequest);
    }

    /**
     * Manual payout request: transfer up to $amount from eligible booking balances (FIFO).
     *
     * @return array{amount: float, currency: string, bookings: int}
     */
    public function requestManualPayout(UserDetail $userDetail, float $amount): array
    {
        $userDetail->loadMissing(['user', 'studio']);

        if (! $this->isManualPayoutMode($userDetail)) {
            throw ValidationException::withMessages([
                'amount' => ['Manual payout requests are only available when payout mode is set to manual.'],
            ]);
        }

        if (! $this->isArtistPaymentReady($userDetail)) {
            throw ValidationException::withMessages([
                'amount' => ['Complete payout setup before requesting a payout.'],
            ]);
        }

        $amount = round($amount, 2);
        if ($amount < 0.01) {
            throw ValidationException::withMessages([
                'amount' => ['Enter an amount greater than zero.'],
            ]);
        }

        $artistUserId = (int) $userDetail->user_id;
        $available = $this->availableBalanceForArtist($artistUserId, $userDetail);
        if ($amount > $available + 0.001) {
            throw ValidationException::withMessages([
                'amount' => ['Amount cannot exceed your available balance of '.number_format($available, 2).'.'],
            ]);
        }

        $currency = 'EUR';
        if (! $this->payoutAlert->hasSufficientPlatformBalance($amount, $currency)) {
            $this->notifyLowPlatformBalance(
                amount: $amount,
                currency: $currency,
                source: 'manual_request',
                userDetail: $userDetail,
            );

            throw ValidationException::withMessages([
                'amount' => ['Withdraw is unavailable right now. Please contact support.'],
            ]);
        }

        $eligible = $this->eligibleBookingsForArtist($artistUserId);
        $remaining = $amount;
        $paidTotal = 0.0;
        $bookingsPaid = 0;

        foreach ($eligible as $booking) {
            if ($remaining < 0.01) {
                break;
            }

            $booking->loadMissing(['artist.userDetail.studio', 'artistPayout']);
            $slice = min($remaining, $this->remainingPayoutAmount($booking, $userDetail));
            if ($slice < 0.01) {
                continue;
            }

            if ($this->transferBookingPayout($booking, $userDetail, $slice, allowManualRequest: true)) {
                $paidTotal = round($paidTotal + $slice, 2);
                $remaining = round($remaining - $slice, 2);
                $bookingsPaid++;
                $currency = strtoupper((string) ($booking->currency ?: $currency));
            }
        }

        if ($paidTotal < 0.01) {
            throw ValidationException::withMessages([
                'amount' => ['No eligible balance could be paid out right now. Please try again later.'],
            ]);
        }

        return [
            'amount' => $paidTotal,
            'currency' => $currency,
            'bookings' => $bookingsPaid,
        ];
    }

    public function isManualPayoutMode(?UserDetail $userDetail): bool
    {
        if (! $userDetail) {
            return true;
        }

        $mode = (string) ($userDetail->payout_mode ?? 'manual');

        return $mode !== 'automatic';
    }

    public function availableBalanceForArtist(int $artistUserId, ?UserDetail $userDetail = null): float
    {
        if (! $userDetail) {
            $userDetail = UserDetail::query()->where('user_id', $artistUserId)->first();
        }
        if (! $userDetail) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($this->eligibleBookingsForArtist($artistUserId) as $booking) {
            $total += $this->remainingPayoutAmount($booking, $userDetail);
        }

        return round($total, 2);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Booking>
     */
    public function eligibleBookingsForArtist(int $artistUserId)
    {
        return Booking::query()
            ->where('artist_user_id', $artistUserId)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->where('pay_artist', false)
            ->whereNotNull('deposit_amount')
            ->where('deposit_amount', '>', 0)
            ->with(['artistPayout', 'balanceCollections'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Booking $booking) => $this->isBookingPayoutEligible($booking))
            ->values();
    }

    /**
     * Whether a paid booking's artist share can be withdrawn or auto-paid out.
     * Requires the booking to be completed and the cancellation window to have passed.
     */
    public function isBookingPayoutEligible(Booking $booking): bool
    {
        if (($booking->payment_status ?? '') !== 'paid') {
            return false;
        }

        if ($booking->pay_artist) {
            return false;
        }

        if (($booking->status ?? '') !== 'completed') {
            return false;
        }

        return $this->hasPassedCancellationDeadline($booking);
    }

    public function remainingPayoutAmount(Booking $booking, UserDetail $userDetail): float
    {
        if ($booking->pay_artist) {
            return 0.0;
        }

        $net = $this->computeArtistPayoutAmount($booking, $userDetail);
        $released = 0.0;
        $payout = $booking->relationLoaded('artistPayout')
            ? $booking->artistPayout
            : $booking->artistPayout()->first();

        if ($payout && $payout->isCompleted()) {
            $released = (float) $payout->amount;
        }

        return max(0, round($net - $released, 2));
    }

    /**
     * Transfer a specific amount for a booking and record the payout.
     */
    public function transferBookingPayout(
        Booking $booking,
        UserDetail $userDetail,
        float $amount,
        bool $allowManualRequest = true,
    ): bool {
        $booking->loadMissing(['artist.userDetail.studio', 'artistPayout']);

        if ($booking->pay_artist) {
            return false;
        }

        if (! $this->isBookingPayoutEligible($booking)) {
            return false;
        }

        if (! $this->isArtistPaymentReady($userDetail)) {
            return false;
        }

        if (! $allowManualRequest && $this->isManualPayoutMode($userDetail)) {
            return false;
        }

        $amount = round($amount, 2);
        $remaining = $this->remainingPayoutAmount($booking, $userDetail);
        if ($amount < 0.01 || $amount > $remaining + 0.001) {
            return false;
        }
        $amount = min($amount, $remaining);

        $connectedAccountId = $this->resolveConnectedAccountId($userDetail);
        if (! $connectedAccountId) {
            return false;
        }

        $currency = strtoupper((string) ($booking->currency ?: 'EUR'));
        $amountCents = (int) round($amount * 100);
        if ($amountCents < 1) {
            return false;
        }

        $locked = DB::transaction(function () use ($booking, $userDetail, $amount) {
            $lockedBooking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedBooking || $lockedBooking->pay_artist) {
                return null;
            }

            $lockedBooking->load('artistPayout');
            if ($this->remainingPayoutAmount($lockedBooking, $userDetail) < $amount - 0.001) {
                return null;
            }

            return $lockedBooking;
        });

        if (! $locked) {
            return false;
        }

        $alreadyReleased = 0.0;
        $existingPayout = $locked->artistPayout;
        if ($existingPayout && $existingPayout->isCompleted()) {
            $alreadyReleased = (float) $existingPayout->amount;
        }
        $newReleasedTotal = round($alreadyReleased + $amount, 2);
        $fullNet = $this->computeArtistPayoutAmount($locked, $userDetail);
        $fullyPaid = $newReleasedTotal >= round($fullNet, 2) - 0.001;

        try {
            // Full single-booking auto payouts can link to the Stripe charge.
            // Partial / multi-request manual payouts transfer from platform balance.
            $sourceChargeId = null;
            if (
                $alreadyReleased < 0.01
                && $fullyPaid
                && ($locked->payment_provider ?? 'stripe') !== 'viva_iris'
            ) {
                $sourceChargeId = $this->stripeConnect->resolveChargeIdFromPaymentIntent($locked->payment_intent_id);
            }

            $this->guardPlatformBalanceForTransfer(
                amount: $amount,
                currency: $currency,
                sourceChargeId: $sourceChargeId,
                allowManualRequest: $allowManualRequest,
                booking: $locked,
                userDetail: $userDetail,
            );

            $transfer = $this->stripeConnect->transferToConnectedAccount(
                destinationAccountId: $connectedAccountId,
                amountCents: $amountCents,
                currency: $currency,
                sourceChargeId: $sourceChargeId,
                metadata: [
                    'booking_id' => (string) $locked->id,
                    'artist_user_id' => (string) $locked->artist_user_id,
                    'payment_intent_id' => (string) ($locked->payment_intent_id ?? ''),
                    'payment_provider' => (string) ($locked->payment_provider ?? 'stripe'),
                    'payout_slice' => (string) $amount,
                    'fully_paid' => $fullyPaid ? '1' : '0',
                ],
                idempotencyKey: 'artist_payout_booking_'.$locked->id.'_'.$amountCents.'_'.(int) round($newReleasedTotal * 100),
            );
        } catch (ApiErrorException $e) {
            if ($this->isInsufficientStripeBalanceError($e)) {
                $this->notifyLowPlatformBalance(
                    amount: $amount,
                    currency: $currency,
                    source: $allowManualRequest ? 'manual_request' : 'automatic_payout',
                    userDetail: $userDetail,
                    booking: $locked,
                );

                if ($allowManualRequest) {
                    throw ValidationException::withMessages([
                        'amount' => ['Withdraw is unavailable right now. Please contact support.'],
                    ]);
                }

                throw new InsufficientPlatformBalanceException($e->getMessage());
            }

            $this->recordFailedPayout($locked, $connectedAccountId, $amount, $currency, $e->getMessage());

            throw $e;
        }

        $completed = false;

        DB::transaction(function () use ($locked, $newReleasedTotal, $transfer, $fullyPaid, &$completed) {
            $booking = Booking::query()
                ->whereKey($locked->id)
                ->lockForUpdate()
                ->first();

            if (! $booking || $booking->pay_artist) {
                return;
            }

            $payout = $booking->artistPayout()->first();
            $payload = [
                'amount' => $newReleasedTotal,
                'stripe_transfer_id' => $transfer['id'],
                'stripe_account_id' => $transfer['destination'],
                'currency' => $transfer['currency'],
                'status' => ArtistPayout::STATUS_COMPLETED,
                'failure_reason' => null,
            ];

            if ($payout) {
                $payout->update($payload);
            } else {
                ArtistPayout::create([
                    'booking_id' => $booking->id,
                    ...$payload,
                ]);
            }

            if ($fullyPaid) {
                $booking->update([
                    'pay_artist' => true,
                    'deposit_released' => true,
                    'deposit_released_at' => now(),
                ]);
            }

            $completed = true;
        });

        return $completed;
    }

    /**
     * Per-booking earnings breakdown for the artist dashboard.
     *
     * @return array{
     *     deposit: float,
     *     balance_platform: float,
     *     balance_cash: float,
     *     balance_pending: float,
     *     booking_fee: float,
     *     gross: float,
     *     net: float
     * }
     */
    public function earningBreakdownForArtist(Booking $booking, ?UserDetail $userDetail): array
    {
        $deposit = max(0, round((float) ($booking->deposit_amount ?? 0), 2));
        $balancePlatform = $this->platformCollectedBalanceAmount($booking);
        $balanceCash = $this->cashBalanceAmount($booking);
        $balancePending = $this->pendingBalanceAmount($booking);
        $gross = round($deposit + $balancePlatform, 2);

        $bookingFee = 0.0;
        if ($userDetail) {
            $bookingFee = (float) $this->pricing->resolveBookingFee($userDetail)['artist_fee'];
        }

        $net = $userDetail
            ? $this->computeArtistPayoutAmount($booking, $userDetail)
            : max(0, round($gross, 2));

        return [
            'deposit' => $deposit,
            'balance_platform' => $balancePlatform,
            'balance_cash' => $balanceCash,
            'balance_pending' => $balancePending,
            'booking_fee' => round($bookingFee, 2),
            'gross' => $gross,
            'net' => round($net, 2),
        ];
    }

    /**
     * Gross amount the platform holds for the artist (deposit + platform-collected balance).
     * Excludes cash balance — the artist already received that directly from the client.
     */
    public function collectedGrossForArtist(Booking $booking): float
    {
        $deposit = max(0, round((float) ($booking->deposit_amount ?? 0), 2));

        return round($deposit + $this->platformCollectedBalanceAmount($booking), 2);
    }

    public function computeArtistPayoutAmount(Booking $booking, UserDetail $userDetail): float
    {
        $bookingFee = $this->pricing->resolveBookingFee($userDetail);
        $artistFee = (float) $bookingFee['artist_fee'];

        return max(0, round($this->collectedGrossForArtist($booking) - $artistFee, 2));
    }

    /**
     * Balance paid via payment link (or other platform settlement), not cash at the studio.
     */
    public function platformCollectedBalanceAmount(Booking $booking): float
    {
        $collections = $booking->relationLoaded('balanceCollections')
            ? $booking->balanceCollections
            : $booking->balanceCollections()->get();

        $total = 0.0;

        foreach ($collections as $collection) {
            if (! $this->isPlatformCollectedBalance($collection)) {
                continue;
            }

            $total += max(0, round((float) $collection->amount - (float) ($collection->platform_fee ?? 0), 2));
        }

        return round($total, 2);
    }

    private function isPlatformCollectedBalance(BalanceCollection $collection): bool
    {
        if ($collection->collection_type !== BalanceCollection::TYPE_PAYMENT_LINK) {
            return false;
        }

        return $collection->isPaid();
    }

    public function cashBalanceAmount(Booking $booking): float
    {
        $total = 0.0;

        foreach ($this->balanceCollectionsForBooking($booking) as $collection) {
            if ($collection->collection_type !== BalanceCollection::TYPE_PAID_IN_CASH || ! $collection->isPaid()) {
                continue;
            }

            $total += max(0, round((float) $collection->amount, 2));
        }

        return round($total, 2);
    }

    public function pendingBalanceAmount(Booking $booking): float
    {
        $total = 0.0;

        foreach ($this->balanceCollectionsForBooking($booking) as $collection) {
            if ($collection->isPaid()) {
                continue;
            }

            if ($collection->collection_type === BalanceCollection::TYPE_PAYMENT_LINK
                || $collection->collection_type === BalanceCollection::TYPE_NOT_SETTLED_YET) {
                $total += max(0, round((float) $collection->amount, 2));
            }
        }

        return round($total, 2);
    }

    /**
     * @return \Illuminate\Support\Collection<int, BalanceCollection>
     */
    private function balanceCollectionsForBooking(Booking $booking)
    {
        return $booking->relationLoaded('balanceCollections')
            ? $booking->balanceCollections
            : $booking->balanceCollections()->get();
    }

    public function hasPassedCancellationDeadline(Booking $booking): bool
    {
        $deadline = $this->cancellationDeadlineAt($booking);

        return $deadline !== null && now()->gte($deadline);
    }

    public function cancellationDeadlineAt(Booking $booking): ?Carbon
    {
        if ($booking->cancellation_deadline) {
            return $booking->cancellation_deadline->copy();
        }

        if (! $booking->booking_date || ! $booking->start_time_utc) {
            return null;
        }

        $bookingDateTime = Carbon::parse(
            $booking->booking_date->format('Y-m-d').' '.$booking->start_time_utc
        );
        $windowHours = $this->cancellationService->effectiveCancellationWindowHours($booking);

        return $bookingDateTime->copy()->subHours($windowHours);
    }

    /**
     * Pending payout details for a booking that is not yet withdrawable.
     *
     * @return array{
     *     amount: float,
     *     available_at: string|null,
     *     available_label: string,
     *     reason: string,
     *     sort_key: string
     * }|null
     */
    public function payoutPendingInfo(Booking $booking, UserDetail $userDetail): ?array
    {
        if (($booking->payment_status ?? '') !== 'paid' || $booking->pay_artist) {
            return null;
        }

        if ($this->isBookingPayoutEligible($booking)) {
            return null;
        }

        $amount = $this->remainingPayoutAmount($booking, $userDetail);
        if ($amount < 0.01) {
            return null;
        }

        $isCompleted = ($booking->status ?? '') === 'completed';
        $deadline = $this->cancellationDeadlineAt($booking);
        $cancellationPassed = $this->hasPassedCancellationDeadline($booking);
        $tz = $booking->timezone ?: config('app.timezone', 'UTC');

        if ($isCompleted && ! $cancellationPassed && $deadline) {
            $label = $deadline->copy()->timezone($tz)->format('M j, Y g:i A');

            return [
                'amount' => round($amount, 2),
                'available_at' => $deadline->toIso8601String(),
                'available_label' => $label,
                'reason' => 'Cancellation window ends',
                'sort_key' => $deadline->format('Y-m-d H:i:s'),
            ];
        }

        if (! $isCompleted && $cancellationPassed) {
            return [
                'amount' => round($amount, 2),
                'available_at' => null,
                'available_label' => 'After completion',
                'reason' => 'Mark booking as completed',
                'sort_key' => '9999-after-completion',
            ];
        }

        if (! $isCompleted && $deadline) {
            $label = $deadline->copy()->timezone($tz)->format('M j, Y g:i A');

            return [
                'amount' => round($amount, 2),
                'available_at' => $deadline->toIso8601String(),
                'available_label' => $label,
                'reason' => 'After booking is completed',
                'sort_key' => $deadline->format('Y-m-d H:i:s'),
            ];
        }

        return [
            'amount' => round($amount, 2),
            'available_at' => null,
            'available_label' => 'When eligible',
            'reason' => 'Not yet eligible for payout',
            'sort_key' => '9999-pending',
        ];
    }

    private function guardPlatformBalanceForTransfer(
        float $amount,
        string $currency,
        ?string $sourceChargeId,
        bool $allowManualRequest,
        Booking $booking,
        UserDetail $userDetail,
    ): void {
        if ($sourceChargeId !== null && $sourceChargeId !== '') {
            return;
        }

        if ($this->payoutAlert->hasSufficientPlatformBalance($amount, $currency)) {
            return;
        }

        $this->notifyLowPlatformBalance(
            amount: $amount,
            currency: $currency,
            source: $allowManualRequest ? 'manual_request' : 'automatic_payout',
            userDetail: $userDetail,
            booking: $booking,
        );

        if ($allowManualRequest) {
            throw ValidationException::withMessages([
                'amount' => ['Withdraw is unavailable right now. Please contact support.'],
            ]);
        }

        throw new InsufficientPlatformBalanceException('Platform Stripe balance too low for payout.');
    }

    private function notifyLowPlatformBalance(
        float $amount,
        string $currency,
        string $source,
        UserDetail $userDetail,
        ?Booking $booking = null,
    ): void {
        $available = $this->payoutAlert->platformAvailableAmount($currency) ?? 0.0;

        $this->payoutAlert->notifyInsufficientBalance([
            'source' => $source,
            'requested_amount' => $amount,
            'available_amount' => $available,
            'currency' => $currency,
            'artist_name' => $this->artistDisplayName($userDetail),
            'booking_id' => $booking?->id,
            'booking_reference' => $booking?->referenceLabel(),
        ]);
    }

    private function artistDisplayName(UserDetail $userDetail): string
    {
        $userDetail->loadMissing('user');
        $user = $userDetail->user;
        if (! $user) {
            return 'Artist #'.$userDetail->user_id;
        }

        $name = trim($user->first_name.' '.$user->last_name);

        return $name !== '' ? $name : 'Artist #'.$userDetail->user_id;
    }

    private function isInsufficientStripeBalanceError(ApiErrorException $e): bool
    {
        $code = strtolower((string) ($e->getStripeCode() ?? ''));

        return $code === 'balance_insufficient'
            || str_contains(strtolower($e->getMessage()), 'insufficient funds')
            || str_contains(strtolower($e->getMessage()), 'insufficient balance');
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
     * Whether the artist dashboard should show the global payout banner.
     * Uses local flags only (no live Stripe API) so it is safe on every page.
     */
    public function needsPayoutSetupBanner(?UserDetail $userDetail): bool
    {
        return $this->payoutDashboardNotice($userDetail) !== null;
    }

    /**
     * Global payout notice for the artist dashboard (setup vs Stripe restricted).
     *
     * @return array<string, mixed>|null
     */
    public function payoutDashboardNotice(?UserDetail $userDetail): ?array
    {
        if (! $this->needsPayoutSetupReminder($userDetail)) {
            return null;
        }

        if ($this->needsStripeRestrictedNotice($userDetail)) {
            $isStudioPayout = ($userDetail->payment_type ?? '') === 'studio_account';

            return [
                'id' => 'payoutRestrictedBanner',
                'theme' => 'red',
                'icon' => 'pause_circle',
                'title' => 'Restricted',
                'subtitle' => 'Payouts paused',
                'description' => $isStudioPayout
                    ? 'Stripe paused payouts because your studio account is missing required information. Ask your studio to add the missing details to start receiving payouts again.'
                    : 'Stripe paused your payouts because your account is missing required information. Add the missing details to start receiving payouts again.',
                'buttonText' => 'Fix Stripe account',
                'buttonIcon' => 'arrow_forward',
                'buttonUrl' => $isStudioPayout
                    ? route('settings.payment')
                    : route('settings.payment.stripe.requirements'),
            ];
        }

        return [
            'id' => 'payoutSetupBanner',
            'theme' => 'amber',
            'icon' => 'payments',
            'title' => 'Payouts not set up',
            'description' => 'You need to setup payouts to accept deposits for bookings.',
            'buttonText' => 'Setup payouts',
            'buttonIcon' => 'settings',
            'buttonUrl' => route('settings.payment'),
        ];
    }

    /**
     * Stripe is connected but still needs user-submittable information.
     */
    private function needsStripeRestrictedNotice(?UserDetail $userDetail): bool
    {
        if (! $userDetail) {
            return false;
        }

        $paymentType = (string) ($userDetail->payment_type ?? '');

        if ($paymentType === 'artist_account') {
            $hasStripeAccount = trim((string) ($userDetail->stripe_account_id ?? '')) !== '';

            return $hasStripeAccount && ! empty($userDetail->stripe_requirement);
        }

        if ($paymentType === 'studio_account') {
            if (($userDetail->payment_status ?? '') !== 'approved') {
                return false;
            }

            return ! empty($userDetail->stripe_requirement)
                || ! empty($userDetail->studio?->stripe_requirement);
        }

        return false;
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
