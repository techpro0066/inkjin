<?php

namespace App\Support;

class PhoneCountryCodes
{
    /**
     * Calling codes for artist registration countries.
     *
     * @var array<string, string>
     */
    private const DIAL_BY_ISO = [
        'AT' => '43',
        'AU' => '61',
        'BE' => '32',
        'BG' => '359',
        'CA' => '1',
        'CH' => '41',
        'CY' => '357',
        'CZ' => '420',
        'DE' => '49',
        'DK' => '45',
        'EE' => '372',
        'ES' => '34',
        'FI' => '358',
        'FR' => '33',
        'GB' => '44',
        'GR' => '30',
        'HR' => '385',
        'HU' => '36',
        'IE' => '353',
        'IT' => '39',
        'LT' => '370',
        'LU' => '352',
        'LV' => '371',
        'MT' => '356',
        'NL' => '31',
        'NO' => '47',
        'NZ' => '64',
        'PL' => '48',
        'PT' => '351',
        'RO' => '40',
        'SE' => '46',
        'SG' => '65',
        'SI' => '386',
        'SK' => '421',
        'US' => '1',
    ];

    /**
     * Dial-code options for booking/request phone fields.
     * Same country set as artist signup (`StripeConnectCountries` registration list).
     *
     * @return list<array{iso: string, name: string, dial: string}>
     */
    public static function all(): array
    {
        $countries = [];

        foreach (StripeConnectCountries::registrationCountriesForSelect() as $row) {
            $iso = strtoupper((string) ($row['code'] ?? ''));
            $dial = self::DIAL_BY_ISO[$iso] ?? null;
            if ($iso === '' || $dial === null) {
                continue;
            }

            $countries[] = [
                'iso' => $iso,
                'name' => (string) ($row['name'] ?? $iso),
                'dial' => $dial,
            ];
        }

        usort($countries, static function (array $a, array $b): int {
            if ($a['iso'] === 'GR') {
                return -1;
            }
            if ($b['iso'] === 'GR') {
                return 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $countries;
    }

    public static function defaultIso(): string
    {
        return 'GR';
    }

    public static function dialForIso(string $iso): ?string
    {
        $iso = strtoupper(trim($iso));

        return self::DIAL_BY_ISO[$iso] ?? null;
    }

    /**
     * Build E.164 from ISO dial code + national number.
     */
    public static function toE164(string $iso, string $nationalNumber): string
    {
        $dial = self::dialForIso($iso);
        $digits = preg_replace('/\D+/', '', $nationalNumber) ?? '';
        $digits = ltrim($digits, '0');

        if ($dial === null || $digits === '') {
            return '';
        }

        return '+'.$dial.$digits;
    }

    /**
     * Split an E.164 phone into iso + national number when possible.
     *
     * @return array{iso: string, national: string}|null
     */
    public static function splitE164(?string $phone): ?array
    {
        $normalized = PaymentMethods::normalizePhone($phone);
        if ($normalized === '' || ! str_starts_with($normalized, '+')) {
            return null;
        }

        $digits = substr($normalized, 1);
        $best = null;
        $bestLen = 0;

        foreach (self::all() as $row) {
            $dial = $row['dial'];
            $len = strlen($dial);
            if ($len > $bestLen && str_starts_with($digits, $dial)) {
                $best = $row;
                $bestLen = $len;
            }
        }

        if (! $best) {
            return null;
        }

        return [
            'iso' => $best['iso'],
            'national' => substr($digits, $bestLen),
        ];
    }
}
