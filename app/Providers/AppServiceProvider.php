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
            $request = request();
            if ($request->hasHeader('Host')) {
                URL::forceRootUrl($request->getSchemeAndHttpHost().rtrim($request->getBaseUrl(), '/'));
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
                $view->with('showPayoutSetupBanner', false);
                $view->with('payoutDashboardNotice', null);

                return;
            }

            $streamChat = app(StreamChatService::class);

            $view->with('chatBadgeEnabled', $streamChat->isConfigured()
                && ChatChannel::query()->forUser($user->id)->exists());

            $payoutDashboardNotice = null;
            if ($user->role === 'artist') {
                $userDetail = $user->userDetail;
                if ($userDetail?->relationLoaded('studio') !== true && ($userDetail?->payment_type ?? '') === 'studio_account') {
                    $userDetail?->loadMissing('studio');
                }
                $payoutDashboardNotice = app(\App\Services\ArtistPayoutService::class)
                    ->payoutDashboardNotice($userDetail);
            }

            $view->with('payoutDashboardNotice', $payoutDashboardNotice);
            $view->with('showPayoutSetupBanner', $payoutDashboardNotice !== null);
        });
    }
}
