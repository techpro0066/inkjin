<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Auto-logout deleted or deactivated accounts that still have a live session.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $deleted = method_exists($user, 'isDeleted') && $user->isDeleted();
        $deactivated = method_exists($user, 'isDeactivated') && $user->isDeactivated();

        if (! $deleted && ! $deactivated) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $deleted
            ? 'This account has been deleted.'
            : 'This account has been deactivated. Contact support if you need access.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => ['email' => [$message]],
            ], 401);
        }

        return redirect()
            ->guest(route('login'))
            ->withErrors(['email' => $message]);
    }
}
