<?php

namespace App\Support;

/**
 * EU VAT on Inkjin booking fee only (deposit is untaxed).
 * Country is inferred from the client's phone country calling code.
 * All EU member states use a flat 24% rate; outside the EU the rate is 0%.
 */
class EuVat
{
    /** Flat VAT rate (%) applied to the booking fee for every EU country. */
    public const EU_RATE = 24.0;

    /**
     * EU member states (ISO 3166-1 alpha-2 → display name).
     *
     * @var array<string, string>
     */
    private const COUNTRIES = [
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'BG' => 'Bulgaria',
        'HR' => 'Croatia',
        'CY' => 'Cyprus',
        'CZ' => 'Czechia',
        'DK' => 'Denmark',
        'EE' => 'Estonia',
        'FI' => 'Finland',
        'FR' => 'France',
        'DE' => 'Germany',
        'GR' => 'Greece',
        'HU' => 'Hungary',
        'IE' => 'Ireland',
        'IT' => 'Italy',
        'LV' => 'Latvia',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MT' => 'Malta',
        'NL' => 'Netherlands',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'RO' => 'Romania',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'ES' => 'Spain',
        'SE' => 'Sweden',
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

        $countryName = self::COUNTRIES[$countryCode];
        $rate = self::EU_RATE;

        return [
            'is_eu' => true,
            'country_code' => $countryCode,
            'country_name' => $countryName,
            'rate' => $rate,
            'label' => 'VAT on booking fee ('.(int) $rate.'%)',
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
