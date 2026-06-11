<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\UserNotRegistered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $email = mb_strtolower(trim((string) $request->input('email')));

        if (
            $email !== ''
            && UserNotRegistered::query()->whereRaw('LOWER(email) = ?', [$email])->exists()
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'country_not_available' => true,
                    'redirect' => route('register'),
                ]);
            }

            return redirect()->route('register')->with('country_not_available', true);
        }

        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user->role !== 'admin' && $user->role !== 'artist' && $user->role !== 'user') {
            return abort(403, 'Access denied. You are not authorized to access this page.');
        }

        if ($user->role === 'user' && $user->must_set_password) {
            return redirect()->intended(route('user.bookings.index', ['set_password' => '1']));
        }

        return redirect()->intended(authenticated_home_url($user));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
