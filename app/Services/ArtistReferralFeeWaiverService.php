<?php

namespace App\Services;

use App\Models\ArtistReferral;
use App\Models\Booking;
use App\Models\UserDetail;
use Illuminate\Support\Facades\DB;

class ArtistReferralFeeWaiverService
{
    /**
     * Whether this artist's first paid booking should have InkJin fees waived.
     */
    public function eligible(UserDetail $userDetail): bool
    {
        $artistUserId = (int) $userDetail->user_id;
        if ($artistUserId < 1) {
            return false;
        }

        $hasPendingReferral = ArtistReferral::query()
            ->where('referred_user_id', $artistUserId)
            ->where('fee_waived', false)
            ->exists();

        if (! $hasPendingReferral) {
            return false;
        }

        return ! Booking::query()
            ->where('artist_user_id', $artistUserId)
            ->where('payment_status', 'paid')
            ->exists();
    }

    /**
     * @param  array{base_fee: float, fee_type: string, client_fee: float, artist_fee: float}  $bookingFee
     * @return array{base_fee: float, fee_type: string, client_fee: float, artist_fee: float, referral_fee_waived: bool}
     */
    public function waiveBookingFee(array $bookingFee): array
    {
        return [
            'base_fee' => 0.0,
            'fee_type' => (string) ($bookingFee['fee_type'] ?? 'client'),
            'client_fee' => 0.0,
            'artist_fee' => 0.0,
            'referral_fee_waived' => true,
        ];
    }

    public function wasFeeWaivedForBooking(Booking $booking): bool
    {
        return ArtistReferral::query()
            ->where('qualified_booking_id', $booking->id)
            ->where('fee_waived', true)
            ->exists();
    }

    /**
     * Artist fee deducted from payout for a specific booking (0 when referral waiver applied).
     */
    public function artistFeeForBooking(Booking $booking, UserDetail $userDetail): float
    {
        if ($this->wasFeeWaivedForBooking($booking)) {
            return 0.0;
        }

        return (float) app(BookingCheckoutPricingService::class)
            ->resolveBookingFeeWithoutReferralWaiver($userDetail)['artist_fee'];
    }

    /**
     * Mark the referral fee waiver as used on the artist's first paid booking.
     */
    public function consumeForPaidBooking(Booking $booking): void
    {
        if ((string) ($booking->payment_status ?? '') !== 'paid') {
            return;
        }

        $artistUserId = (int) $booking->artist_user_id;
        if ($artistUserId < 1) {
            return;
        }

        DB::transaction(function () use ($booking, $artistUserId): void {
            $referral = ArtistReferral::query()
                ->where('referred_user_id', $artistUserId)
                ->where('fee_waived', false)
                ->lockForUpdate()
                ->first();

            if (! $referral) {
                return;
            }

            $hasEarlierPaidBooking = Booking::query()
                ->where('artist_user_id', $artistUserId)
                ->where('payment_status', 'paid')
                ->whereKeyNot($booking->id)
                ->exists();

            if ($hasEarlierPaidBooking) {
                return;
            }

            $referral->update([
                'fee_waived' => true,
                'qualified_booking_id' => $booking->id,
                'qualified_at' => now(),
            ]);
        });
    }
}
