<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Studio\OnboardingController;
use App\Models\Studio;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStudio
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->role !== 'studio') {
            abort(403, 'Access denied. Studio role required.');
        }

        // Already finished — onboarding links go straight to the dashboard.
        if (
            $user->on_boarding === 'yes'
            && $request->routeIs('studio.onboarding.*')
        ) {
            return redirect()->route('studio.dashboard');
        }

        // Signup studios must finish onboarding before dashboard/account access.
        // Invite studios can use the dashboard and complete onboarding later.
        if (
            $user->mustCompleteStudioOnboarding()
            && ! $request->routeIs('studio.onboarding.*')
            && ! $request->routeIs('logout')
        ) {
            $studio = Studio::query()->where('user_id', $user->id)->first();

            return redirect()->to(OnboardingController::nextIncompleteStepRoute($user, $studio));
        }

        return $next($request);
    }
}
