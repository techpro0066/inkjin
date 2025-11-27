<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookingsController extends Controller
{
    /**
     * Display a listing of bookings for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Build query based on user role
        if ($user->role === 'artist') {
            // Artists see bookings they received (where they are the artist)
            $query = Booking::where('artist_user_id', $user->id)
                ->with(['user', 'tattoo']);
        } else {
            // Regular users see bookings they made
            $query = Booking::where('user_id', $user->id)
                ->with(['artist', 'tattoo']);
        }
        
        // Apply filters if provided
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('payment_status') && $request->payment_status !== '') {
            $query->where('payment_status', $request->payment_status);
        }
        
        // Filter by date range
        if ($request->has('date_from') && $request->date_from !== '') {
            $query->where('booking_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to !== '') {
            $query->where('booking_date', '<=', $request->date_to);
        }
        
        // Order by booking date and time (most recent first)
        $bookings = $query->orderBy('booking_date', 'desc')
            ->orderBy('start_time_utc', 'desc')
            ->paginate(15)
            ->withQueryString(); // Preserve query parameters in pagination links
        
        // Calculate summary statistics
        if ($user->role === 'artist') {
            $baseQuery = Booking::where('artist_user_id', $user->id);
        } else {
            $baseQuery = Booking::where('user_id', $user->id);
        }
        
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'confirmed' => (clone $baseQuery)->where('status', 'confirmed')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'upcoming' => (clone $baseQuery)
                ->where('status', 'confirmed')
                ->where('booking_date', '>=', Carbon::now()->toDateString())
                ->count(),
        ];
        
        return view('bookings.index', compact('bookings', 'stats'));
    }
}

