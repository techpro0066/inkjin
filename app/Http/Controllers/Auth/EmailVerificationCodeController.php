<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\EmailVerificationOtp;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationCodeController extends Controller
{
    /**
     * Verify email using the 4-digit code sent to the user's inbox.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(authenticated_home_url($request->user()));
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{4}$/'],
        ]);

        if (! EmailVerificationOtp::verify($request->user(), $validated['code'])) {
            return back()
                ->withErrors(['code' => 'That code is invalid or has expired. You can request a new code below.'])
                ->withInput();
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        $home = authenticated_home_url($request->user());
        $separator = str_contains($home, '?') ? '&' : '?';

        return redirect()->intended($home.$separator.'verified=1');
    }
}
