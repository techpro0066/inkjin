<?php

namespace App\Http\Controllers;

use App\Models\InkJinArtist;
use App\Models\InkJinTattoo;
use App\Services\InkJinApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InkJinController extends Controller
{
    protected InkJinApiService $apiService;

    public function __construct(InkJinApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Get tattoo by ID
     * 
     * @param int $id Tattoo node ID
     * @return JsonResponse
     */
    public function getTattoo(int $id): JsonResponse
    {
        $tattoo = $this->apiService->getTattooById($id);
        
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
     * Get artist by ID
     * 
     * @param int $id Artist user ID (uid)
     * @return JsonResponse
     */
    public function getArtist(int $id): JsonResponse
    {
        $artist = $this->apiService->getArtistById($id);
        
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

    /**
     * Public tattoo page
     * URL: /{artist_name}/{tattoo_name}/{tattoo_id}
     * 
     * @param string $artistName Artist name slug
     * @param string $tattooName Tattoo name slug
     * @param int $tattooId Tattoo ID
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function publicTattooPage(string $artistName, string $tattooName, int $tattooId)
    {
        // Get tattoo by ID
        $tattoo = $this->apiService->getTattooById($tattooId);
        
        if ($tattoo === null) {
            abort(404, 'Tattoo not found');
        }
        
        // Get artist by ID from tattoo
        $artistId = $tattoo['author_id'] ?? null;
        if (!$artistId) {
            abort(404, 'Artist not found');
        }
        
        $artist = $this->apiService->getArtistById($artistId);
        
        if ($artist === null) {
            abort(404, 'Artist not found');
        }
        
        // Verify artist name matches
        $artistSlug = slugify($artist['display_name'] ?? $artist['username'] ?? '');
        $tattooSlug = slugify($tattoo['title'] ?? '');
        
        // If either name doesn't match, redirect to correct URL
        if ($artistSlug !== $artistName || $tattooSlug !== $tattooName) {
            return redirect()->route('public.tattoo', [
                'artist_name' => $artistSlug,
                'tattoo_name' => $tattooSlug,
                'tattoo_id' => $tattooId
            ], 301);
        }
        
        // All checks passed, show the page
        return view('public.tattoo', [
            'tattoo' => $tattoo,
            'artist' => $artist,
        ]);
    }

    /**
     * Public artist profile page
     * URL: /{username}
     * 
     * @param string $username Artist username
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function publicArtistProfile(string $username)
    {
        // Get artist by username
        $artist = $this->apiService->getArtistByUsername($username);
        
        if ($artist === null) {
            abort(404, 'Artist not found');
        }
        
        // Verify username matches (in case of redirect needed)
        $artistUsername = $artist['username'] ?? '';
        if ($artistUsername !== $username) {
            return redirect()->route('public.artist', [
                'username' => $artistUsername
            ], 301);
        }
        
        // Get all tattoos for the artist (they're already in the artist response)
        $tattoos = $artist['artist_tattoos'] ?? [];
        
        // All checks passed, show the page
        return view('public.artist', [
            'artist' => $artist,
            'tattoos' => $tattoos,
        ]);
    }

    /**
     * Get tattoo by ID from database
     * 
     * @param int $id Tattoo ID
     * @return JsonResponse
     */
    public function getTattooFromDb(int $id): JsonResponse
    {
        $tattoo = InkJinTattoo::with('artist')->find($id);
        
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
     * Get artist by ID from database
     * 
     * @param int $id Artist user ID
     * @return JsonResponse
     */
    public function getArtistFromDb(int $id): JsonResponse
    {
        $artist = InkJinArtist::find($id);
        
        if ($artist === null) {
            return response()->json([
                'success' => false,
                'message' => 'Artist not found',
            ], 404);
        }
        
        // Load related tattoos
        $artist->load('tattoos');
        
        return response()->json([
            'success' => true,
            'data' => $artist,
        ]);
    }

    /**
     * Public artist profile page from database
     * URL: /artist/{username}
     * 
     * @param string $username Artist username
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function publicArtistProfileFromDb(string $username)
    {
        // Get artist by username from database
        $artist = InkJinArtist::where('username', $username)->first();
        
        if ($artist === null) {
            abort(404, 'Artist not found');
        }
        
        // Verify username matches (in case of redirect needed)
        $artistUsername = $artist->username ?? '';
        if ($artistUsername !== $username) {
            return redirect()->route('public.artist.db', [
                'username' => $artistUsername
            ], 301);
        }
        
        // Get all tattoos for the artist
        $tattoos = $artist->tattoos;
        
        // Convert artist model to array format for view compatibility
        $artistData = [
            'uid' => $artist->user_id ?? $artist->id,
            'username' => $artist->username,
            'display_name' => $artist->display_name,
            'field_profile_picture' => $artist->profile_picture,
            'field_profile_description' => $artist->description,
            'field_profile_instagram' => $artist->instagram,
            'field_profile_tiktok' => $artist->tiktok,
            'field_profile_website' => $artist->website,
            'field_profile_mobile_phone' => $artist->phone,
            'field_profile_tattooing_since' => $artist->tattooing_since,
            'field_profile_studio' => $artist->studio,
            'field_profile_primary_style' => $artist->primary_style,
            'field_profile_style' => $artist->style,
            'field_address_city' => $artist->city,
            'field_address_country' => $artist->country,
            'followed_count' => $artist->followers_count ?? 0,
            'artist_tattoo_count' => [['tattoo_count' => $artist->tattoo_count ?? $tattoos->count()]],
        ];
        
        // Convert tattoos collection to array format
        $tattoosData = $tattoos->map(function ($tattoo) {
            return [
                'nid' => $tattoo->tattoo_id ?? $tattoo->id,
                'title' => $tattoo->title,
                'field_tattoo_image_preview' => $tattoo->image,
            ];
        })->toArray();
        
        // All checks passed, show the page
        return view('public.artist', [
            'artist' => $artistData,
            'tattoos' => $tattoosData,
            'tattooRoute' => 'public.tattoo.db', // Use database route for tattoos
        ]);
    }

    /**
     * Public tattoo page from database
     * URL: /tattoo/{artist_display_name}/{tattoo_title}/{tattoo_id}
     * 
     * @param string $artistDisplayName Artist display name slug
     * @param string $tattooTitle Tattoo title slug
     * @param int $tattooId Tattoo ID
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function publicTattooPageFromDb(string $artistDisplayName, string $tattooTitle, int $tattooId)
    {
        // Get tattoo by ID from database
        $tattoo = InkJinTattoo::find($tattooId);
        
        if ($tattoo === null) {
            abort(404, 'Tattoo not found');
        }
        
        // Get artist from database
        $artist = $tattoo->artist;
        
        if ($artist === null) {
            abort(404, 'Artist not found');
        }
        
        // Verify names match
        $artistSlug = slugify($artist->display_name ?? $artist->username ?? '');
        $tattooSlug = slugify($tattoo->title ?? '');
        
        // If either name doesn't match, redirect to correct URL
        if ($artistSlug !== $artistDisplayName || $tattooSlug !== $tattooTitle) {
            return redirect()->route('public.tattoo.db', [
                'artist_display_name' => $artistSlug,
                'tattoo_title' => $tattooSlug,
                'tattoo_id' => $tattooId
            ], 301);
        }
        
        // Convert tattoo model to array format for view compatibility
        $tattooData = [
            'tattoo_id' => $tattoo->tattoo_id ?? $tattoo->id,
            'title' => $tattoo->title,
            'field_tattoo_image_preview' => $tattoo->image,
            'field_tattoo_description' => null, // Not in CSV structure
            'field_tags_names' => $tattoo->tags,
            'field_tattoo_color' => $tattoo->color,
            'field_tattoo_style_primary' => $tattoo->primary_style,
            'field_tattoo_style' => $tattoo->style,
            'field_tattoo_suggested_placement' => $tattoo->suggested_placement,
            'field_tattoo_width' => null, // Not in CSV structure
            'field_tattoo_height' => null, // Not in CSV structure
            'field_tattoo_price' => null, // Not in CSV structure
            'author_id' => $tattoo->author_id ?? ($artist->user_id ?? $artist->id),
            'author_username' => $tattoo->author_username ?? $artist->username,
            'display_name' => $tattoo->author_display_name ?? $artist->display_name,
        ];
        
        // Convert artist model to array format for view compatibility
        $artistData = [
            'uid' => $artist->user_id ?? $artist->id,
            'username' => $artist->username,
            'display_name' => $artist->display_name,
            'field_profile_picture' => $artist->profile_picture,
            'field_profile_description' => $artist->description,
            'field_profile_instagram' => $artist->instagram,
            'field_profile_tiktok' => $artist->tiktok,
            'field_profile_website' => $artist->website,
            'field_profile_mobile_phone' => $artist->phone,
            'field_profile_tattooing_since' => $artist->tattooing_since,
            'field_profile_studio' => $artist->studio,
            'field_profile_primary_style' => $artist->primary_style,
            'field_profile_style' => $artist->style,
            'field_address_city' => $artist->city,
            'field_address_country' => $artist->country,
        ];
        
        // All checks passed, show the page
        return view('public.tattoo', [
            'tattoo' => $tattooData,
            'artist' => $artistData,
        ]);
    }

    /**
     * Public artists list page from database
     * URL: /artists
     * 
     * @return View
     */
    public function publicArtistsList()
    {
        // Get all artists from database with eager loading to avoid N+1 queries
        $artists = InkJinArtist::withCount('tattoos')
            ->orderBy('display_name', 'asc')
            ->orderBy('username', 'asc')
            ->get();
        
        // Convert artists to array format for view compatibility
        $artistsData = $artists->map(function ($artist) {
            return [
                'uid' => $artist->user_id ?? $artist->id,
                'username' => $artist->username,
                'display_name' => $artist->display_name,
                'field_profile_picture' => $artist->profile_picture,
                'field_profile_description' => $artist->description,
                'field_profile_instagram' => $artist->instagram,
                'field_profile_tiktok' => $artist->tiktok,
                'field_profile_website' => $artist->website,
                'field_profile_studio' => $artist->studio,
                'field_profile_primary_style' => $artist->primary_style,
                'field_address_city' => $artist->city,
                'field_address_country' => $artist->country,
                'followed_count' => $artist->followers_count ?? 0,
                'tattoo_count' => $artist->tattoo_count ?? $artist->tattoos_count ?? 0,
            ];
        })->toArray();
        
        return view('public.artists', [
            'artists' => $artistsData,
        ]);
    }
}

