<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(authenticated_home_url());
        }

        try {
            $request->user()->sendEmailVerificationNotification();
            return back()->with('status', 'verification-link-sent');
        } catch (\Symfony\Component\Mailer\Exception\UnexpectedResponseException $e) {
            // Handle Mailtrap rate limit or other SMTP errors gracefully
            // Email might still be sent, so we continue with success message
            Log::warning('Email verification notification error: ' . $e->getMessage());
            return back()->with('status', 'verification-link-sent');
        } catch (\Exception $e) {
            // Handle any other email errors
            Log::error('Email verification notification failed: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Unable to send verification email. Please try again later.']);
        }
    }
}
