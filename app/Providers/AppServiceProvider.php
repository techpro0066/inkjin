<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\ChatChannel;
use App\Models\User;
use App\Observers\BookingObserver;
use App\Services\StreamChatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            $appUrl = rtrim((string) config('app.url'), '/');

            // Prefer configured APP_URL so ngrok HTTPS is not rewritten to http
            // when the proxy forwards to local PHP as plain HTTP.
            if ($appUrl !== '' && filter_var($appUrl, FILTER_VALIDATE_URL)) {
                URL::forceRootUrl($appUrl);

                if (str_starts_with($appUrl, 'https://')) {
                    URL::forceScheme('https');
                }
            } else {
                $request = request();
                if ($request->hasHeader('Host')) {
                    URL::forceRootUrl($request->getSchemeAndHttpHost().rtrim($request->getBaseUrl(), '/'));
                }
            }
        }

        Booking::observe(BookingObserver::class);

        View::composer([
            'layouts.user_dashboard_layout',
            'layouts.artist_dashboard_layout',
            'layouts.components.user_sidebar',
            'layouts.components.artist_sidebar',
        ], function ($view): void {
            $user = Auth::user();

            if (! $user instanceof User || ! in_array($user->role, ['user', 'artist'], true)) {
                $view->with('chatBadgeEnabled', false);

                return;
            }

            $streamChat = app(StreamChatService::class);

            $view->with('chatBadgeEnabled', $streamChat->isConfigured()
                && ChatChannel::query()->forUser($user->id)->exists());
        });
    }
}
