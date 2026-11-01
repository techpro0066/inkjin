<?php

namespace App\Console\Commands;

use App\Mail\PayoutSetupReminderMail;
use App\Models\User;
use App\Services\ArtistPayoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPayoutSetupRemindersCommand extends Command
{
    protected $signature = 'artists:send-payout-setup-reminders';

    protected $description = 'Email artists 3 days after signup if payouts are still not set up';

    public function handle(ArtistPayoutService $payoutService): int
    {
        $cutoff = now()->subDays(3);
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        User::query()
            ->where('role', 'artist')
            ->whereNotNull('email_verified_at')
            ->where('created_at', '<=', $cutoff)
            ->where(function ($query) {
                $query->whereDoesntHave('payouts')
                    ->orWhereHas('payouts', function ($payoutQuery) {
                        $payoutQuery->where('is_active', true)
                            ->whereNull('payout_setup_reminder_sent_at');
                    });
            })
            ->with(['userDetail.studio', 'payouts'])
            ->orderBy('id')
            ->chunkById(100, function ($artists) use ($payoutService, &$sent, &$failed, &$skipped) {
                foreach ($artists as $artist) {
                    $userDetail = $artist->userDetail;
                    if (! $userDetail) {
                        $skipped++;
                        continue;
                    }

                    if (! $payoutService->needsPayoutSetupReminder($userDetail)) {
                        $skipped++;
                        continue;
                    }

                    $email = trim((string) ($artist->email ?? ''));
                    if ($email === '') {
                        $skipped++;
                        continue;
                    }

                    $claimed = false;
                    try {
                        $claimed = DB::transaction(function () use ($userDetail, $artist) {
                            $locked = \App\Models\UserPayout::query()
                                ->where('user_id', $artist->id)
                                ->where('is_active', true)
                                ->whereNull('payout_setup_reminder_sent_at')
                                ->lockForUpdate()
                                ->first();

                            if ($locked) {
                                $locked->payout_setup_reminder_sent_at = now();
                                $locked->save();

                                return true;
                            }

                            // No payout row yet — create a draft marker so we don't re-email.
                            if (\App\Models\UserPayout::query()->where('user_id', $artist->id)->exists()) {
                                return false;
                            }

                            \App\Models\UserPayout::query()->create([
                                'user_id' => $artist->id,
                                'status' => 'draft',
                                'is_active' => true,
                                'payout_mode' => 'automatic',
                                'payout_setup_reminder_sent_at' => now(),
                            ]);

                            return true;
                        });
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::error('Failed to claim payout setup reminder', [
                            'user_id' => $artist->id,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }

                    if (! $claimed) {
                        $skipped++;
                        continue;
                    }

                    $firstName = trim((string) ($artist->first_name ?? ''));
                    if ($firstName === '') {
                        $firstName = 'there';
                    }

                    try {
                        Mail::to($email)->send(new PayoutSetupReminderMail(
                            $firstName,
                            route('settings.payment'),
                        ));
                        $sent++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $userDetail->forceFill(['payout_setup_reminder_sent_at' => null])->save();
                        Log::error('Failed to send payout setup reminder', [
                            'user_id' => $artist->id,
                            'email' => $email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info(sprintf(
            'Payout setup reminders: sent=%d skipped=%d failed=%d',
            $sent,
            $skipped,
            $failed,
        ));

        return self::SUCCESS;
    }
}
