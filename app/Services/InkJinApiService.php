<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InkJinApiService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('inkjin.api_url', 'http://inkjinapi.mp8dev.reea.net');
        $this->clientId = trim(config('inkjin.client_id', ''), '"\'');
        $this->clientSecret = trim(config('inkjin.client_secret', ''), '"\'');
    }

    /**
     * Get access token (anonymous access)
     * Token is cached for 1 hour to reduce API calls
     */
    protected function getAccessToken(): string
    {
        return Cache::remember('inkjin_access_token', 3600, function () {
            try {
                // Verify credentials are loaded
                if (empty($this->clientId) || empty($this->clientSecret)) {
                    Log::error('InkJin API credentials not configured', [
                        'client_id_set' => !empty($this->clientId),
                        'client_secret_set' => !empty($this->clientSecret),
                    ]);
                    throw new \Exception('InkJin API credentials not configured. Please check your .env file.');
                }

                $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
                    'client_id' => trim($this->clientId),
                    'client_secret' => trim($this->clientSecret),
                    'grant_type' => 'client_credentials',
                ]);

                if ($response->successful()) {
                    $token = $response->json('access_token');
                    if (empty($token)) {
                        Log::error('InkJin API returned empty access token', [
                            'response' => $response->json(),
                        ]);
                        throw new \Exception('InkJin API returned empty access token');
                    }
                    return $token;
                }

                Log::error('Failed to get InkJin access token', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => "{$this->baseUrl}/oauth/token",
                    'client_id_length' => strlen($this->clientId),
                    'client_secret_length' => strlen($this->clientSecret),
                ]);

                throw new \Exception('Failed to authenticate with InkJin API');
            } catch (\Exception $e) {
                Log::error('InkJin API authentication error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Make authenticated request to InkJin API
     */
    protected function makeRequest(string $method, string $endpoint, array $params = []): array
    {
        $token = $this->getAccessToken();
        
        $url = "{$this->baseUrl}{$endpoint}";
        
        // Always include _format=json
        $params['_format'] = 'json';

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->{strtolower($method)}($url, $params);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('InkJin API request failed', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \Exception("InkJin API request failed: {$response->status()}");
    }

    /**
     * Get tattoo by ID
     * 
     * @param int $tattooId The tattoo node ID
     * @return array|null Returns the tattoo data as an array, or null if not found
     */
    public function getTattooById(int $tattooId): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/api/tattoo/{$tattooId}");
            
            // The API returns an array with a single tattoo object
            if (is_array($response) && count($response) > 0) {
                return $response[0];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get tattoo by ID', [
                'tattoo_id' => $tattooId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get artist by ID
     * 
     * @param int $artistId The artist user ID (uid)
     * @return array|null Returns the artist data as an array, or null if not found
     */
    public function getArtistById(int $artistId): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/api/artists/{$artistId}");
            
            // The API returns an object with a 'rows' array containing the artist
            if (isset($response['rows']) && count($response['rows']) > 0) {
                return $response['rows'][0];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get artist by ID', [
                'artist_id' => $artistId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get artist by username
     * 
     * @param string $username The artist username
     * @return array|null Returns the artist data as an array, or null if not found
     */
    public function getArtistByUsername(string $username): ?array
    {
        try {
            // Search for artist by username using the artists listing endpoint
            $response = $this->makeRequest('GET', '/api/artists', [
                'search' => $username,
                'items_per_page' => 9999,
                'page' => 0,
            ]);
            
            // Search through results to find exact username match
            if (isset($response['rows']) && is_array($response['rows'])) {
                foreach ($response['rows'] as $artist) {
                    if (isset($artist['username']) && $artist['username'] === $username) {
                        // Found the artist, now get full details by ID
                        if (isset($artist['uid'])) {
                            return $this->getArtistById($artist['uid']);
                        }
                    }
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get artist by username', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get tattoo by ID and return as JSON response
     * 
     * @param int $tattooId The tattoo node ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTattooByIdJson(int $tattooId)
    {
        $tattoo = $this->getTattooById($tattooId);
        
        if ($tattoo === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tattoo not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $tattoo,
        ]);
    }

    /**
     * Get artist by ID and return as JSON response
     * 
     * @param int $artistId The artist user ID (uid)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getArtistByIdJson(int $artistId)
    {
        $artist = $this->getArtistById($artistId);
        
        if ($artist === null) {
            return response()->json([
                'success' => false,
                'message' => 'Artist not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $artist,
        ]);
    }
}

