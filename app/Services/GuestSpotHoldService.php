<?php

namespace App\Services;

use App\Models\CustomRequest;
use App\Models\GuestSpot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuestSpotHoldService
{
    /**
     * Hold one guest spot until the artist response deadline.
     *
     * @throws \RuntimeException when capacity is exhausted
     */
    public function holdForQuote(CustomRequest $customRequest): void
    {
        if (! $customRequest->isGuestRequest()) {
            return;
        }

        $this->releaseExpiredHold($customRequest);

        $customRequest->loadMissing('guestSpot');
        $guestSpot = $customRequest->guestSpot;
        if (! $guestSpot) {
            throw new \RuntimeException('This guest spot is no longer available.');
        }

        $expiresAt = $this->expiresAtFromGuestSpot($guestSpot);

        DB::transaction(function () use ($customRequest, $guestSpot, $expiresAt) {
            /** @var CustomRequest $locked */
            $locked = CustomRequest::query()->whereKey($customRequest->id)->lockForUpdate()->firstOrFail();
            $spot = GuestSpot::query()->whereKey($guestSpot->id)->lockForUpdate()->first();
            if (! $spot) {
                throw new \RuntimeException('This guest spot is no longer available.');
            }

            if ($locked->guest_spot_held) {
                $locked->update([
                    'guest_hold_expires_at' => $expiresAt,
                ]);
                $customRequest->fill($locked->only(['guest_spot_held', 'guest_hold_expires_at']));

                return;
            }

            if ($spot->tracksSpotCapacity() && ! $spot->consumeRemainingSpot()) {
                throw new \RuntimeException('All slots are full. You cannot send a quote for this guest spot.');
            }

            $held = $spot->tracksSpotCapacity();
            $locked->update([
                'guest_spot_held' => $held,
                'guest_hold_expires_at' => $expiresAt,
            ]);
            $customRequest->fill([
                'guest_spot_held' => $held,
                'guest_hold_expires_at' => $expiresAt,
            ]);
        });
    }

    /**
     * Convert an active hold into a paid booking consumption (no second decrement).
     */
    public function convertHoldOnPayment(CustomRequest $customRequest): bool
    {
        if (! $customRequest->isGuestRequest()) {
            return false;
        }

        $this->releaseExpiredHold($customRequest);
        $customRequest->refresh();

        if ($customRequest->isGuestHoldExpired()) {
            throw new \RuntimeException('Your quote hold has expired. Please ask the artist to send a quote again.');
        }

        if ($customRequest->guest_spot_held) {
            $customRequest->update([
                'guest_spot_held' => false,
                'guest_hold_expires_at' => null,
            ]);

            return true;
        }

        $customRequest->loadMissing('guestSpot');
        $guestSpot = $customRequest->guestSpot;
        if (! $guestSpot) {
            throw new \RuntimeException('This guest spot is no longer available.');
        }

        if (! $guestSpot->tracksSpotCapacity()) {
            $customRequest->update(['guest_hold_expires_at' => null]);

            return false;
        }

        if (! $guestSpot->hasAvailableSpots()) {
            throw new \RuntimeException('All slots are full.');
        }

        if (! $guestSpot->consumeRemainingSpot()) {
            throw new \RuntimeException('All slots are full.');
        }

        $customRequest->update(['guest_hold_expires_at' => null]);

        return true;
    }

    public function releaseExpiredHold(CustomRequest $customRequest): bool
    {
        if (! $customRequest->isGuestRequest() || $customRequest->isBooked()) {
            return false;
        }

        if (! $customRequest->guest_hold_expires_at || $customRequest->guest_hold_expires_at->isFuture()) {
            return false;
        }

        return $this->releaseHold($customRequest, resetToPending: true);
    }

    public function releaseHold(CustomRequest $customRequest, bool $resetToPending = false): bool
    {
        if (! $customRequest->isGuestRequest() || $customRequest->isBooked()) {
            return false;
        }

        return DB::transaction(function () use ($customRequest, $resetToPending) {
            /** @var CustomRequest|null $locked */
            $locked = CustomRequest::query()->whereKey($customRequest->id)->lockForUpdate()->first();
            if (! $locked || $locked->isBooked()) {
                return false;
            }

            $wasHeld = (bool) $locked->guest_spot_held;
            if ($wasHeld && $locked->guest_id) {
                $spot = GuestSpot::query()->whereKey($locked->guest_id)->lockForUpdate()->first();
                $spot?->releaseRemainingSpot();
            }

            $update = [
                'guest_spot_held' => false,
                'guest_hold_expires_at' => null,
            ];

            if ($resetToPending && $locked->status === 'confirmed') {
                $update['status'] = 'pending';
            }

            $locked->update($update);
            $customRequest->fill($update);

            return $wasHeld || $resetToPending;
        });
    }

    public function expireDueHolds(): int
    {
        $expired = CustomRequest::query()
            ->where('is_guest', true)
            ->whereNotNull('guest_hold_expires_at')
            ->where('guest_hold_expires_at', '<=', now())
            ->whereNull('booking_id')
            ->whereIn('status', ['confirmed', 'pending'])
            ->orderBy('id')
            ->get();

        $count = 0;
        foreach ($expired as $request) {
            try {
                if ($this->releaseExpiredHold($request)) {
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::error('Failed to expire guest spot hold', [
                    'custom_request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    private function expiresAtFromGuestSpot(GuestSpot $guestSpot): Carbon
    {
        $value = max(1, (int) ($guestSpot->response_deadline ?? 1));
        $unit = strtolower((string) ($guestSpot->response_deadline_unit ?? 'hours'));

        if ($unit === 'days') {
            return now()->addDays($value);
        }

        return now()->addHours($value);
    }
}
