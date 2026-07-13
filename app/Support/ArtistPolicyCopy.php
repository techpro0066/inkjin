<?php

namespace App\Support;

use App\Models\UserDetail;

class ArtistPolicyCopy
{
    /**
     * @return array{deposit: string, rescheduling: string, cancellation: string, no_show: string}
     */
    public static function for(?UserDetail $userDetail): array
    {
        $currencyCode = strtoupper(trim((string) ($userDetail?->currency ?? 'EUR')));
        $currencySymbol = match ($currencyCode) {
            'EUR' => '€',
            'GBP' => '£',
            'USD', 'CAD', 'AUD', 'NZD', 'SGD' => '$',
            default => $currencyCode !== '' ? $currencyCode.' ' : '€',
        };

        $depositType = strtolower((string) ($userDetail?->minimum_deposit_type ?? 'percentage'));
        $depositAmountRaw = (float) ($userDetail?->minimum_deposit_amount ?? 30);
        $depositAmountLabel = rtrim(rtrim(number_format(max(0, $depositAmountRaw), 2, '.', ''), '0'), '.');
        if ($depositType === 'amount') {
            $deposit = 'A '.$currencySymbol.$depositAmountLabel.' deposit is required to secure and confirm your appointment. The deposit goes toward the final cost of your tattoo.';
        } else {
            $deposit = 'A '.$depositAmountLabel.'% deposit on the value of your tattoo is required to secure and confirm your appointment. The deposit goes toward the final cost of your tattoo.';
        }

        $reschedulePolicy = strtolower((string) ($userDetail?->reschedule_times ?? 'never'));
        $rescheduling = match ($reschedulePolicy) {
            'once' => 'You can reschedule your appointment once.',
            'twice' => 'You can reschedule your appointment twice.',
            'unlimited' => 'You can reschedule your appointment an unlimited number of times.',
            default => 'You cannot reschedule your appointment. We have a strict no-reschedule policy.',
        };

        $cwRaw = strtolower(trim((string) ($userDetail?->cancellation_window ?? '48h')));
        $cancelWindowHuman = match (true) {
            str_contains($cwRaw, '2w') || $cwRaw === '2w' => '2 weeks',
            str_contains($cwRaw, '1w') || $cwRaw === '1w' || str_contains($cwRaw, 'w') => '1 week',
            str_contains($cwRaw, '72') => '72 hours',
            str_contains($cwRaw, '48') => '48 hours',
            str_contains($cwRaw, '24') => '24 hours',
            str_contains($cwRaw, '12') => '12 hours',
            default => '48 hours',
        };

        return [
            'deposit' => $deposit,
            'rescheduling' => $rescheduling,
            'cancellation' => "You'll receive a full refund if you cancel at least {$cancelWindowHuman} before your appointment.",
            'no_show' => 'Your deposit is forfeited in full. A new deposit will be required to book a future appointment.',
        ];
    }
}
