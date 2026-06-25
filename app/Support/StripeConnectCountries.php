<?php

namespace App\Support;

use App\Models\UserDetail;
use App\Services\StripeCountrySpecService;
use ResourceBundle;

class StripeConnectCountries
{
    /**
     * ISO codes offered on the artist registration page.
     *
     * @var list<string>
     */
    private const REGISTRATION_COUNTRY_CODES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR',
        'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK',
        'SI', 'ES', 'SE', 'NO', 'CH', 'GB', 'US', 'CA', 'AU', 'NZ', 'SG',
    ];

    /**
     * @return array<string, array{name: string, currency: string}>
     */
    public static function supported(): array
    {
        return app(StripeCountrySpecService::class)->supportedPayoutCountries();
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public static function supportedForSelect(): array
    {
        $items = [];
        foreach (self::supported() as $code => $meta) {
            $items[] = [
                'code' => $code,
                'name' => $meta['name'],
            ];
        }

        usort($items, fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        return $items;
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public static function registrationCountriesForSelect(): array
    {
        $items = [];

        foreach (self::REGISTRATION_COUNTRY_CODES as $code) {
            $meta = self::metaForCode($code);
            if ($meta === null) {
                continue;
            }

            $items[] = [
                'code' => $code,
                'name' => $meta['name'],
            ];
        }

        usort($items, fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        return $items;
    }

    public static function isRegistrationCountry(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), self::REGISTRATION_COUNTRY_CODES, true);
    }

    public static function isSupported(string $countryCode): bool
    {
        return self::metaForCode($countryCode) !== null;
    }

    public static function nameFor(string $countryCode): ?string
    {
        return self::metaForCode($countryCode)['name'] ?? null;
    }

    public static function currencyForCountry(string $countryCode): ?string
    {
        $currency = self::metaForCode($countryCode)['currency'] ?? null;

        return $currency ? strtoupper($currency) : null;
    }

    /**
     * Country names for the payout waiting list (Stripe Connect not supported).
     *
     * @return list<string>
     */
    public static function unsupportedCountryNamesForWaitingList(): array
    {
        $supportedNames = array_map(
            fn (array $meta) => strtolower($meta['name']),
            self::supported()
        );

        $names = [];
        $filePath = storage_path('app/data/countries-cities.json');
        if (is_readable($filePath)) {
            $data = json_decode((string) file_get_contents($filePath), true);
            if (is_array($data)) {
                foreach ($data as $item) {
                    $name = trim((string) ($item['country'] ?? ''));
                    if ($name === '' || in_array(strtolower($name), $supportedNames, true)) {
                        continue;
                    }
                    $names[$name] = $name;
                }
            }
        }

        foreach (self::fallbackWorldCountries() as $code => $name) {
            if (! in_array($code, array_keys(self::supported()), true)) {
                $names[$name] = $name;
            }
        }

        $names = array_values($names);
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    /**
     * Countries not supported for Stripe payouts (waiting list).
     *
     * @return array<string, string> ISO code => English name
     */
    public static function unsupportedForWaitingList(): array
    {
        if (extension_loaded('intl') && class_exists(ResourceBundle::class)) {
            $supportedCodes = array_keys(self::supported());
            $all = self::allCountriesByCode();
            $unsupported = [];

            foreach ($all as $code => $name) {
                if (! in_array($code, $supportedCodes, true)) {
                    $unsupported[$code] = $name;
                }
            }

            asort($unsupported);

            return $unsupported;
        }

        $unsupported = [];
        foreach (self::fallbackWorldCountries() as $code => $name) {
            if (! isset(self::supported()[$code])) {
                $unsupported[$code] = $name;
            }
        }
        asort($unsupported);

        return $unsupported;
    }

    /**
     * Update preferences currency when it does not match the bank account country.
     *
     * @return array{updated: bool, currency: string|null, previous: string|null}
     */
    public static function syncCurrencyFromBankCountry(UserDetail $userDetail): array
    {
        $country = strtoupper(trim((string) $userDetail->payout_bank_country));
        $expected = self::currencyForCountry($country);
        $previous = $userDetail->currency ? strtoupper(trim($userDetail->currency)) : null;

        if ($expected === null) {
            return ['updated' => false, 'currency' => $previous, 'previous' => $previous];
        }

        if ($previous === $expected) {
            return ['updated' => false, 'currency' => $expected, 'previous' => $previous];
        }

        $userDetail->currency = $expected;

        return ['updated' => true, 'currency' => $expected, 'previous' => $previous];
    }

    /**
     * @return array{name: string, currency: string}|null
     */
    private static function metaForCode(string $countryCode): ?array
    {
        $code = strtoupper(trim($countryCode));
        if ($code === '') {
            return null;
        }

        $supported = self::supported();
        if (isset($supported[$code])) {
            return $supported[$code];
        }

        $config = config('stripe_connect_countries.supported.'.$code);
        if (! is_array($config) || empty($config['name']) || empty($config['currency'])) {
            return null;
        }

        return [
            'name' => (string) $config['name'],
            'currency' => strtoupper((string) $config['currency']),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function allCountriesByCode(): array
    {
        if (extension_loaded('intl') && class_exists(ResourceBundle::class)) {
            $bundle = ResourceBundle::create('en', 'ICU');
            if ($bundle) {
                $countries = $bundle->get('Countries');
                if ($countries) {
                    $result = [];
                    foreach ($countries as $code => $name) {
                        if (! is_string($code) || strlen($code) !== 2 || $code === 'ZZ') {
                            continue;
                        }
                        $result[$code] = (string) $name;
                    }

                    return $result;
                }
            }
        }

        return self::fallbackWorldCountries();
    }

    /**
     * @return array<string, string>
     */
    private static function fallbackWorldCountries(): array
    {
        $names = [];
        foreach (self::supported() as $code => $meta) {
            $names[$code] = $meta['name'];
        }

        $extras = [
            'AF' => 'Afghanistan', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AD' => 'Andorra',
            'AO' => 'Angola', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AZ' => 'Azerbaijan',
            'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BY' => 'Belarus', 'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana', 'BN' => 'Brunei', 'KH' => 'Cambodia',
            'CL' => 'Chile', 'CN' => 'China', 'CO' => 'Colombia', 'CR' => 'Costa Rica',
            'CU' => 'Cuba', 'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt',
            'SV' => 'El Salvador', 'ET' => 'Ethiopia', 'GE' => 'Georgia', 'GH' => 'Ghana',
            'GT' => 'Guatemala', 'HN' => 'Honduras', 'IN' => 'India', 'ID' => 'Indonesia',
            'IR' => 'Iran', 'IQ' => 'Iraq', 'IL' => 'Israel', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan',
            'KE' => 'Kenya', 'KW' => 'Kuwait', 'LB' => 'Lebanon', 'LY' => 'Libya', 'MY' => 'Malaysia',
            'MA' => 'Morocco', 'NP' => 'Nepal', 'NG' => 'Nigeria', 'PK' => 'Pakistan', 'PA' => 'Panama',
            'PY' => 'Paraguay', 'PE' => 'Peru', 'PH' => 'Philippines', 'QA' => 'Qatar', 'RU' => 'Russia',
            'SA' => 'Saudi Arabia', 'RS' => 'Serbia', 'ZA' => 'South Africa', 'KR' => 'South Korea',
            'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SY' => 'Syria', 'TW' => 'Taiwan', 'TH' => 'Thailand',
            'TN' => 'Tunisia', 'TR' => 'Turkey', 'UA' => 'Ukraine', 'UY' => 'Uruguay', 'VE' => 'Venezuela',
            'VN' => 'Vietnam', 'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe',
        ];

        return array_merge($extras, $names);
    }
}
