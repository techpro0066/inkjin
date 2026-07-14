<?php

namespace App\Http\Controllers\UserController;

use App\Http\Controllers\Controller;
use App\Services\UserDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly UserDashboardService $dashboardService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $dashboard = $this->dashboardService->buildForUser($user);

        return view('user.dashboard', [
            'stats' => $dashboard['stats'],
            'upcomingBookings' => $dashboard['upcoming_bookings'],
            'recentMessages' => $dashboard['recent_messages'],
            'activeRequests' => $dashboard['active_requests'],
        ]);
    }
}
