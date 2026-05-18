<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostManagedRequestAccessController extends Controller
{
    /**
     * Signed link from the managed booking flow: logs the client in and sends them to their dashboard.
     */
    public function __invoke(Request $request, User $user, BookingRequest $bookingRequest): RedirectResponse
    {
        if ((int) $bookingRequest->user_id !== (int) $user->id) {
            abort(403);
        }

        if ($user->role !== 'user') {
            abort(403);
        }

        Auth::login($user, false);
        $request->session()->regenerate();

        $params = ['set_password' => '1'];

        if ($user->must_set_password) {
            return redirect()->route('user.requests.index', $params);
        }

        return redirect()->route('user.requests.index');
    }
}
