<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Auto-logout deleted accounts that still have a live session.
     * Deactivated artists/studios may still use the dashboard.
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
        if (! $deleted) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = 'This account has been deleted.';

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
