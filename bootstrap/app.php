<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(fn (Request $request) => authenticated_home_url($request->user()));

        $middleware->alias([
            'onboarding' => \App\Http\Middleware\CheckOnboarding::class,
            'artist' => \App\Http\Middleware\CheckArtist::class,
            'admin' => \App\Http\Middleware\CheckAdmin::class,
            'user' => \App\Http\Middleware\CheckUser::class,
            'client_password' => \App\Http\Middleware\RequireClientPasswordComplete::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $e instanceof HttpExceptionInterface || $e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has expired. Please refresh the page and try again.',
                ], 419);
            }

            $appUrl = rtrim((string) config('app.url'), '/');
            $previous = url()->previous();
            $current = url()->current();

            if (
                is_string($previous)
                && $previous !== ''
                && filter_var($previous, FILTER_VALIDATE_URL)
                && str_starts_with($previous, $appUrl)
                && $previous !== $current
            ) {
                return redirect()->to($previous)->with(
                    'status',
                    'Your session expired. Please submit the form again.'
                );
            }

            if ($request->user()) {
                return redirect()->to(authenticated_home_url($request->user()))->with(
                    'status',
                    'Your session expired. Please try again.'
                );
            }

            return redirect()->guest(route('login'))->with(
                'status',
                'Your session expired. Please sign in again.'
            );
        });
    })->create();
