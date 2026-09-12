<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationPreferencesService
{
    /**
     * Country names/codes treated as United States for regional defaults.
     *
     * @var list<string>
     */
    private const US_ALIASES = ['us', 'usa', 'united states', 'united states of america'];

    /**
     * Default IANA timezone per supported registration country code.
     *
     * @var array<string, string>
     */
    private const DEFAULT_TIMEZONE_BY_COUNTRY = [
        'AT' => 'Europe/Vienna',
        'BE' => 'Europe/Brussels',
        'BG' => 'Europe/Sofia',
        'HR' => 'Europe/Zagreb',
        'CY' => 'Asia/Nicosia',
        'CZ' => 'Europe/Prague',
        'DK' => 'Europe/Copenhagen',
        'EE' => 'Europe/Tallinn',
        'FI' => 'Europe/Helsinki',
        'FR' => 'Europe/Paris',
        'DE' => 'Europe/Berlin',
        'GR' => 'Europe/Athens',
        'HU' => 'Europe/Budapest',
        'IE' => 'Europe/Dublin',
        'IT' => 'Europe/Rome',
        'LV' => 'Europe/Riga',
        'LT' => 'Europe/Vilnius',
        'LU' => 'Europe/Luxembourg',
        'MT' => 'Europe/Malta',
        'NL' => 'Europe/Amsterdam',
        'PL' => 'Europe/Warsaw',
        'PT' => 'Europe/Lisbon',
        'RO' => 'Europe/Bucharest',
        'SK' => 'Europe/Bratislava',
        'SI' => 'Europe/Ljubljana',
        'ES' => 'Europe/Madrid',
        'SE' => 'Europe/Stockholm',
        'NO' => 'Europe/Oslo',
        'CH' => 'Europe/Zurich',
        'GB' => 'Europe/London',
        'US' => 'America/New_York',
        'CA' => 'America/Toronto',
        'AU' => 'Australia/Sydney',
        'NZ' => 'Pacific/Auckland',
        'SG' => 'Asia/Singapore',
    ];

    private function apiKey(): ?string
    {
        $key = config('services.google.timezone_api_key')
            ?: config('services.google.place_api_key');

        return $key ? (string) $key : null;
    }

    /**
     * Resolve the IANA timezone ID for a location.
     *
     * Uses the Google Time Zone API with coordinates. When coordinates are not
     * provided, the address is geocoded first (Google Geocoding API).
     */
    public function resolveTimezone(?float $latitude, ?float $longitude, ?string $address = null): ?string
    {
        $key = $this->apiKey();
        if ($key === null) {
            return null;
        }

        if (($latitude === null || $longitude === null) && $address) {
            [$latitude, $longitude] = $this->geocodeAddress($address, $key) ?? [null, null];
        }

        if ($latitude === null || $longitude === null) {
            return null;
        }

        try {
            $response = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/timezone/json', [
                'location' => $latitude.','.$longitude,
                'timestamp' => now()->timestamp,
                'key' => $key,
            ]);

            if (! $response->ok()) {
                return null;
            }

            $data = $response->json();
            if (($data['status'] ?? null) !== 'OK') {
                Log::warning('Time Zone API returned non-OK status', [
                    'status' => $data['status'] ?? null,
                    'error_message' => $data['errorMessage'] ?? null,
                ]);

                return null;
            }

            $timezoneId = $data['timeZoneId'] ?? null;

            return is_string($timezoneId) && $timezoneId !== '' ? $timezoneId : null;
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve timezone from Time Zone API', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function geocodeAddress(string $address, string $key): ?array
    {
        try {
            $response = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $key,
            ]);

            if (! $response->ok()) {
                return null;
            }

            $data = $response->json();
            $location = $data['results'][0]['geometry']['location'] ?? null;
            if (! is_array($location) || ! isset($location['lat'], $location['lng'])) {
                return null;
            }

            return [(float) $location['lat'], (float) $location['lng']];
        } catch (\Throwable $e) {
            Log::warning('Failed to geocode studio address', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function isUnitedStates(?string $country): bool
    {
        $normalized = strtolower(trim((string) $country));

        return $normalized !== '' && in_array($normalized, self::US_ALIASES, true);
    }

    public function dateFormatForCountry(?string $country): string
    {
        return $this->isUnitedStates($country) ? 'MM/DD/YYYY' : 'DD/MM/YYYY';
    }

    public function sizeUnitForCountry(?string $country): string
    {
        return $this->isUnitedStates($country) ? 'in' : 'cm';
    }

    public function timezoneForCountryCode(?string $countryCode): string
    {
        $code = strtoupper(trim((string) $countryCode));

        return self::DEFAULT_TIMEZONE_BY_COUNTRY[$code] ?? 'UTC';
    }

    /**
     * @return array{timezone: string, date_time_format: string, size_unit: string}
     */
    public function preferencesForCountryCode(?string $countryCode): array
    {
        $code = strtoupper(trim((string) $countryCode));

        return [
            'timezone' => $this->timezoneForCountryCode($code),
            'date_time_format' => $this->dateFormatForCountry($code),
            'size_unit' => $this->sizeUnitForCountry($code),
        ];
    }

    /**
     * @return array<string, array{timezone: string, date_time_format: string, size_unit: string}>
     */
    public function preferencesMapForCountries(array $countryCodes): array
    {
        $map = [];
        foreach ($countryCodes as $countryCode) {
            $code = strtoupper(trim((string) $countryCode));
            if ($code === '') {
                continue;
            }
            $map[$code] = $this->preferencesForCountryCode($code);
        }

        return $map;
    }
}
