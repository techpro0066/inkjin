<?php

namespace App\Services;

use App\Mail\ArtistStripeRequirementMail;
use App\Models\Studio;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StripeRequirementSyncService
{
    public function __construct(
        private readonly StripeConnectService $stripeConnect,
    ) {}

    /**
     * Sync Stripe requirement flags for connected artist and studio accounts.
     *
     * @return array{checked: int, flagged: int, cleared: int, failed: int}
     */
    public function syncReadyArtistAccounts(): array
    {
        $stats = $this->emptyStats();

        if (! $this->stripeConnect->isConfigured()) {
            return $stats;
        }

        $this->syncArtistAccountDetails($stats);
        $this->syncStudioAccounts($stats);

        return $stats;
    }

    /**
     * @param  array{checked: int, flagged: int, cleared: int, failed: int}  $stats
     */
    private function syncArtistAccountDetails(array &$stats): void
    {
        UserDetail::query()
            ->where('payment_type', 'artist_account')
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->where(function ($query) {
                $query->where('payment_status', 'approved')
                    ->orWhere('stripe_requirement', true)
                    ->orWhereNull('payment_status')
                    ->orWhere('payment_status', 'pending');
            })
            ->orderBy('id')
            ->chunkById(50, function ($details) use (&$stats) {
                foreach ($details as $userDetail) {
                    $stats['checked']++;

                    try {
                        $result = $this->syncUserDetail($userDetail);
                        if ($result === 'flagged') {
                            $stats['flagged']++;
                        } elseif ($result === 'cleared') {
                            $stats['cleared']++;
                        }
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        Log::warning('Stripe requirement sync failed', [
                            'user_detail_id' => $userDetail->id,
                            'stripe_account_id' => $userDetail->stripe_account_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }

    /**
     * @param  array{checked: int, flagged: int, cleared: int, failed: int}  $stats
     */
    private function syncStudioAccounts(array &$stats): void
    {
        Studio::query()
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->orderBy('id')
            ->chunkById(50, function ($studios) use (&$stats) {
                foreach ($studios as $studio) {
                    $stats['checked']++;

                    try {
                        $result = $this->syncStudio($studio);
                        if ($result === 'flagged') {
                            $stats['flagged']++;
                        } elseif ($result === 'cleared') {
                            $stats['cleared']++;
                        }
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        Log::warning('Studio Stripe requirement sync failed', [
                            'studio_id' => $studio->id,
                            'stripe_account_id' => $studio->stripe_account_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }

    /**
     * @return 'flagged'|'cleared'|'unchanged'
     */
    public function syncStudio(Studio $studio): string
    {
        $accountId = trim((string) ($studio->resolveStripeAccountId() ?? ''));
        if ($accountId === '') {
            return 'unchanged';
        }

        if ($studio->stripe_account_id !== $accountId) {
            $studio->stripe_account_id = $accountId;
        }

        $status = $this->stripeConnect->getOnboardingStatus($accountId);
        $needsRequirement = $this->stripeConnect->accountNeedsUserAction($status);
        $wasFlagged = (bool) $studio->stripe_requirement;

        $studio->stripe_requirement = $needsRequirement;
        $studio->save();

        $this->propagateStudioRequirementToArtists($studio, $needsRequirement);

        if ($needsRequirement) {
            if (! $wasFlagged) {
                Log::info('Studio Stripe account requires user action', [
                    'studio_id' => $studio->id,
                    'stripe_account_id' => $accountId,
                    'currently_due' => $status['currently_due'] ?? [],
                    'disabled_reason' => $status['disabled_reason'] ?? null,
                ]);

                $this->notifyLinkedStudioArtists($studio);

                return 'flagged';
            }

            return 'unchanged';
        }

        if ($wasFlagged) {
            Log::info('Studio Stripe account requirements cleared', [
                'studio_id' => $studio->id,
                'stripe_account_id' => $accountId,
            ]);

            return 'cleared';
        }

        return 'unchanged';
    }

    private function propagateStudioRequirementToArtists(Studio $studio, bool $needsRequirement): void
    {
        $query = UserDetail::query()
            ->where('studio_id', $studio->id)
            ->where('payment_type', 'studio_account')
            ->where('payment_status', 'approved');

        if ($needsRequirement) {
            $query->update(['stripe_requirement' => true]);

            return;
        }

        $query->update([
            'stripe_requirement' => false,
            'stripe_requirement_email_sent_at' => null,
        ]);
    }

    private function notifyLinkedStudioArtists(Studio $studio): void
    {
        UserDetail::query()
            ->with('user')
            ->where('studio_id', $studio->id)
            ->where('payment_type', 'studio_account')
            ->where('payment_status', 'approved')
            ->where('stripe_requirement', true)
            ->whereNull('stripe_requirement_email_sent_at')
            ->orderBy('id')
            ->chunkById(50, function ($details) {
                foreach ($details as $userDetail) {
                    $this->sendRequirementEmail($userDetail, isStudioPayout: true);
                }
            });
    }

    /**
     * @return 'flagged'|'cleared'|'unchanged'
     */
    public function syncUserDetail(UserDetail $userDetail): string
    {
        if (($userDetail->payment_type ?? '') === 'studio_account') {
            $studio = $userDetail->studio;
            if (! $studio) {
                return 'unchanged';
            }

            return $this->syncStudio($studio);
        }

        $accountId = trim((string) ($userDetail->stripe_account_id ?? ''));
        if ($accountId === '') {
            return 'unchanged';
        }

        $status = $this->stripeConnect->getOnboardingStatus($accountId);
        $needsRequirement = $this->stripeConnect->accountNeedsUserAction($status);

        $wasFlagged = (bool) $userDetail->stripe_requirement;
        $previousPaymentStatus = (string) ($userDetail->payment_status ?? '');

        if ($needsRequirement) {
            $userDetail->stripe_requirement = true;
            if (($userDetail->payment_type ?? '') === 'artist_account') {
                $userDetail->payment_status = 'pending';
            }
            $userDetail->save();

            if (! $wasFlagged || $previousPaymentStatus === 'approved') {
                Log::info('Stripe account requires user action; payout marked pending', [
                    'user_detail_id' => $userDetail->id,
                    'stripe_account_id' => $accountId,
                    'currently_due' => $status['currently_due'] ?? [],
                    'disabled_reason' => $status['disabled_reason'] ?? null,
                ]);

                $this->sendRequirementEmail($userDetail, isStudioPayout: false);

                return 'flagged';
            }

            return 'unchanged';
        }

        $userDetail->stripe_requirement = false;
        $userDetail->stripe_requirement_email_sent_at = null;
        if (($userDetail->payment_type ?? '') === 'artist_account' && $previousPaymentStatus !== 'rejected') {
            $userDetail->payment_status = 'approved';
        }
        $userDetail->save();

        if ($wasFlagged) {
            Log::info('Stripe account requirements cleared; payout marked approved', [
                'user_detail_id' => $userDetail->id,
                'stripe_account_id' => $accountId,
            ]);

            return 'cleared';
        }

        return 'unchanged';
    }

    private function sendRequirementEmail(UserDetail $userDetail, bool $isStudioPayout): void
    {
        if ($userDetail->stripe_requirement_email_sent_at) {
            return;
        }

        $userDetail->loadMissing('user');
        $user = $userDetail->user;
        if (! $user instanceof User) {
            return;
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email === '') {
            return;
        }

        $firstName = trim((string) ($user->first_name ?? ''));
        if ($firstName === '') {
            $firstName = 'there';
        }

        $actionUrl = $isStudioPayout
            ? route('settings.payment')
            : route('settings.payment.stripe.requirements');

        try {
            Mail::to($email)->send(new ArtistStripeRequirementMail(
                $firstName,
                $actionUrl,
                $isStudioPayout,
            ));

            $userDetail->forceFill([
                'stripe_requirement_email_sent_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            Log::error('Failed to send Stripe requirement email to artist', [
                'user_id' => $user->id,
                'user_detail_id' => $userDetail->id,
                'is_studio_payout' => $isStudioPayout,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{checked: int, flagged: int, cleared: int, failed: int}
     */
    private function emptyStats(): array
    {
        return ['checked' => 0, 'flagged' => 0, 'cleared' => 0, 'failed' => 0];
    }
}
