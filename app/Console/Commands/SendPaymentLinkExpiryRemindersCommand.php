<?php

namespace App\Console\Commands;

use App\Mail\PaymentLinkExpiryReminderMail;
use App\Models\PaymentLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentLinkExpiryRemindersCommand extends Command
{
    protected $signature = 'payment-links:send-expiry-reminders';

    protected $description = 'Email clients the day before an unpaid payment link expires, if they entered an email';

    public function handle(): int
    {
        $now = now();
        $sent = 0;
        $failed = 0;

        $links = PaymentLink::query()
            ->where('status', PaymentLink::STATUS_ACTIVE)
            ->whereNull('paid_at')
            ->whereNull('expiry_reminder_sent_at')
            ->whereNotNull('payer_email')
            ->where('payer_email', '!=', '')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $now->copy()->addHours(24))
            ->with(['artist.userDetail.user'])
            ->orderBy('id')
            ->get();

        foreach ($links as $link) {
            if ($link->isPaid() || $link->isExpired()) {
                continue;
            }

            $email = mb_strtolower(trim((string) $link->payer_email));
            $userDetail = $link->artist?->userDetail;
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $userDetail) {
                continue;
            }

            $claimed = false;
            try {
                $claimed = DB::transaction(function () use ($link) {
                    $locked = PaymentLink::query()
                        ->whereKey($link->id)
                        ->where('status', PaymentLink::STATUS_ACTIVE)
                        ->whereNull('paid_at')
                        ->whereNull('expiry_reminder_sent_at')
                        ->lockForUpdate()
                        ->first();
                    if (! $locked) {
                        return false;
                    }

                    $locked->update(['expiry_reminder_sent_at' => now()]);

                    return true;
                });
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Failed to claim payment-link expiry reminder', [
                    'payment_link_id' => $link->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if (! $claimed) {
                continue;
            }

            try {
                Mail::to($email)->send(new PaymentLinkExpiryReminderMail($link, $userDetail));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                PaymentLink::query()->whereKey($link->id)->update(['expiry_reminder_sent_at' => null]);
                Log::error('Failed to send payment-link expiry reminder', [
                    'payment_link_id' => $link->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info(sprintf('Payment-link expiry reminders: %d sent, %d failed.', $sent, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
