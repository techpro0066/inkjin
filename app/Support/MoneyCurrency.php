<?php

namespace App\Support;

final class MoneyCurrency
{
    private const ZERO_DECIMAL_CURRENCIES = [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG',
        'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];

    private const THREE_DECIMAL_CURRENCIES = ['BHD', 'JOD', 'KWD', 'OMR', 'TND'];

    private const SYMBOLS = [
        'AUD' => 'A$',
        'CAD' => 'CA$',
        'CHF' => 'CHF ',
        'CNY' => 'CN¥',
        'CZK' => 'CZK ',
        'DKK' => 'DKK ',
        'EUR' => '€',
        'GBP' => '£',
        'HUF' => 'HUF ',
        'INR' => '₹',
        'JPY' => '¥',
        'NOK' => 'NOK ',
        'NZD' => 'NZ$',
        'PLN' => 'PLN ',
        'SEK' => 'SEK ',
        'SGD' => 'S$',
        'USD' => 'US$',
        'KRW' => '₩',
    ];

    public static function code(?string $currency): string
    {
        $code = strtoupper(trim((string) $currency));

        return preg_match('/^[A-Z]{3}$/D', $code) === 1 ? $code : 'EUR';
    }

    public static function decimalPlaces(?string $currency): int
    {
        $code = self::code($currency);

        if (in_array($code, self::ZERO_DECIMAL_CURRENCIES, true)) {
            return 0;
        }

        return in_array($code, self::THREE_DECIMAL_CURRENCIES, true) ? 3 : 2;
    }

    public static function round(float $amount, ?string $currency): float
    {
        return round($amount, self::decimalPlaces($currency));
    }

    public static function toStripeAmount(float $amount, ?string $currency): int
    {
        $places = self::decimalPlaces($currency);

        return (int) round(self::round($amount, $currency) * (10 ** $places));
    }

    public static function fromStripeAmount(int $amount, ?string $currency): float
    {
        return $amount / (10 ** self::decimalPlaces($currency));
    }

    public static function symbol(?string $currency): string
    {
        $code = self::code($currency);

        return self::SYMBOLS[$code] ?? $code.' ';
    }

    public static function format(float $amount, ?string $currency, bool $trimTrailingZeros = false): string
    {
        $code = self::code($currency);
        $places = self::decimalPlaces($code);
        $number = number_format(self::round($amount, $code), $places, '.', ',');

        if ($trimTrailingZeros && $places > 0) {
            $number = rtrim(rtrim($number, '0'), '.');
        }

        return self::symbol($code).$number;
    }
}
