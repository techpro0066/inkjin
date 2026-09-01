<?php

namespace App\Services;

use App\Exceptions\InsufficientPlatformBalanceException;
use App\Mail\AdminArtistReferralRewardMail;
use App\Mail\ArtistReferralRewardReceivedMail;
use App\Mail\ArtistReferralRewardRejectedMail;
use App\Models\ArtistReferral;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\ApiErrorException;

class ArtistReferralRewardService
{
    public const REASON_BOOKING_COMPLETED = 'booking_completed';

    public const REASON_CANCELLATION_WINDOW_PASSED = 'cancellation_window_passed';

    public function __construct(
        private readonly ArtistPayoutService $artistPayoutService,
        private readonly PlatformPayoutAlertService $adminAlerts,
        private readonly StripeConnectService $stripeConnect,
    ) {}

    /**
     * Check pending referrals whose cancellation window has passed.
     *
     * @return array{processed: int, notified: int, skipped: int}
     */
    public function processPendingReferrals(): array
    {
        $stats = ['processed' => 0, 'notified' => 0, 'skipped' => 0];

        ArtistReferral::query()
            ->where('status', ArtistReferral::STATUS_PENDING)
            ->whereNotNull('qualified_booking_id')
            ->with(['qualifiedBooking', 'referrer.userDetail', 'referred.userDetail'])
            ->orderBy('id')
            ->chunkById(50, function ($referrals) use (&$stats): void {
                foreach ($referrals as $referral) {
                    $stats['processed']++;
                    if ($this->evaluateBooking($referral->qualifiedBooking, $referral)) {
                        $stats['notified']++;
                    } else {
                        $stats['skipped']++;
                    }
                }
            });

        return $stats;
    }

    public function evaluateBooking(?Booking $booking, ?ArtistReferral $referral = null): bool
    {
        if (! $booking instanceof Booking) {
            return false;
        }

        if ((string) ($booking->payment_status ?? '') !== 'paid') {
            return false;
        }

        if ((string) ($booking->status ?? '') === 'cancelled') {
            return false;
        }

        $referral ??= ArtistReferral::query()
            ->where('referred_user_id', $booking->artist_user_id)
            ->where('status', ArtistReferral::STATUS_PENDING)
            ->first();

        if (! $referral || ! $referral->qualified_booking_id) {
            return false;
        }

        if ((int) $referral->qualified_booking_id !== (int) $booking->id) {
            return false;
        }

        $reason = $this->resolveQualifyReason($booking);
        if ($reason === null) {
            return false;
        }

        return $this->notifyAdmin($referral, $booking, $reason);
    }

    public function resolveQualifyReason(Booking $booking): ?string
    {
        if ((string) ($booking->status ?? '') === 'completed') {
            return self::REASON_BOOKING_COMPLETED;
        }

        if ($this->artistPayoutService->hasPassedCancellationDeadline($booking)) {
            return self::REASON_CANCELLATION_WINDOW_PASSED;
        }

        return null;
    }

    public function notifyAdmin(ArtistReferral $referral, Booking $booking, string $reason): bool
    {
        $sent = false;
        $finalReason = $reason;
        $referralId = $referral->id;

        DB::transaction(function () use ($referralId, $booking, &$sent, &$finalReason): void {
            $locked = ArtistReferral::query()
                ->whereKey($referralId)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== ArtistReferral::STATUS_PENDING || $locked->admin_notified_at) {
                return;
            }

            if ((int) ($locked->qualified_booking_id ?? 0) !== (int) $booking->id) {
                return;
            }

            $currentReason = $this->resolveQualifyReason($booking);
            if ($currentReason === null) {
                return;
            }

            $locked->update([
                'status' => ArtistReferral::STATUS_SENT_TO_ADMIN,
                'qualified_at' => $locked->qualified_at ?? now(),
                'admin_notified_at' => now(),
            ]);

            $sent = true;
            $finalReason = $currentReason;
        });

        if (! $sent) {
            return false;
        }

