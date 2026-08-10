<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        $user->update([
            'password' => $validated['password'],
        ]);

        Auth::logoutOtherDevices($validated['password']);

        $this->forgetOtherSessions($request);

        // Keep the current session valid after the password hash changed.
        $request->session()->put(
            'password_hash_'.Auth::getDefaultDriver(),
            $user->getAuthPassword()
        );

        Mail::to($user->email)->send(new PasswordChangedMail(
            firstName: trim((string) $user->first_name) !== '' ? $user->first_name : 'there',
            supportEmail: (string) config('mail.from.address'),
            resetPasswordUrl: route('password.request'),
        ));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully. Other sessions have been signed out.',
                'status' => 'password-updated',
            ]);
        }

        return back()->with('status', 'password-updated');
    }

    private function forgetOtherSessions(Request $request): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $userId = $request->user()?->getAuthIdentifier();
        if (! $userId) {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $userId)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
