<?php

namespace App\Http\Controllers;

use App\Models\CustomRequest;
use App\Services\ArtistDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ArtistDashboardController extends Controller
{
    public function __construct(private ArtistDashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $needsWeeklyAvailabilitySetup = $user && ! $user->hasWeeklyAvailabilitySlots();

        $recentCustomRequests = CustomRequest::query()
            ->with(['user'])
            ->where('artist_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $pendingCustomRequestsCount = CustomRequest::query()
            ->where('artist_id', Auth::id())
            ->where('status', 'pending')
            ->count();

        $dashboard = $this->dashboardService->buildForArtist((int) Auth::id());

        return view('artist.dashboard', [
            'needsWeeklyAvailabilitySetup' => $needsWeeklyAvailabilitySetup,
            'recentCustomRequests' => $recentCustomRequests,
            'pendingCustomRequestsCount' => $pendingCustomRequestsCount,
            'dashboardStats' => $dashboard['stats'],
            'recentBookings' => $dashboard['recent_bookings'],
        ]);
    }
}
