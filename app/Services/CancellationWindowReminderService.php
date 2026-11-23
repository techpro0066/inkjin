<?php

namespace App\Services;

use App\Mail\CancellationWindowEndingMail;
use App\Models\Booking;
use App\Models\UserDetail;
use App\Support\MoneyCurrency;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class CancellationWindowReminderService
{
    public function __construct(private CancellationService $cancellations) {}

    /**
     * @return array{sent: int, failed: int}
     */
    public function sendDue(?\DateTimeInterface $now = null): array
    {
        $now = Carbon::parse($now ?? now());
        $sent = 0;
        $failed = 0;

        $bookings = Booking::query()
            ->confirmed()
            ->whereNull('cancellation_window_reminder_sent_at')
            ->with(['user', 'artist.userDetail'])
            ->orderBy('id')
            ->limit(200)
            ->get();

        foreach ($bookings as $booking) {
            $start = $booking->sessionStartUtc();
            if (! $start) {
                continue;
            }

            $windowHours = $this->cancellations->effectiveCancellationWindowHours($booking);
            $deadline = $start->copy()->subHours($windowHours);
            $reminderTime = $deadline->copy()->subHours(48);

            if ($deadline->lte($now) || $reminderTime->gt($now)) {
                continue;
            }

            $createdAt = $booking->created_at;
            if ($createdAt && $reminderTime->lte($createdAt)) {
                continue;
            }

            $client = $booking->user;
            $artist = $booking->artist;
            $detail = $artist?->userDetail;
            $email = trim((string) ($client?->email ?? ''));
            if (! $client || ! $artist || ! $detail || $email === '') {
                continue;
            }

            $claimed = false;
            try {
                $claimed = DB::transaction(function () use ($booking) {
                    $locked = Booking::query()
                        ->whereKey($booking->id)
                        ->whereNull('cancellation_window_reminder_sent_at')
                        ->where('status', 'confirmed')
                        ->lockForUpdate()
                        ->first();
                    if (! $locked) {
                        return false;
                    }
                    $locked->update(['cancellation_window_reminder_sent_at' => now()]);

                    return true;
                });
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Failed to claim cancellation-window reminder', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if (! $claimed) {
                continue;
            }

            try {
                $detail->setRelation('user', $artist);
                Mail::to($email)->send(new CancellationWindowEndingMail(
                    $booking,
                    $client,
                    $detail,
                    $this->emailData($booking, $client, $detail, $deadline),
                ));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Booking::query()->whereKey($booking->id)
                    ->update(['cancellation_window_reminder_sent_at' => null]);
                Log::error('Failed to send cancellation-window reminder', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    public function cancellationDeadline(Booking $booking): ?\Carbon\Carbon
    {
        $start = $booking->sessionStartUtc();
        if (! $start) {
            return null;
        }

        return $start->copy()->subHours(
            $this->cancellations->effectiveCancellationWindowHours($booking)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function emailData(
        Booking $booking,
        \App\Models\User $client,
        UserDetail $detail,
        Carbon $deadline,
    ): array {
        $timezone = trim((string) ($booking->timezone ?: $detail->timezone ?: 'UTC')) ?: 'UTC';
        $start = $booking->sessionStartUtc()?->copy()->timezone($timezone);
        $end = null;
        if ($start && $booking->end_time_utc) {
            $endTime = (string) $booking->end_time_utc;
            if (preg_match('/^\d{1,2}:\d{2}$/', $endTime)) {
                $endTime .= ':00';
            }
            try {
                $end = Carbon::parse($start->format('Y-m-d').' '.$endTime, 'UTC')
                    ->timezone($timezone);
            } catch (\Throwable) {
                $end = null;
            }
        }
        $deadlineLocal = $deadline->copy()->timezone($timezone);

        $artistName = trim((string) $detail->publicDisplayName()) ?: 'your artist';
        $artistFirst = trim((string) ($booking->artist?->first_name ?? '')) ?: $artistName;
        $clientFirst = trim((string) ($client->first_name ?? '')) ?: 'there';
        $handle = trim((string) ($detail->user_name ?? ''));
        if ($handle !== '' && ! str_starts_with($handle, '@')) {
            $handle = '@'.$handle;
        }

        $studio = trim((string) ($detail->studio_name ?? ''));
        $address = implode(', ', $detail->studioLocationLines());
        $location = implode(', ', array_values(array_filter([$studio, $address], fn (string $part) => $part !== '')));

        $avatarPath = trim((string) ($detail->avatar ?? ''));
        $avatarUrl = $avatarPath === ''
            ? null
            : (str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')
                ? $avatarPath
                : asset(ltrim($avatarPath, '/')));

        $deposit = (float) ($booking->deposit_amount ?? 0);
        $balance = $booking->remainingBalanceAmount();
        $currency = $booking->currency ?: $detail->currency;
        $showReschedule = $this->canRescheduleFromPolicy($booking, $detail);

        $viewBookingUrl = URL::temporarySignedRoute(
            'user.post-booking.access',
            now()->addDays(14),
            ['user' => $client->id, 'booking' => $booking->id],
        );

        return [
            'clientFirstName' => $clientFirst,
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistInitials' => $detail->publicDisplayInitials(),
            'artistAvatarUrl' => $avatarUrl,
            'artistMeta' => implode(' · ', array_values(array_filter([
                $handle,
                $studio !== '' ? $studio : null,
            ]))),
            'deadlineLabel' => $deadlineLocal->format('D j M \a\t H:i'),
            'deadlineDateLabel' => $deadlineLocal->format('D j/m/Y').' · '.$deadlineLocal->format('H:i'),
            'sessionLabel' => $start
                ? $start->format('D j/m/Y').' · '.$start->format('H:i').' to '.($end?->timezone($timezone)->format('H:i') ?: '--:--')
                : 'Session details',
            'location' => $location,
            'depositLabel' => MoneyCurrency::format($deposit, $currency),
            'showBalance' => $balance > 0,
            'balanceLabel' => MoneyCurrency::format($balance, $currency),
            'showReschedule' => $showReschedule,
            'viewBookingUrl' => $viewBookingUrl,
        ];
    }

    private function canRescheduleFromPolicy(Booking $booking, UserDetail $detail): bool
    {
        $policy = strtolower((string) ($detail->reschedule_times ?? 'never'));
        if ($policy === 'never') {
            return false;
        }

        $count = (int) ($booking->reschedule_count ?? 0);
        if ($policy === 'once') {
            return $count < 1;
        }
        if ($policy === 'twice') {
            return $count < 2;
        }

        return $policy === 'unlimited';
    }
}
