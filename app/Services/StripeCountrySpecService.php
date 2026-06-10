<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\CountrySpec;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class StripeCountrySpecService
{
    private const CACHE_KEY = 'stripe.connect.payout_countries';

    public function isConfigured(): bool
    {
        return (bool) config('services.stripe.secret');
    }

    /**
     * Bank-account countries supported for this platform's Stripe Connect account.
     *
     * @return array<string, array{name: string, currency: string}>
     */
    public function supportedPayoutCountries(): array
    {
        if (! $this->isConfigured()) {
            return $this->configFallback();
        }

        $ttl = (int) config('services.stripe.connect.countries_cache_ttl', 86400);

        return Cache::remember(self::CACHE_KEY, $ttl, function () {
            try {
                return $this->fetchFromStripe();
            } catch (\Throwable $e) {
                Log::warning('Could not fetch Stripe Connect payout countries', [
                    'error' => $e->getMessage(),
                ]);

                return $this->configFallback();
            }
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, array{name: string, currency: string}>
     */
    private function fetchFromStripe(): array
    {
        $this->initialize();

        $platform = Account::retrieve();
        $platformCountry = strtoupper((string) (
            $platform->country ?? config('services.stripe.connect.default_country', 'GR')
        ));

        $platformSpec = CountrySpec::retrieve($platformCountry);
        $transferCountries = $platformSpec->supported_transfer_countries ?? [];

        if ($transferCountries === []) {
            $transferCountries = [$platformCountry];
        }

        /** @var array<string, CountrySpec> $specIndex */
        $specIndex = [];
        $allSpecs = CountrySpec::all(['limit' => 100]);
        foreach ($allSpecs->data as $spec) {
            $specIndex[strtoupper($spec->id)] = $spec;
        }

        $result = [];
        foreach ($transferCountries as $code) {
            $code = strtoupper((string) $code);
            if ($code === '') {
                continue;
            }

            $spec = $specIndex[$code] ?? null;
            if ($spec === null) {
                try {
                    $spec = CountrySpec::retrieve($code);
                } catch (ApiErrorException) {
                    continue;
                }
            }

            $currency = strtoupper((string) ($spec->default_currency ?? ''));
            if ($currency === '') {
                continue;
            }

            $result[$code] = [
                'name' => $this->displayCountryName($code),
                'currency' => $currency,
            ];
        }

        uasort($result, fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        return $result;
    }

    private function displayCountryName(string $code): string
    {
        if (extension_loaded('intl')) {
            $name = \Locale::getDisplayRegion('-'.$code, 'en');
            if (is_string($name) && $name !== '' && $name !== $code) {
                return $name;
            }
        }

        $fallback = config('stripe_connect_countries.supported.'.$code.'.name');
        if (is_string($fallback) && $fallback !== '') {
            return $fallback;
        }

        return $code;
    }

    /**
     * @return array<string, array{name: string, currency: string}>
     */
    private function configFallback(): array
    {
        return config('stripe_connect_countries.supported', []);
    }

    private function initialize(): void
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));
    }
}
