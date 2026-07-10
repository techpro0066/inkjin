<?php

namespace App\Support;

/**
 * EU VAT on Inkjin booking fee only (deposit is untaxed).
 * Country is inferred from the client's phone country calling code.
 */
class EuVat
{
    /**
     * Standard VAT rates (%) for EU member states.
     *
     * @var array<string, array{name: string, rate: float}>
     */
    private const COUNTRIES = [
        'AT' => ['name' => 'Austria', 'rate' => 20.0],
        'BE' => ['name' => 'Belgium', 'rate' => 21.0],
        'BG' => ['name' => 'Bulgaria', 'rate' => 20.0],
        'HR' => ['name' => 'Croatia', 'rate' => 25.0],
        'CY' => ['name' => 'Cyprus', 'rate' => 19.0],
        'CZ' => ['name' => 'Czechia', 'rate' => 21.0],
        'DK' => ['name' => 'Denmark', 'rate' => 25.0],
        'EE' => ['name' => 'Estonia', 'rate' => 22.0],
        'FI' => ['name' => 'Finland', 'rate' => 25.5],
        'FR' => ['name' => 'France', 'rate' => 20.0],
        'DE' => ['name' => 'Germany', 'rate' => 19.0],
        'GR' => ['name' => 'Greece', 'rate' => 24.0],
        'HU' => ['name' => 'Hungary', 'rate' => 27.0],
        'IE' => ['name' => 'Ireland', 'rate' => 23.0],
        'IT' => ['name' => 'Italy', 'rate' => 22.0],
        'LV' => ['name' => 'Latvia', 'rate' => 21.0],
        'LT' => ['name' => 'Lithuania', 'rate' => 21.0],
        'LU' => ['name' => 'Luxembourg', 'rate' => 17.0],
        'MT' => ['name' => 'Malta', 'rate' => 18.0],
        'NL' => ['name' => 'Netherlands', 'rate' => 21.0],
        'PL' => ['name' => 'Poland', 'rate' => 23.0],
        'PT' => ['name' => 'Portugal', 'rate' => 23.0],
        'RO' => ['name' => 'Romania', 'rate' => 19.0],
        'SK' => ['name' => 'Slovakia', 'rate' => 23.0],
        'SI' => ['name' => 'Slovenia', 'rate' => 22.0],
        'ES' => ['name' => 'Spain', 'rate' => 21.0],
        'SE' => ['name' => 'Sweden', 'rate' => 25.0],
    ];

    /**
     * Longest-prefix dialing codes → ISO country (EU only).
     *
     * @var array<string, string>
     */
    private const DIAL_CODES = [
        '359' => 'BG',
        '358' => 'FI',
        '357' => 'CY',
        '356' => 'MT',
        '353' => 'IE',
        '352' => 'LU',
        '370' => 'LT',
        '371' => 'LV',
        '372' => 'EE',
        '385' => 'HR',
        '386' => 'SI',
        '420' => 'CZ',
        '421' => 'SK',
        '30' => 'GR',
        '31' => 'NL',
        '32' => 'BE',
        '33' => 'FR',
        '34' => 'ES',
        '36' => 'HU',
        '39' => 'IT',
        '40' => 'RO',
        '43' => 'AT',
        '45' => 'DK',
        '46' => 'SE',
        '48' => 'PL',
        '49' => 'DE',
        '351' => 'PT',
    ];

    /**
     * @return array{
     *     is_eu: bool,
     *     country_code: string|null,
     *     country_name: string|null,
     *     rate: float,
     *     label: string|null
     * }
     */
    public static function resolveFromPhone(?string $phone): array
    {
        $countryCode = self::countryCodeFromPhone($phone);

        if ($countryCode === null || ! isset(self::COUNTRIES[$countryCode])) {
            return [
                'is_eu' => false,
                'country_code' => $countryCode,
                'country_name' => null,
                'rate' => 0.0,
                'label' => null,
            ];
        }

        $meta = self::COUNTRIES[$countryCode];
        $rate = (float) $meta['rate'];
        $rateLabel = fmod($rate, 1.0) === 0.0
            ? (string) (int) $rate
            : rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');

        return [
            'is_eu' => true,
            'country_code' => $countryCode,
            'country_name' => $meta['name'],
            'rate' => $rate,
            'label' => $meta['name'].' VAT ('.$rateLabel.'%)',
        ];
    }

    /**
     * Tax only the booking fee (client-facing platform fee).
     *
     * @return array{
     *     is_eu: bool,
     *     country_code: string|null,
     *     country_name: string|null,
     *     rate: float,
     *     label: string|null,
     *     taxable_amount: float,
     *     tax_amount: float
     * }
     */
    public static function taxOnBookingFee(float $bookingFee, ?string $phone): array
    {
        $resolved = self::resolveFromPhone($phone);
        $taxable = max(0, round($bookingFee, 2));
        $taxAmount = 0.0;

        if ($resolved['is_eu'] && $taxable > 0 && $resolved['rate'] > 0) {
            $taxAmount = round($taxable * ($resolved['rate'] / 100), 2);
        }

        return array_merge($resolved, [
            'taxable_amount' => $taxable,
            'tax_amount' => $taxAmount,
        ]);
    }

    public static function countryCodeFromPhone(?string $phone): ?string
    {
        $normalized = PaymentMethods::normalizePhone($phone);
        if ($normalized === '') {
            return null;
        }

        if (! str_starts_with($normalized, '+')) {
            // Bare national numbers without + are ambiguous — no VAT guess.
            return null;
        }

        $digits = substr($normalized, 1);
        if ($digits === '' || ! preg_match('/^\d+$/', $digits)) {
            return null;
        }

        // Longest prefix first (3-digit, then 2-digit).
        foreach ([3, 2] as $len) {
            if (strlen($digits) < $len) {
                continue;
            }
            $prefix = substr($digits, 0, $len);
            if (isset(self::DIAL_CODES[$prefix])) {
                return self::DIAL_CODES[$prefix];
            }
        }

        return null;
    }
}
