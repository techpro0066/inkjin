<?php

namespace App\Services;

use App\Models\UserDetail;
use App\Support\EuVat;

class BookingCheckoutPricingService
{
    /**
     * @return array{base_fee: float, fee_type: string, client_fee: float, artist_fee: float}
     */
    public function resolveBookingFee(UserDetail $userDetail): array
    {
        $baseFee = 10.00;
        $feeType = (string) ($userDetail->booking_fee_type ?: 'client');
        if (! in_array($feeType, ['client', 'artist', 'split'], true)) {
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
     *     subtotal: float,
     *     tax_amount: float,
     *     tax_rate: float|null,
     *     tax_country: string|null,
     *     tax_label: string|null,
     *     total_due: float,
     *     deposit_meta: array,
     *     booking_fee: array
     * }
     */
    public function checkoutTotals(UserDetail $userDetail, float $tattooMinPrice, ?string $clientPhone = null): array
    {
        $depositMeta = $this->resolveDepositForTattoo($userDetail, $tattooMinPrice);
        $priced = $this->totalsForAmount($userDetail, (float) $depositMeta['deposit'], $clientPhone);
        $priced['deposit'] = $priced['base_amount'];
        $priced['deposit_meta'] = $depositMeta;

        return $priced;
    }

    /**
     * Booking fee + VAT on a fixed amount (deposit, payment-link charge, etc.).
     *
     * @return array{
     *     base_amount: float,
     *     deposit: float,
     *     platform_fee: float,
     *     subtotal: float,
     *     tax_amount: float,
     *     tax_rate: float|null,
     *     tax_country: string|null,
     *     tax_label: string|null,
     *     total_due: float,
     *     booking_fee: array
     * }
     */
    public function totalsForAmount(UserDetail $userDetail, float $baseAmount, ?string $clientPhone = null): array
    {
        $baseAmount = round(max(0, $baseAmount), 2);
        $bookingFee = $this->resolveBookingFee($userDetail);
        $platformFee = (float) $bookingFee['client_fee'];
        $subtotal = round($baseAmount + $platformFee, 2);

        $vat = EuVat::taxOnBookingFee($platformFee, $clientPhone);
        $taxAmount = (float) $vat['tax_amount'];
        $totalDue = round($subtotal + $taxAmount, 2);

        return [
            'base_amount' => $baseAmount,
            'deposit' => $baseAmount,
            'platform_fee' => $platformFee,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'tax_rate' => $vat['is_eu'] ? (float) $vat['rate'] : null,
            'tax_country' => $vat['country_code'],
            'tax_label' => $vat['label'],
            'total_due' => $totalDue,
            'booking_fee' => $bookingFee,
        ];
    }
}
