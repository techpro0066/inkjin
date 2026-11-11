<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Services\ArtistNavBadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NavBadgesController extends Controller
{
    public function __construct(private ArtistNavBadgeService $badges) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('userDetail');

        return response()->json($this->badges->forArtist($user));
    }
}
