<?php

namespace App\Support;

class PhoneCountryCodes
{
    /**
     * Dial-code options for booking/request phone fields.
     * Prefer ISO in the UI; compose E.164 as +{dial}{national}.
     *
     * @return list<array{iso: string, name: string, dial: string}>
     */
    public static function all(): array
    {
        $countries = [
            ['iso' => 'GR', 'name' => 'Greece', 'dial' => '30'],
            ['iso' => 'AL', 'name' => 'Albania', 'dial' => '355'],
            ['iso' => 'AD', 'name' => 'Andorra', 'dial' => '376'],
            ['iso' => 'AE', 'name' => 'United Arab Emirates', 'dial' => '971'],
            ['iso' => 'AR', 'name' => 'Argentina', 'dial' => '54'],
            ['iso' => 'AT', 'name' => 'Austria', 'dial' => '43'],
            ['iso' => 'AU', 'name' => 'Australia', 'dial' => '61'],
            ['iso' => 'BA', 'name' => 'Bosnia and Herzegovina', 'dial' => '387'],
            ['iso' => 'BE', 'name' => 'Belgium', 'dial' => '32'],
            ['iso' => 'BG', 'name' => 'Bulgaria', 'dial' => '359'],
            ['iso' => 'BR', 'name' => 'Brazil', 'dial' => '55'],
            ['iso' => 'CA', 'name' => 'Canada', 'dial' => '1'],
            ['iso' => 'CH', 'name' => 'Switzerland', 'dial' => '41'],
            ['iso' => 'CL', 'name' => 'Chile', 'dial' => '56'],
            ['iso' => 'CN', 'name' => 'China', 'dial' => '86'],
            ['iso' => 'CY', 'name' => 'Cyprus', 'dial' => '357'],
            ['iso' => 'CZ', 'name' => 'Czechia', 'dial' => '420'],
            ['iso' => 'DE', 'name' => 'Germany', 'dial' => '49'],
            ['iso' => 'DK', 'name' => 'Denmark', 'dial' => '45'],
            ['iso' => 'EE', 'name' => 'Estonia', 'dial' => '372'],
            ['iso' => 'EG', 'name' => 'Egypt', 'dial' => '20'],
            ['iso' => 'ES', 'name' => 'Spain', 'dial' => '34'],
            ['iso' => 'FI', 'name' => 'Finland', 'dial' => '358'],
            ['iso' => 'FR', 'name' => 'France', 'dial' => '33'],
            ['iso' => 'GB', 'name' => 'United Kingdom', 'dial' => '44'],
            ['iso' => 'GE', 'name' => 'Georgia', 'dial' => '995'],
            ['iso' => 'HR', 'name' => 'Croatia', 'dial' => '385'],
            ['iso' => 'HU', 'name' => 'Hungary', 'dial' => '36'],
            ['iso' => 'IE', 'name' => 'Ireland', 'dial' => '353'],
            ['iso' => 'IL', 'name' => 'Israel', 'dial' => '972'],
            ['iso' => 'IN', 'name' => 'India', 'dial' => '91'],
            ['iso' => 'IS', 'name' => 'Iceland', 'dial' => '354'],
            ['iso' => 'IT', 'name' => 'Italy', 'dial' => '39'],
            ['iso' => 'JP', 'name' => 'Japan', 'dial' => '81'],
            ['iso' => 'KR', 'name' => 'South Korea', 'dial' => '82'],
            ['iso' => 'LT', 'name' => 'Lithuania', 'dial' => '370'],
            ['iso' => 'LU', 'name' => 'Luxembourg', 'dial' => '352'],
            ['iso' => 'LV', 'name' => 'Latvia', 'dial' => '371'],
            ['iso' => 'MA', 'name' => 'Morocco', 'dial' => '212'],
            ['iso' => 'MD', 'name' => 'Moldova', 'dial' => '373'],
            ['iso' => 'ME', 'name' => 'Montenegro', 'dial' => '382'],
            ['iso' => 'MK', 'name' => 'North Macedonia', 'dial' => '389'],
            ['iso' => 'MT', 'name' => 'Malta', 'dial' => '356'],
            ['iso' => 'MX', 'name' => 'Mexico', 'dial' => '52'],
            ['iso' => 'NL', 'name' => 'Netherlands', 'dial' => '31'],
            ['iso' => 'NO', 'name' => 'Norway', 'dial' => '47'],
            ['iso' => 'NZ', 'name' => 'New Zealand', 'dial' => '64'],
            ['iso' => 'PL', 'name' => 'Poland', 'dial' => '48'],
            ['iso' => 'PT', 'name' => 'Portugal', 'dial' => '351'],
            ['iso' => 'RO', 'name' => 'Romania', 'dial' => '40'],
            ['iso' => 'RS', 'name' => 'Serbia', 'dial' => '381'],
            ['iso' => 'RU', 'name' => 'Russia', 'dial' => '7'],
            ['iso' => 'SA', 'name' => 'Saudi Arabia', 'dial' => '966'],
            ['iso' => 'SE', 'name' => 'Sweden', 'dial' => '46'],
            ['iso' => 'SI', 'name' => 'Slovenia', 'dial' => '386'],
            ['iso' => 'SK', 'name' => 'Slovakia', 'dial' => '421'],
            ['iso' => 'TR', 'name' => 'Turkey', 'dial' => '90'],
            ['iso' => 'UA', 'name' => 'Ukraine', 'dial' => '380'],
            ['iso' => 'US', 'name' => 'United States', 'dial' => '1'],
            ['iso' => 'XK', 'name' => 'Kosovo', 'dial' => '383'],
            ['iso' => 'ZA', 'name' => 'South Africa', 'dial' => '27'],
        ];

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
        foreach (self::all() as $row) {
            if ($row['iso'] === $iso) {
                return $row['dial'];
            }
        }

        return null;
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
