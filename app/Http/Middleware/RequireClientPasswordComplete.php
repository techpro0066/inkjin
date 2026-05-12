<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireClientPasswordComplete
{
    /**
     * Block client users who still need to set a password (e.g. after first booking) from the rest of the app.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role !== 'user' || ! $user->must_set_password) {
            return $next($request);
        }

        $route = $request->route();
        $name = $route ? $route->getName() : null;
        $method = $request->method();

        $allowed = [
            'user.bookings.index' => ['GET', 'HEAD'],
            'user.password.booking-initial.store' => ['POST'],
            'logout' => ['POST'],
        ];

        if ($name && isset($allowed[$name]) && in_array($method, $allowed[$name], true)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Please set your account password on your bookings page before continuing.',
            ], 403);
        }

        return redirect()->route('user.bookings.index', ['set_password' => '1']);
    }
}
