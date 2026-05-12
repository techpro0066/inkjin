<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostBookingAccessController extends Controller
{
    /**
     * Signed link from the booking confirmation flow: logs the client in and sends them to bookings.
     */
    public function __invoke(Request $request, User $user, Booking $booking): RedirectResponse
    {
        if ((int) $booking->user_id !== (int) $user->id) {
            abort(403);
        }

        if ($user->role !== 'user') {
            abort(403);
        }

        Auth::login($user, false);
        $request->session()->regenerate();

        if ($user->must_set_password) {
            return redirect()->route('user.bookings.index', ['set_password' => '1']);
        }

        return redirect()->route('user.bookings.index');
    }
}
