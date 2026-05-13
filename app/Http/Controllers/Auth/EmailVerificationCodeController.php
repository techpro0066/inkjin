<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ArtistWelcomeMail;
use App\Support\EmailVerificationOtp;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

            if ($request->user()->role === 'artist') {
                try {
                    Mail::to($request->user()->email)->send(new ArtistWelcomeMail(url('/artist/dashboard')));
                } catch (\Throwable $e) {
                    Log::error('Failed to send artist welcome email after verification', [
                        'user_id' => $request->user()->id,
                        'email' => $request->user()->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $home = authenticated_home_url($request->user());
        $separator = str_contains($home, '?') ? '&' : '?';

        return redirect()->intended($home.$separator.'verified=1');
    }
}
