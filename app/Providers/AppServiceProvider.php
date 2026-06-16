<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\ChatChannel;
use App\Models\User;
use App\Observers\BookingObserver;
use App\Services\StreamChatService;
use Illuminate\Support\Facades\Auth;
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
