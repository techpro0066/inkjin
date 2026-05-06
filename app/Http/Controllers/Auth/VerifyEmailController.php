<?php

namespace App\Http\Controllers\Auth;

use App\Mail\ArtistWelcomeMail;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(authenticated_home_url().'?verified=1');
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

        return redirect()->intended(authenticated_home_url().'?verified=1');
    }
}
