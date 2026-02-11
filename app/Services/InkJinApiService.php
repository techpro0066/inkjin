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
            $body = $response->json();
            if ($body === null && $response->body() !== '') {
                Log::warning('InkJin API returned non-JSON response', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body_preview' => substr($response->body(), 0, 500),
                ]);
                throw new \Exception('InkJin API returned invalid JSON (status ' . $response->status() . '). Check endpoint and API availability.');
            }
            return is_array($body) ? $body : [];
        }

        $status = $response->status();
        $bodyPreview = substr($response->body(), 0, 300);
        Log::error('InkJin API request failed', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $status,
            'body' => $response->body(),
        ]);
        $msg = "InkJin API request failed: {$status}";
        if ($bodyPreview !== '') {
            $msg .= ' — ' . $bodyPreview;
        }
        throw new \Exception($msg);
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
     * Allowed items_per_page values for the live API (Drupal constraint).
     */
    protected const LIVE_API_PAGE_SIZES = [5, 10, 15, 20, 25, 50, 9999];

    /**
     * Get list of artists from live InkJin API.
     * See docs/artist/get_listing.txt and docs/DIRECT_API_CALLS.md.
     *
     * @param int $page 1-based page number (converted to 0-based for API)
     * @param int $perPage Items per page (clamped to allowed API values, max 50)
     * @param string $search Optional search term
     * @return array{data: array, meta: array}
     */
    public function getArtistsListLive(int $page = 1, int $perPage = 24, string $search = ''): array
    {
        $page = max(1, $page);
        $perPage = min($perPage, 50);
        $perPage = $this->clampToAllowedPageSize($perPage);
        $apiPage = $page - 1; // API is 0-based

        $params = [
            'page' => $apiPage,
            'items_per_page' => $perPage,
        ];
        if ($search !== '') {
            $params['search'] = trim($search);
        }

        $response = $this->makeRequest('GET', '/api/artists', $params);
        $rows = $response['rows'] ?? [];
        $pager = $response['pager'] ?? [];

        $totalItems = (int) ($pager['total_items'] ?? count($rows));
        $totalPages = (int) ($pager['total_pages'] ?? 1);
        $itemsPerPage = (int) ($pager['items_per_page'] ?? $perPage);

        $data = array_map(function ($artist) {
            $tattooCount = 0;
            if (isset($artist['artist_tattoo_count'][0]['tattoo_count'])) {
                $tattooCount = (int) $artist['artist_tattoo_count'][0]['tattoo_count'];
            }
            return [
                'id' => (int) ($artist['uid'] ?? 0),
                'artist_handle' => $artist['username'] ?? '',
                'display_name' => $artist['display_name'] ?? '',
                'profile_url' => $this->artistProfileUrlFromUsername($artist['username'] ?? ''),
                'field_profile_picture' => $artist['field_profile_picture'] ?? null,
                'instagram' => $artist['field_profile_instagram'] ?? null,
                'tiktok' => $artist['field_profile_tiktok'] ?? null,
                'website' => $artist['field_profile_website'] ?? null,
                'studio' => $artist['field_profile_studio'] ?? null,
                'style' => $artist['field_profile_primary_style'] ?? $artist['field_profile_style'] ?? null,
                'city' => $artist['field_address_city'] ?? null,
                'country' => $artist['field_address_country'] ?? null,
                'tattoo_count' => $tattooCount,
            ];
        }, is_array($rows) ? $rows : []);

        return [
            'data' => array_values($data),
            'meta' => [
                'current_page' => $page,
                'last_page' => max(1, $totalPages),
                'per_page' => $itemsPerPage,
                'total' => $totalItems,
            ],
        ];
    }

    /**
     * Get list of tattoos from live InkJin API.
     * See docs/DIRECT_API_CALLS.md (api/tattoos/list).
     *
     * @param int $page 1-based page number (converted to 0-based for API)
     * @param int $perPage Items per page (clamped to allowed API values, max 50)
     * @param string $search Optional search term
     * @param string $authBy Optional author username(s), comma-separated
     * @return array{data: array, meta: array}
     */
    public function getTattoosListLive(int $page = 1, int $perPage = 24, string $search = '', string $authBy = ''): array
    {
        $page = max(1, $page);
        $perPage = min($perPage, 50);
        $perPage = $this->clampToAllowedPageSize($perPage);
        $apiPage = $page - 1;

        $params = [
            'page' => $apiPage,
            'items_per_page' => $perPage,
            'sort_by' => 'created',
            'sort_order' => 'DESC',
        ];
        if ($search !== '') {
            $params['search'] = trim($search);
        }
        if ($authBy !== '') {
            $params['auth_by'] = trim($authBy);
        }

        $response = $this->makeRequest('GET', '/api/tattoos/list', $params);
        $rows = $response['rows'] ?? [];
        $pager = $response['pager'] ?? [];

        $totalItems = (int) ($pager['total_items'] ?? count($rows));
        $totalPages = (int) ($pager['total_pages'] ?? 1);
        $itemsPerPage = (int) ($pager['items_per_page'] ?? $perPage);

        $data = array_map(function ($tattoo) {
            $nid = (int) ($tattoo['nid'] ?? $tattoo['id'] ?? 0);
            $title = $tattoo['title'] ?? '';
            $authorUsername = $tattoo['author_username'] ?? $tattoo['username'] ?? '';
            $authorDisplayName = $tattoo['author_display_name'] ?? $tattoo['display_name'] ?? '';
            $imageUrl = $tattoo['field_tattoo_image_preview'] ?? $tattoo['field_tattoo_image'] ?? $tattoo['filename'] ?? null;
            $tagsNames = $tattoo['field_tags_names'] ?? $tattoo['tags'] ?? '';
            $artistSlug = $this->slugify($authorDisplayName ?: $authorUsername);
            $tattooSlug = $this->slugify($title);

            return [
                'id' => $nid,
                'title' => $title,
                'description' => $tattoo['description'] ?? $tattoo['body'] ?? null,
                'filename' => $imageUrl,
                'primary_style' => $tattoo['field_primary_style'] ?? $tattoo['primary_style'] ?? null,
                'suggested_placement' => $tattoo['field_suggested_placement'] ?? $tattoo['suggested_placement'] ?? null,
                'color' => $tattoo['field_color'] ?? $tattoo['color'] ?? null,
                'tags' => is_array($tagsNames) ? implode(', ', $tagsNames) : $tagsNames,
                'price' => $tattoo['field_price'] ?? $tattoo['price'] ?? null,
                'currency' => $tattoo['field_currency'] ?? $tattoo['currency'] ?? null,
                'artist_handle' => $authorUsername,
                'artist_display_name' => $authorDisplayName,
                'artist_profile_url' => $this->artistProfileUrlFromUsername($authorUsername),
                'tattoo_url' => $this->tattooPageUrl($artistSlug, $tattooSlug, $nid),
            ];
        }, is_array($rows) ? $rows : []);

        return [
            'data' => array_values($data),
            'meta' => [
                'current_page' => $page,
                'last_page' => max(1, $totalPages),
                'per_page' => $itemsPerPage,
                'total' => $totalItems,
            ],
        ];
    }

    protected function clampToAllowedPageSize(int $perPage): int
    {
        foreach (self::LIVE_API_PAGE_SIZES as $allowed) {
            if ($perPage <= $allowed) {
                return $allowed;
            }
        }
        return 50;
    }

    protected function artistProfileUrlFromUsername(string $username): string
    {
        if ($username === '') {
            return '';
        }
        return route('public.artist', ['username' => $username]);
    }

    protected function tattooPageUrl(string $artistSlug, string $tattooSlug, int $tattooId): string
    {
        return route('public.tattoo', [
            'artist_name' => $artistSlug,
            'tattoo_name' => $tattooSlug,
            'tattoo_id' => $tattooId,
        ]);
    }

    protected function slugify(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/[^a-zA-Z0-9\s\-_.]/', '', $text);
        $text = preg_replace('/[\s\-_.]+/', '-', $text);
        return strtolower($text);
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

