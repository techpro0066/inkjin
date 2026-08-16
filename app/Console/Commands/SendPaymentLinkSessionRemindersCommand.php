<?php

namespace App\Console\Commands;

use App\Mail\PaymentLinkSessionReminderMail;
use App\Models\Booking;
use App\Models\PaymentLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentLinkSessionRemindersCommand extends Command
{
    protected $signature = 'payment-links:send-session-reminders';

    protected $description = 'Email clients 48 hours before a paid payment-link session';

    public function handle(): int
    {
        $now = now();
        $windowEnd = $now->copy()->addHours(48);
        $sent = 0;
        $failed = 0;

        $bookings = Booking::query()
            ->confirmed()
            ->whereNull('reminder_sent_at')
            ->whereBetween('booking_date', [
                $now->toDateString(),
                $windowEnd->copy()->addDay()->toDateString(),
            ])
            ->whereHas('paymentLink', function ($query) {
                $query->where('status', PaymentLink::STATUS_PAID);
            })
            ->with(['user', 'artist.userDetail.user', 'paymentLink'])
            ->orderBy('id')
            ->get();

        foreach ($bookings as $booking) {
            $start = $booking->sessionStartUtc();
            if (! $start || $start->lte($now) || $start->gt($windowEnd)) {
                continue;
            }

            $client = $booking->user;
            $userDetail = $booking->artist?->userDetail;
            $link = $booking->paymentLink;
            $clientEmail = trim((string) ($client?->email ?? ''));

            if (! $client || $clientEmail === '' || ! $userDetail || ! $link) {
                continue;
            }

            $claimed = false;
            try {
                $claimed = DB::transaction(function () use ($booking) {
                    $locked = Booking::query()
                        ->whereKey($booking->id)
                        ->whereNull('reminder_sent_at')
                        ->lockForUpdate()
                        ->first();
                    if (! $locked) {
                        return false;
                    }

                    $locked->update(['reminder_sent_at' => now()]);

                    return true;
                });
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Failed to claim payment-link session reminder', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if (! $claimed) {
                continue;
            }

            try {
                $link->setRelation('booking', $booking);
                Mail::to($clientEmail)->send(new PaymentLinkSessionReminderMail(
                    $booking,
                    $client,
                    $userDetail,
                    $link,
                ));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Booking::query()->whereKey($booking->id)->update(['reminder_sent_at' => null]);
                Log::error('Failed to send payment-link session reminder', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info(sprintf('Payment-link session reminders: %d sent, %d failed.', $sent, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