        $referral = ArtistReferral::query()
            ->with(['referrer.userDetail', 'referred.userDetail'])
            ->find($referralId);

        if (! $referral) {
            return false;
        }

        $email = $this->adminAlerts->resolveAdminEmail();
        if ($email === null) {
            Log::warning('Artist referral reward qualified but no admin email is configured.', [
                'referral_id' => $referral->id,
                'booking_id' => $booking->id,
            ]);

            return true;
        }

        try {
            Mail::to($email)->send(new AdminArtistReferralRewardMail(
                referral: $referral,
                booking: $booking,
                qualifyReason: $finalReason,
                referralsUrl: route('admin.referrals.index'),
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send artist referral reward admin email', [
                'referral_id' => $referral->id,
                'booking_id' => $booking->id,
                'admin_email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    /**
     * Transfer the reward to the referrer's Stripe account, mark as rewarded, and email them.
     */
    public function sendRewardToReferrer(ArtistReferral $referral): ArtistReferral
    {
        $context = DB::transaction(function () use ($referral): array {
            $locked = ArtistReferral::query()
                ->whereKey($referral->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw new \RuntimeException('Referral not found.');
            }

            if ($locked->status === ArtistReferral::STATUS_REWARDED) {
                throw new \RuntimeException('This referral reward has already been sent.');
            }

            if ($locked->status !== ArtistReferral::STATUS_SENT_TO_ADMIN) {
                throw new \RuntimeException('This referral is not ready to be rewarded yet.');
            }

            if ($locked->stripe_transfer_id) {
                throw new \RuntimeException('A Stripe transfer is already recorded for this referral.');
            }

            $locked->loadMissing('referrer.userDetail');
            $userDetail = $locked->referrer?->userDetail;
            if (! $userDetail) {
                throw new \RuntimeException('Referring artist profile not found.');
            }

            if (! $this->artistPayoutService->isArtistPaymentReady($userDetail)) {
                throw new \RuntimeException('The referring artist must have a connected Stripe account ready for payouts before you can send this reward.');
            }

            $connectedAccountId = $this->artistPayoutService->resolveConnectedAccountId($userDetail);
            if (! $connectedAccountId) {
                throw new \RuntimeException('The referring artist does not have a valid Stripe connected account.');
            }

            $amount = round((float) ($locked->reward_amount ?: 20), 2);
            $currency = 'EUR';
            $amountCents = (int) round($amount * 100);

            if ($amountCents < 1) {
                throw new \RuntimeException('Reward amount must be at least €0.01.');
            }

            $this->guardPlatformBalanceForReferralReward($amount, $currency, $userDetail, $locked);

            return [
                'referral' => $locked,
                'userDetail' => $userDetail,
                'connectedAccountId' => $connectedAccountId,
                'amount' => $amount,
                'amountCents' => $amountCents,
                'currency' => $currency,
            ];
        });

        /** @var ArtistReferral $lockedReferral */
        $lockedReferral = $context['referral'];
        $userDetail = $context['userDetail'];
        $connectedAccountId = $context['connectedAccountId'];
        $amount = $context['amount'];
        $amountCents = $context['amountCents'];
        $currency = $context['currency'];

        try {
            $transfer = $this->stripeConnect->transferToConnectedAccount(
                destinationAccountId: $connectedAccountId,
                amountCents: $amountCents,
                currency: $currency,
                sourceChargeId: null,
                metadata: [
                    'referral_id' => (string) $lockedReferral->id,
                    'referrer_user_id' => (string) $lockedReferral->referrer_user_id,
                    'referred_user_id' => (string) $lockedReferral->referred_user_id,
                    'qualified_booking_id' => (string) ($lockedReferral->qualified_booking_id ?? ''),
                    'reward_type' => 'artist_referral',
                ],
                idempotencyKey: 'artist_referral_reward_'.$lockedReferral->id,
            );
        } catch (ApiErrorException $e) {
            if ($this->isInsufficientStripeBalanceError($e)) {
                $this->notifyLowPlatformBalance($amount, $currency, $userDetail, $lockedReferral);

                throw new InsufficientPlatformBalanceException('Platform Stripe balance is too low to send this reward.');
            }

            Log::error('Stripe referral reward transfer failed', [
                'referral_id' => $lockedReferral->id,
                'referrer_user_id' => $lockedReferral->referrer_user_id,
                'amount' => $amount,
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Stripe transfer failed. Please try again or contact support.');
        }

        $referral = DB::transaction(function () use ($lockedReferral, $transfer): ArtistReferral {
            $locked = ArtistReferral::query()
                ->whereKey($lockedReferral->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw new \RuntimeException('Referral not found.');
            }

            if ($locked->status === ArtistReferral::STATUS_REWARDED) {
                return $locked->fresh(['referrer.userDetail', 'referred.userDetail', 'qualifiedBooking']);
            }

            if ($locked->status !== ArtistReferral::STATUS_SENT_TO_ADMIN) {
                throw new \RuntimeException('This referral is not ready to be rewarded yet.');
            }

            if ($locked->stripe_transfer_id && $locked->stripe_transfer_id !== $transfer['id']) {
                throw new \RuntimeException('A different Stripe transfer is already recorded for this referral.');
            }

            $locked->update([
                'status' => ArtistReferral::STATUS_REWARDED,
                'reward_paid_at' => now(),
                'stripe_transfer_id' => $transfer['id'],
                'stripe_account_id' => $transfer['destination'],
                'reward_currency' => $transfer['currency'],
            ]);

            return $locked->fresh(['referrer.userDetail', 'referred.userDetail', 'qualifiedBooking']);
        });

        $referrerEmail = trim((string) ($referral->referrer?->email ?? ''));
        if ($referrerEmail === '') {
            Log::warning('Referral reward transferred but referrer has no email.', [
                'referral_id' => $referral->id,
                'referrer_user_id' => $referral->referrer_user_id,
                'stripe_transfer_id' => $referral->stripe_transfer_id,
            ]);

            return $referral;
        }

        try {
            Mail::to($referrerEmail)->send(new ArtistReferralRewardReceivedMail(
                referral: $referral,
                referEarnUrl: route('artist.refer-earn.index'),
            ));
        } catch (\Throwable $e) {
            Log::error('Referral reward transferred but artist notification email failed', [
                'referral_id' => $referral->id,
                'referrer_user_id' => $referral->referrer_user_id,
                'stripe_transfer_id' => $referral->stripe_transfer_id,
                'error' => $e->getMessage(),
            ]);
        }

        return $referral;
    }

    private function guardPlatformBalanceForReferralReward(
        float $amount,
        string $currency,
        UserDetail $userDetail,
        ArtistReferral $referral,
    ): void {
        if ($this->adminAlerts->hasSufficientPlatformBalance($amount, $currency)) {
            return;
        }

        $this->notifyLowPlatformBalance($amount, $currency, $userDetail, $referral);

        throw new InsufficientPlatformBalanceException('Platform Stripe balance is too low to send this reward.');
    }

    private function notifyLowPlatformBalance(
        float $amount,
        string $currency,
        UserDetail $userDetail,
        ArtistReferral $referral,
    ): void {
        $available = $this->adminAlerts->platformAvailableAmount($currency) ?? 0.0;
        $userDetail->loadMissing('user');
        $user = $userDetail->user;
        $artistName = 'Artist #'.$userDetail->user_id;
        if ($user) {
            $name = trim($user->first_name.' '.$user->last_name);
            if ($name !== '') {
                $artistName = $name;
            }
        }

        $this->adminAlerts->notifyInsufficientBalance([
            'source' => 'referral_reward',
            'requested_amount' => $amount,
            'available_amount' => $available,
            'currency' => $currency,
            'artist_name' => $artistName,
            'booking_id' => $referral->qualified_booking_id,
            'booking_reference' => $referral->qualifiedBooking?->referenceLabel(),
        ]);
    }

    private function isInsufficientStripeBalanceError(ApiErrorException $e): bool
    {
        $code = strtolower((string) ($e->getStripeCode() ?? ''));

        return $code === 'balance_insufficient'
            || str_contains(strtolower($e->getMessage()), 'insufficient funds')
            || str_contains(strtolower($e->getMessage()), 'insufficient balance');
    }

    /**
     * Reject a referral reward, store the admin reason, and email the referring artist.
     */
    public function rejectReferral(ArtistReferral $referral, string $reason): ArtistReferral
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \RuntimeException('A rejection reason is required.');
        }

        $referral = DB::transaction(function () use ($referral, $reason): ArtistReferral {
            $locked = ArtistReferral::query()
                ->whereKey($referral->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw new \RuntimeException('Referral not found.');
            }

            if ($locked->status === ArtistReferral::STATUS_REJECTED) {
                throw new \RuntimeException('This referral has already been rejected.');
            }

            if ($locked->status === ArtistReferral::STATUS_REWARDED) {
                throw new \RuntimeException('This referral has already been rewarded and cannot be rejected.');
            }

            if ($locked->status !== ArtistReferral::STATUS_SENT_TO_ADMIN) {
                throw new \RuntimeException('This referral is not ready to be rejected yet.');
            }

            $locked->update([
                'status' => ArtistReferral::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'rejected_at' => now(),
            ]);

            return $locked->fresh(['referrer.userDetail', 'referred.userDetail', 'qualifiedBooking']);
        });

        $referrerEmail = trim((string) ($referral->referrer?->email ?? ''));
        if ($referrerEmail === '') {
            Log::warning('Referral rejected but referrer has no email.', [
                'referral_id' => $referral->id,
                'referrer_user_id' => $referral->referrer_user_id,
            ]);

            return $referral;
        }

        try {
            Mail::to($referrerEmail)->send(new ArtistReferralRewardRejectedMail(
                referral: $referral,
                referEarnUrl: route('artist.refer-earn.index'),
            ));
        } catch (\Throwable $e) {
            Log::error('Referral rejected but artist notification email failed', [
                'referral_id' => $referral->id,
                'referrer_user_id' => $referral->referrer_user_id,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Referral was rejected but the artist notification email could not be sent.');
        }

        return $referral;
    }

    public static function qualifyReasonLabel(?string $reason): string
    {
        return match ($reason) {
            self::REASON_BOOKING_COMPLETED => 'Booking completed',
            self::REASON_CANCELLATION_WINDOW_PASSED => 'Cancellation window passed',
            default => 'Qualified',
        };
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            ArtistReferral::STATUS_PENDING => 'Pending',
            ArtistReferral::STATUS_SENT_TO_ADMIN => 'Wait for approval',
            ArtistReferral::STATUS_REWARDED => 'Rewarded',
            ArtistReferral::STATUS_REJECTED => 'Rejected',
            default => ucfirst(str_replace('_', ' ', (string) $status)),
        };
    }

    /**
     * Status label shown to referring artists (hides internal admin workflow).
     */
    public static function artistDashboardStatusLabel(?string $status): string
    {
        return match ($status) {
            ArtistReferral::STATUS_REWARDED => 'Rewarded',
            ArtistReferral::STATUS_REJECTED => 'Rejected',
            default => 'Pending',
        };
    }

    /**
     * Status key for artist dashboard styling.
     */
    public static function artistDashboardStatusKey(?string $status): string
    {
        return match ($status) {
            ArtistReferral::STATUS_REWARDED => ArtistReferral::STATUS_REWARDED,
            ArtistReferral::STATUS_REJECTED => ArtistReferral::STATUS_REJECTED,
            default => ArtistReferral::STATUS_PENDING,
        };
    }

    public static function displayName(?User $user): string
    {
        if (! $user) {
            return '—';
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        $username = trim((string) ($user->userDetail?->user_name ?? ''));

        if ($name !== '') {
            return $name;
        }

        if ($username !== '') {
            return '@'.$username;
        }

        return (string) ($user->email ?? 'User #'.$user->id);
    }
}
