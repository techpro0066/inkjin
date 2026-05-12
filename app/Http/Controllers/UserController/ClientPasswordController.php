<?php

namespace App\Http\Controllers\UserController;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ClientPasswordController extends Controller
{
    /**
     * First-time password after booking (replaces temporary credentials).
     */
    public function storeBookingInitial(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->must_set_password) {
            return redirect()->route('user.dashboard');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_set_password' => false,
        ])->save();

        return redirect()->route('user.bookings.index')->with('status', 'password-set');
    }
}
