<?php

namespace App\Services;

use App\Mail\AppointmentReminder24hMail;
use App\Mail\AppointmentReminder72hMail;
use App\Models\Booking;
use App\Models\ConsentAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingAppointmentReminderService
{
    public function __construct(
        private BookingConsentService $consents
    ) {}

    /**
     * @return array{sent_72h: int, sent_24h: int, failed: int}
     */
    public function sendDue(?\DateTimeInterface $now = null): array
    {
        $now = \Carbon\Carbon::parse($now ?? now());
        $sent72 = 0;
        $sent24 = 0;
        $failed = 0;

        // 72h: within next 72h but still more than 24h away (avoid "in 3 days" copy for tomorrow).
        $sent72 += $this->sendWindow(
            hours: 72,
            minHoursExclusive: 24,
            column: 'reminder_72h_sent_at',
            now: $now,
            failed: $failed,
            kind: '72h'
        );

        $sent24 += $this->sendWindow(
            hours: 24,
            minHoursExclusive: 0,
            column: 'reminder_24h_sent_at',
            now: $now,
            failed: $failed,
            kind: '24h'
        );

        return [
            'sent_72h' => $sent72,
            'sent_24h' => $sent24,
            'failed' => $failed,
        ];
    }

    /**
     * @param  '72h'|'24h'  $kind
     */
    private function sendWindow(
        int $hours,
        float $minHoursExclusive,
        string $column,
        \Carbon\Carbon $now,
        int &$failed,
        string $kind
    ): int {
        $windowEnd = $now->copy()->addHours($hours);
        $sent = 0;

        $bookings = Booking::query()
            ->confirmed()
            ->whereNull($column)
            ->whereBetween('booking_date', [
                $now->toDateString(),
                $windowEnd->copy()->addDay()->toDateString(),
            ])
            ->with(['user', 'artist.userDetail'])
            ->orderBy('id')
            ->limit(200)
            ->get();

        foreach ($bookings as $booking) {
            $start = $booking->sessionStartUtc();
            if (! $start || $start->lte($now) || $start->gt($windowEnd)) {
                continue;
            }
            // e.g. 72h = within next 72 hours and still beyond minHoursExclusive.
            $hoursUntil = $now->diffInMinutes($start, false) / 60;
            if ($hoursUntil > $hours || $hoursUntil <= $minHoursExclusive) {
                continue;
            }

            $client = $booking->user;
            $email = trim((string) ($client?->email ?? ''));
            if (! $client || $email === '') {
                continue;
            }

            $claimed = false;
            try {
                $claimed = DB::transaction(function () use ($booking, $column) {
                    $locked = Booking::query()
                        ->whereKey($booking->id)
                        ->whereNull($column)
                        ->where('status', 'confirmed')
                        ->lockForUpdate()
                        ->first();
                    if (! $locked) {
                        return false;
                    }
                    $locked->update([$column => now()]);

                    return true;
                });
            } catch (\Throwable $e) {
                $failed++;
                Log::error("Failed to claim {$kind} appointment reminder", [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if (! $claimed) {
                continue;
            }

            try {
                $artist = $booking->artist;
                $detail = $artist?->userDetail;
                if ($detail && $artist) {
                    $detail->setRelation('user', $artist);
                }

                if ($kind === '72h') {
                    Mail::to($email)->send(new AppointmentReminder72hMail(
                        $booking,
                        $client,
                        $detail
                    ));
                } else {
                    $consentUrl = $this->consentUrlForReminder($booking);
                    Mail::to($email)->send(new AppointmentReminder24hMail(
                        $booking,
                        $client,
                        $detail,
                        $consentUrl
                    ));
                }

                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Booking::query()->whereKey($booking->id)->update([$column => null]);
                Log::error("Failed to send {$kind} appointment reminder", [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    private function consentUrlForReminder(Booking $booking): ?string
    {
        try {
            $consent = $this->consents->syncForBooking($booking);
            if (! $consent || $consent->isCompleted()) {
                return null;
            }

            if ($consent->isOpenForClient() && ! $consent->sent_at) {
                $consent->forceFill([
                    'sent_at' => now(),
                    'status' => ConsentAnswer::STATUS_SENT,
                ])->save();
            }

            return $consent->publicUrl();
        } catch (\Throwable $e) {
            Log::warning('Could not prepare consent URL for 24h reminder', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
