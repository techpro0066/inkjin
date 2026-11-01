<?php

namespace App\Services;

use App\Mail\ArtistStripeRequirementMail;
use App\Models\Studio;
use App\Models\StudioPayout;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserPayout;
use App\Models\UserStudio;
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
        $userIds = UserPayout::query()
            ->where('is_active', true)
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->where(function ($query) {
                $query->where('status', 'approved')
                    ->orWhere('stripe_requirement', true)
                    ->orWhere('status', 'draft')
                    ->orWhere('status', 'pending');
            })
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->values();

        UserDetail::query()
            ->whereIn('user_id', $userIds)
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
                            'stripe_account_id' => $userDetail->activePayout()?->stripe_account_id,
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
        $studioIds = StudioPayout::query()
            ->where('is_active', true)
            ->whereNotNull('stripe_account_id')
            ->where('stripe_account_id', '!=', '')
            ->orderBy('studio_id')
            ->pluck('studio_id')
            ->unique()
            ->values();

        foreach ($studioIds->chunk(50) as $chunk) {
            $studios = Studio::query()->whereIn('id', $chunk)->get();
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
                        'stripe_account_id' => $studio->resolveStripeAccountId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
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

        $payout = StudioPayout::activeForStudio((int) $studio->id);
        if (! $payout) {
            return 'unchanged';
        }

        $status = $this->stripeConnect->getOnboardingStatus($accountId);
        $needsRequirement = $this->stripeConnect->accountNeedsUserAction($status);
        $wasFlagged = (bool) $payout->stripe_requirement;

        $payout->stripe_requirement = $needsRequirement;
        if ($needsRequirement && ($payout->status ?? null) === StudioPayout::STATUS_APPROVED) {
            $payout->status = StudioPayout::STATUS_PENDING;
        } elseif (! $needsRequirement && filled($payout->stripe_account_id)) {
            $payout->status = StudioPayout::STATUS_APPROVED;
        }
        $payout->save();
        $studio->unsetRelation('activePayoutRelation');

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
        if (! UserStudio::tableReady()) {
            return;
        }

        $userIds = UserStudio::query()
            ->where('studio_id', $studio->id)
            ->where('payout', true)
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $payoutQuery = UserPayout::query()
            ->whereIn('user_id', $userIds)
            ->where('status', 'approved')
            ->where('is_active', true);

        if ($needsRequirement) {
            $payoutQuery->update(['stripe_requirement' => true]);

            return;
        }

        $payoutQuery->update([
            'stripe_requirement' => false,
            'stripe_requirement_email_sent_at' => null,
        ]);
    }

    private function notifyLinkedStudioArtists(Studio $studio): void
    {
        if (! UserStudio::tableReady()) {
            return;
        }

        $linkedUserIds = UserStudio::query()
            ->where('studio_id', $studio->id)
            ->where('payout', true)
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->values();

        if ($linkedUserIds->isEmpty()) {
            return;
        }

        $userIds = UserPayout::query()
            ->whereIn('user_id', $linkedUserIds)
            ->where('status', 'approved')
            ->where('is_active', true)
            ->where('stripe_requirement', true)
            ->whereNull('stripe_requirement_email_sent_at')
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->values();

        UserDetail::query()
            ->with('user')
            ->whereIn('user_id', $userIds)
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
        $payout = $userDetail->activePayout();
        $hasStudioPayout = UserStudio::tableReady()
            && UserStudio::payoutLinkForUser((int) $userDetail->user_id) !== null;

        // Studio-linked payouts: sync the studio Connect account when the artist has no own Stripe row.
        if ($hasStudioPayout && empty($payout?->stripe_account_id)) {
            $studio = $userDetail->studio
                ?? ($userDetail->resolvedStudioId()
                    ? Studio::query()->find($userDetail->resolvedStudioId())
                    : null);
            if (! $studio) {
                return 'unchanged';
            }

            return $this->syncStudio($studio);
        }

        $accountId = trim((string) ($payout?->stripe_account_id ?? ''));
        if ($accountId === '') {
            return 'unchanged';
        }

        $status = $this->stripeConnect->getOnboardingStatus($accountId);
        $needsRequirement = $this->stripeConnect->accountNeedsUserAction($status);

        $wasFlagged = (bool) ($payout?->stripe_requirement);
        $previousPaymentStatus = (string) ($payout?->status ?? '');

        if ($needsRequirement) {
            UserPayout::upsertActiveForUser((int) $userDetail->user_id, [
                'stripe_requirement' => true,
                'status' => 'pending',
            ]);

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

        $attrs = [
            'stripe_requirement' => false,
            'stripe_requirement_email_sent_at' => null,
        ];
        // Studio revenue-split invites stay pending until the studio accepts.
        // Do not mark approved from the artist's own Stripe becoming ready.
        if ($previousPaymentStatus !== 'rejected'
            && ! ($hasStudioPayout && $previousPaymentStatus !== 'approved')) {
            $attrs['status'] = 'approved';
        }
        UserPayout::upsertActiveForUser((int) $userDetail->user_id, $attrs);

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
        $payout = $userDetail->activePayout();
        if ($payout?->stripe_requirement_email_sent_at) {
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

            \App\Models\UserPayout::upsertActiveForUser((int) $userDetail->user_id, [
                'stripe_requirement_email_sent_at' => now(),
            ]);
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
