<?php

namespace App\Http\Controllers;

use App\Models\User;
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
}

