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
}
