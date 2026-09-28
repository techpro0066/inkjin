<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'redirect' => authenticated_home_url(),
                ]);
            }

            return redirect()->intended(authenticated_home_url());
        }

        try {
            $request->user()->sendEmailVerificationNotification();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'verification-code-sent',
                ]);
            }

            return back()->with('status', 'verification-code-sent');
        } catch (\Symfony\Component\Mailer\Exception\UnexpectedResponseException $e) {
            Log::warning('Email verification notification error: '.$e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'verification-code-sent',
                ]);
            }

            return back()->with('status', 'verification-code-sent');
        } catch (\Exception $e) {
            Log::error('Email verification notification failed: '.$e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to send verification code. Please try again later.',
                ], 422);
            }

            return back()->withErrors(['email' => 'Unable to send verification code. Please try again later.']);
        }
    }
}
