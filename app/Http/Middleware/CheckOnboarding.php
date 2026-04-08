<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOnboarding
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // If user has not completed onboarding and is not already on the onboarding page
        if ($user->on_boarding !== 'yes' && ! $request->routeIs('onboarding.*')) {
            return redirect()->route('onboarding.index');
        }

        // Artists using studio payouts cannot access the app until studio approves.
        $userDetail = $user->userDetail;
        if (
            $user->on_boarding === 'yes' &&
            $user->role === 'artist' &&
            $userDetail &&
            $userDetail->payment_type === 'studio_account' &&
            in_array((string) $userDetail->payment_status, ['pending', 'rejected'], true) &&
            ! $request->routeIs('studio.payment.status')
        ) {
            return redirect()->route('studio.payment.status');
        }

        return $next($request);
    }
}
