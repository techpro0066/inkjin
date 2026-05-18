<?php

namespace App\Services;

use App\Models\UserDetail;

class BookingCheckoutPricingService
{
    /**
     * @return array{base_fee: float, fee_type: string, client_fee: float, artist_fee: float}
     */
    public function resolveBookingFee(UserDetail $userDetail): array
    {
        $baseFee = 10.00;
        $feeType = (string) ($userDetail->booking_fee_type ?: 'client');
        if (!in_array($feeType, ['client', 'artist', 'split'], true)) {
            $feeType = 'client';
        }

        $clientFee = $baseFee;
        if ($feeType === 'artist') {
            $clientFee = 0.00;
        } elseif ($feeType === 'split') {
            $clientFee = $baseFee / 2;
        }

        $artistFee = max(0, $baseFee - $clientFee);

        return [
            'base_fee' => $baseFee,
            'fee_type' => $feeType,
            'client_fee' => round($clientFee, 2),
            'artist_fee' => round($artistFee, 2),
        ];
    }

    /**
     * @return array{deposit: float, type: string, amount: float, label: string}
     */
    public function resolveDepositForTattoo(UserDetail $userDetail, float $tattooMinPrice): array
    {
        $type = (string) ($userDetail->minimum_deposit_type ?: 'percentage');
        $amount = (float) ($userDetail->minimum_deposit_amount ?? 30);

        if ($type === 'amount') {
            $deposit = min($tattooMinPrice, max(0, $amount));
            $label = 'fixed';
        } else {
            $type = 'percentage';
            $amount = max(0, $amount);
            $deposit = $tattooMinPrice * ($amount / 100);
            $label = rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.').'%';
        }

        return [
            'deposit' => round($deposit, 2),
            'type' => $type,
            'amount' => $amount,
            'label' => $label,
        ];
    }

    /**
     * @return array{
     *     deposit: float,
     *     platform_fee: float,
     *     total_due: float,
     *     deposit_meta: array,
     *     booking_fee: array
     * }
     */
    public function checkoutTotals(UserDetail $userDetail, float $tattooMinPrice): array
    {
        $depositMeta = $this->resolveDepositForTattoo($userDetail, $tattooMinPrice);
        $bookingFee = $this->resolveBookingFee($userDetail);
        $deposit = (float) $depositMeta['deposit'];
        $platformFee = (float) $bookingFee['client_fee'];
        $totalDue = $deposit + $platformFee;

        return [
            'deposit' => $deposit,
            'platform_fee' => $platformFee,
            'total_due' => round($totalDue, 2),
            'deposit_meta' => $depositMeta,
            'booking_fee' => $bookingFee,
        ];
    }
}
