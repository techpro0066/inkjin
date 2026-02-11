<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\InkJinArtist;
use App\Models\InkJinTattoo;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        
        // For admin, get statistics
        if ($user->role === 'admin') {
            $stats = [
                'total_users' => User::where('role', '!=', 'admin')->count(),
                'total_regular_users' => User::where('role', 'user')->count(),
                'total_artists' => User::where('role', 'artist')->count(),
            ];
            
            return view('dashboard', compact('stats'));
        }
        
        // For other roles, just show the dashboard
        return view('dashboard');
    }

    /**
     * Display the artists list page within the dashboard.
     */
    public function artists(Request $request)
    {
        $search = $request->input('search', '');

        $query = InkJinArtist::withCount(['tattoos' => function ($q) {
                $q->where('visibility', 'public');
            }])
            ->where('visibility', 'public')
            ->orderBy('profile_name', 'asc')
            ->orderBy('artist_handle', 'asc');

        if ($search !== '') {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('artist_handle', 'like', $term)
                    ->orWhere('profile_name', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('studio', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('country', 'like', $term)
                    ->orWhere('style', 'like', $term);
            });
        }

        $artistsPaginated = $query->paginate(12);

        $artists = $artistsPaginated->getCollection()->map(function ($artist) {
            return [
                'uid' => $artist->id,
                'username' => $artist->artist_handle,
                'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
                'field_profile_picture' => null,
                'field_profile_instagram' => $artist->instagram,
                'field_profile_tiktok' => $artist->tiktok,
                'field_profile_website' => $artist->website,
                'field_profile_studio' => $artist->studio,
                'field_profile_primary_style' => $artist->style,
                'field_address_city' => $artist->city,
                'field_address_country' => $artist->country,
                'tattoo_count' => $artist->tattoos_count ?? 0,
            ];
        });

        $artistsPaginated->setCollection($artists);

        return view('artists.index', [
            'artists' => $artistsPaginated,
            'search' => $search,
        ]);
    }

    /**
     * Display a single artist's profile with their tattoos within the dashboard.
     */
    public function artistShow(Request $request)
    {
        $username = $request->route('username');

        $artist = InkJinArtist::where('artist_handle', $username)
            ->where('visibility', 'public')
            ->first();

        if ($artist === null) {
            abort(404, 'Artist not found');
        }

        // Get all public tattoos for this artist
        $tattoos = $artist->tattoos()
            ->where('visibility', 'public')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $artistData = [
            'uid' => $artist->id,
            'username' => $artist->artist_handle,
            'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
            'field_profile_picture' => null,
            'field_profile_description' => null,
            'field_profile_instagram' => $artist->instagram,
            'field_profile_tiktok' => $artist->tiktok,
            'field_profile_website' => $artist->website,
            'field_profile_mobile_phone' => $artist->mobile_phone,
            'field_profile_tattooing_since' => $artist->since,
            'field_profile_studio' => $artist->studio,
            'field_profile_primary_style' => $artist->style,
            'field_address_city' => $artist->city,
            'field_address_country' => $artist->country,
            'tattoo_count' => $artist->tattoos()->where('visibility', 'public')->count(),
        ];

        $tattoosData = $tattoos->getCollection()->map(function ($tattoo) {
            return [
                'id' => $tattoo->id,
                'title' => $tattoo->title,
                'filename' => $tattoo->filename,
                'description' => $tattoo->description,
                'primary_style' => $tattoo->primary_style,
                'suggested_placement' => $tattoo->suggested_placement,
                'color' => $tattoo->color,
                'tags' => $tattoo->tags,
                'price' => $tattoo->price,
                'max_price' => $tattoo->max_price,
                'currency' => $tattoo->currency,
                'size_height' => $tattoo->size_height,
                'size_width' => $tattoo->size_width,
            ];
        });

        $tattoos->setCollection($tattoosData);

        return view('artists.show', [
            'artist' => $artistData,
            'tattoos' => $tattoos,
        ]);
    }

    /**
     * Display a single tattoo detail page within the dashboard.
     */
    public function tattooShow(Request $request)
    {
        $id = $request->route('id');

        $tattoo = InkJinTattoo::where('visibility', 'public')->find($id);

        if ($tattoo === null) {
            abort(404, 'Tattoo not found');
        }

        $artist = $tattoo->artist;

        if ($artist === null) {
            abort(404, 'Artist not found');
        }

        $tattooData = [
            'id' => $tattoo->id,
            'title' => $tattoo->title,
            'filename' => $tattoo->filename,
            'description' => $tattoo->description,
            'primary_style' => $tattoo->primary_style,
            'other_styles' => $tattoo->other_styles,
            'suggested_placement' => $tattoo->suggested_placement,
            'color' => $tattoo->color,
            'tags' => $tattoo->tags,
            'price' => $tattoo->price,
            'max_price' => $tattoo->max_price,
            'cost_per_session' => $tattoo->cost_per_session,
            'min_sessions' => $tattoo->min_sessions,
            'max_sessions' => $tattoo->max_sessions,
            'session_time_h' => $tattoo->session_time_h,
            'currency' => $tattoo->currency,
            'price_model' => $tattoo->price_model,
            'notes' => $tattoo->notes,
            'size_height' => $tattoo->size_height,
            'size_width' => $tattoo->size_width,
        ];

        $artistData = [
            'uid' => $artist->id,
            'username' => $artist->artist_handle,
            'display_name' => $artist->display_name ?? $artist->profile_name ?? ($artist->first_name . ' ' . $artist->last_name),
            'field_profile_picture' => null,
            'field_profile_instagram' => $artist->instagram,
            'field_profile_tiktok' => $artist->tiktok,
            'field_profile_website' => $artist->website,
            'field_profile_studio' => $artist->studio,
            'field_profile_primary_style' => $artist->style,
            'field_address_city' => $artist->city,
            'field_address_country' => $artist->country,
        ];

        return view('artists.tattoo-show', [
            'tattoo' => $tattooData,
            'artist' => $artistData,
        ]);
    }
}

