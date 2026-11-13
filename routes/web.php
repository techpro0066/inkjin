<?php

use App\Http\Controllers\VivaReturnController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InkJinController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Admin\BookingsController as AdminBookingsController;
use App\Http\Controllers\Admin\RequestsController as AdminRequestsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FinancialController as AdminFinancialController;
use App\Http\Controllers\Admin\StripeConnectedAccountsController as AdminStripeConnectedAccountsController;
use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\ConsentFormQuestionController;
use App\Http\Controllers\Admin\PlacementController;
use App\Http\Controllers\Admin\SizeController;
use App\Http\Controllers\Admin\StyleController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ReferralsController as AdminReferralsController;
use App\Http\Controllers\QuestionsController;

use App\Http\Controllers\UserController\BookingsController;
use App\Http\Controllers\UserController\ClientPasswordController;
use App\Http\Controllers\UserController\UserSettingsController;
use App\Http\Controllers\User\ChatController as UserChatController;
use App\Http\Controllers\BookingsController as ArtistBookingsController;
use App\Http\Controllers\Auth\PostBookingAccessController;

use App\Http\Controllers\ArtistCustomRequestsController;
use App\Http\Controllers\ArtistDashboardController;
use App\Http\Controllers\Artist\ChatController as ArtistChatController;
use App\Http\Controllers\Artist\ClientsController as ArtistClientsController;
use App\Http\Controllers\Artist\PaymentsController as ArtistPaymentsController;
use App\Http\Controllers\Artist\ReferEarnController as ArtistReferEarnController;
use App\Http\Controllers\Artist\ChangeStudioController;
use App\Http\Controllers\Artist\StudioSettingsController;
use App\Http\Controllers\Artist\OtherSettingsController;
use App\Http\Controllers\Api\ChatController as ApiChatController;
use App\Http\Controllers\CustomRequestController;
use App\Http\Controllers\RequestsController;
use App\Http\Controllers\StripeConnectDevController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->to(authenticated_home_url());
    }

    return redirect()->route('register');
});

// Onboarding routes (must be before other auth routes)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/studio', \App\Http\Controllers\Studio\DashboardController::class)
        ->middleware('studio')
        ->name('studio.dashboard');
    Route::post('/studio/welcome-seen', [\App\Http\Controllers\Studio\DashboardController::class, 'markWelcomeSeen'])
        ->middleware('studio')
        ->name('studio.welcome-seen');
    Route::post('/studio/guest-spot-invites/{guestSpot}/accept', [\App\Http\Controllers\GuestSpotStudioInviteAcceptController::class, 'acceptFromDashboard'])
        ->middleware('studio')
        ->whereNumber('guestSpot')
        ->name('studio.guest-spot-invite.accept');
    Route::post('/studio/guest-spot-invites/{guestSpot}/decline', [\App\Http\Controllers\GuestSpotStudioInviteAcceptController::class, 'declineFromDashboard'])
        ->middleware('studio')
        ->whereNumber('guestSpot')
        ->name('studio.guest-spot-invite.decline');
    Route::get('/studio/calendar', [\App\Http\Controllers\Studio\BookingsController::class, 'calendar'])
        ->middleware('studio')
        ->name('studio.calendar');
    Route::get('/studio/chat', [\App\Http\Controllers\Studio\ChatController::class, 'index'])
        ->middleware('studio')
        ->name('studio.chat.index');
    Route::get('/studio/bookings', [\App\Http\Controllers\Studio\BookingsController::class, 'index'])
        ->middleware('studio')
        ->name('studio.bookings');
    Route::get('/studio/bookings/requests', [\App\Http\Controllers\Studio\BookingsController::class, 'requests'])
        ->middleware('studio')
        ->name('studio.bookings.requests');
    Route::get('/studio/bookings/payment-links', [\App\Http\Controllers\Studio\BookingsController::class, 'paymentLinks'])
        ->middleware('studio')
        ->name('studio.bookings.payment-links');
    Route::get('/studio/bookings/payment-links/export', [\App\Http\Controllers\Studio\BookingsController::class, 'exportPaymentLinks'])
        ->middleware('studio')
        ->name('studio.bookings.payment-links.export');
    Route::post('/studio/bookings/payment-links/validate', [\App\Http\Controllers\Studio\BookingsController::class, 'validatePaymentLink'])
        ->middleware('studio')
        ->name('studio.bookings.payment-links.validate');
    Route::post('/studio/bookings/payment-links', [\App\Http\Controllers\Studio\BookingsController::class, 'storePaymentLink'])
        ->middleware('studio')
        ->name('studio.bookings.payment-links.store');
    Route::post('/studio/bookings/payment-links/{id}/expire', [\App\Http\Controllers\Studio\BookingsController::class, 'expirePaymentLink'])
        ->middleware('studio')
        ->whereNumber('id')
        ->name('studio.bookings.payment-links.expire');
    Route::post('/studio/bookings/requests/{studioRequest}/assign', [\App\Http\Controllers\Studio\BookingsController::class, 'assign'])
        ->middleware('studio')
        ->whereNumber('studioRequest')
        ->name('studio.bookings.requests.assign');
    Route::post('/studio/bookings/requests/{studioRequest}/withdraw', [\App\Http\Controllers\Studio\BookingsController::class, 'withdraw'])
        ->middleware('studio')
        ->whereNumber('studioRequest')
        ->name('studio.bookings.requests.withdraw');
    Route::get('/studio/artists', [\App\Http\Controllers\Studio\ArtistsController::class, 'index'])
        ->middleware('studio')
        ->name('studio.artists');
    Route::get('/studio/artists/join-requests', [\App\Http\Controllers\Studio\ArtistsController::class, 'joinRequests'])
        ->middleware('studio')
        ->name('studio.artists.join-requests');
    Route::get('/studio/artists/guests', [\App\Http\Controllers\Studio\ArtistsController::class, 'guests'])
        ->middleware('studio')
        ->name('studio.artists.guests');
    Route::get('/studio/artists/invites', [\App\Http\Controllers\Studio\ArtistsController::class, 'invites'])
        ->middleware('studio')
        ->name('studio.artists.invites');
    Route::post('/studio/artists/invites', [\App\Http\Controllers\Studio\ArtistsController::class, 'sendInvite'])
        ->middleware('studio')
        ->name('studio.artists.invites.send');
    Route::post('/studio/artists/invites/{invite}/resend', [\App\Http\Controllers\Studio\ArtistsController::class, 'resendInvite'])
        ->middleware('studio')
        ->whereNumber('invite')
        ->name('studio.artists.invites.resend');
    Route::post('/studio/artists/invites/{invite}/cancel', [\App\Http\Controllers\Studio\ArtistsController::class, 'cancelInvite'])
        ->middleware('studio')
        ->whereNumber('invite')
        ->name('studio.artists.invites.cancel');
    Route::post('/studio/artists/{userStudio}/remove', [\App\Http\Controllers\Studio\ArtistsController::class, 'remove'])
        ->middleware('studio')
        ->whereNumber('userStudio')
        ->name('studio.artists.remove');
    Route::post('/studio/artists/{userStudio}/accept', [\App\Http\Controllers\Studio\ArtistsController::class, 'accept'])
        ->middleware('studio')
        ->whereNumber('userStudio')
        ->name('studio.artists.accept');
    Route::post('/studio/artists/{userStudio}/decline', [\App\Http\Controllers\Studio\ArtistsController::class, 'decline'])
        ->middleware('studio')
        ->whereNumber('userStudio')
        ->name('studio.artists.decline');
    Route::post('/studio/artists/{userStudio}/accept-split', [\App\Http\Controllers\Studio\ArtistsController::class, 'acceptSplit'])
        ->middleware('studio')
        ->whereNumber('userStudio')
        ->name('studio.artists.accept-split');
    Route::post('/studio/artists/{userStudio}/decline-split', [\App\Http\Controllers\Studio\ArtistsController::class, 'declineSplit'])
        ->middleware('studio')
        ->whereNumber('userStudio')
        ->name('studio.artists.decline-split');
    Route::get('/studio/page', fn () => redirect()->route('studio.page.about'))
        ->middleware('studio')
        ->name('studio.page');
    Route::get('/studio/page/about', [\App\Http\Controllers\Studio\StudioPageController::class, 'about'])
        ->middleware('studio')
        ->name('studio.page.about');
    Route::post('/studio/page/about', [\App\Http\Controllers\Studio\StudioPageController::class, 'updateAbout'])
        ->middleware('studio')
        ->name('studio.page.about.update');
    Route::get('/studio/page/artists', [\App\Http\Controllers\Studio\StudioPageController::class, 'artists'])
        ->middleware('studio')
        ->name('studio.page.artists');
    Route::post('/studio/page/artists', [\App\Http\Controllers\Studio\StudioPageController::class, 'updateArtists'])
        ->middleware('studio')
        ->name('studio.page.artists.update');
    Route::get('/studio/page/portfolio', [\App\Http\Controllers\Studio\StudioPageController::class, 'portfolio'])
        ->middleware('studio')
        ->name('studio.page.portfolio');
    Route::post('/studio/page/portfolio', [\App\Http\Controllers\Studio\StudioPageController::class, 'updatePortfolio'])
        ->middleware('studio')
        ->name('studio.page.portfolio.update');
    Route::post('/studio/page/portfolio/pieces', [\App\Http\Controllers\Studio\StudioPageController::class, 'storePiece'])
        ->middleware('studio')
        ->name('studio.page.portfolio.piece.store');
    Route::put('/studio/page/portfolio/pieces/{piece}', [\App\Http\Controllers\Studio\StudioPageController::class, 'updatePiece'])
        ->middleware('studio')
        ->name('studio.page.portfolio.piece.update');
    Route::delete('/studio/page/portfolio/pieces/{piece}', [\App\Http\Controllers\Studio\StudioPageController::class, 'destroyPiece'])
        ->middleware('studio')
        ->name('studio.page.portfolio.piece.destroy');
    Route::post('/studio/page/portfolio/ai-suggest', [\App\Http\Controllers\Studio\StudioPageController::class, 'suggestWithAi'])
        ->middleware('studio')
        ->name('studio.page.portfolio.ai-suggest');
    Route::post('/studio/page/portfolio/instagram/refresh', [\App\Http\Controllers\Studio\StudioPageController::class, 'refreshInstagram'])
        ->middleware('studio')
        ->name('studio.page.portfolio.instagram.refresh');
    Route::post('/studio/page/portfolio/instagram/disconnect', [\App\Http\Controllers\Studio\StudioPageController::class, 'disconnectInstagram'])
        ->middleware('studio')
        ->name('studio.page.portfolio.instagram.disconnect');
    Route::get('/studio/page/look', [\App\Http\Controllers\Studio\StudioPageController::class, 'look'])
        ->middleware('studio')
        ->name('studio.page.look');
    Route::post('/studio/page/look', [\App\Http\Controllers\Studio\StudioPageController::class, 'updateLook'])
        ->middleware('studio')
        ->name('studio.page.look.update');
    Route::get('/studio/client-flow', fn () => redirect()->route('studio.client-flow.intake'))
        ->middleware('studio')
        ->name('studio.client-flow');
    Route::get('/studio/client-flow/intake', [\App\Http\Controllers\Studio\ClientFlowController::class, 'intake'])
        ->middleware('studio')
        ->name('studio.client-flow.intake');
    Route::get('/studio/client-flow/consent', [\App\Http\Controllers\Studio\ClientFlowController::class, 'consent'])
        ->middleware('studio')
        ->name('studio.client-flow.consent');
    Route::put('/studio/client-flow/consent/policy', [\App\Http\Controllers\Studio\ClientFlowController::class, 'updateConsentPolicy'])
        ->middleware('studio')
        ->name('studio.client-flow.consent.policy');
    Route::put('/studio/client-flow/consent/settings', [\App\Http\Controllers\Studio\ClientFlowController::class, 'updateConsentSettings'])
        ->middleware('studio')
        ->name('studio.client-flow.consent.settings');
    Route::post('/studio/client-flow/questions', [QuestionsController::class, 'store'])
        ->middleware('studio')
        ->name('studio.client-flow.questions.store');
    Route::put('/studio/client-flow/questions/{id}', [QuestionsController::class, 'update'])
        ->middleware('studio')
        ->name('studio.client-flow.questions.update');
    Route::patch('/studio/client-flow/questions/{id}/status', [QuestionsController::class, 'updateSystemQuestionStatus'])
        ->middleware('studio')
        ->name('studio.client-flow.questions.status');
    Route::post('/studio/client-flow/questions/reorder', [QuestionsController::class, 'reorder'])
        ->middleware('studio')
        ->name('studio.client-flow.questions.reorder');
    Route::delete('/studio/client-flow/questions/{id}', [QuestionsController::class, 'destroy'])
        ->middleware('studio')
        ->name('studio.client-flow.questions.destroy');
    Route::get('/studio/money', fn () => redirect()->route('studio.money.earnings'))
        ->middleware('studio')
        ->name('studio.money');
    Route::get('/studio/money/earnings', [\App\Http\Controllers\Studio\MoneyController::class, 'earnings'])
        ->middleware('studio')
        ->name('studio.money.earnings');
    Route::get('/studio/money/earnings/export', [\App\Http\Controllers\Studio\MoneyController::class, 'exportEarnings'])
        ->middleware('studio')
        ->name('studio.money.earnings.export');
    Route::get('/studio/money/payouts', [\App\Http\Controllers\Studio\MoneyController::class, 'payouts'])
        ->middleware('studio')
        ->name('studio.money.payouts');
    Route::post('/studio/money/payouts', [\App\Http\Controllers\Studio\MoneyController::class, 'updatePayouts'])
        ->middleware('studio')
        ->name('studio.money.payouts.update');
    Route::post('/studio/money/payouts/stripe/session', [\App\Http\Controllers\Studio\MoneyController::class, 'createStripeSession'])
        ->middleware('studio')
        ->name('studio.money.payouts.stripe.session');
    Route::get('/studio/money/payouts/stripe/status', [\App\Http\Controllers\Studio\MoneyController::class, 'stripeStatus'])
        ->middleware('studio')
        ->name('studio.money.payouts.stripe.status');
    Route::post('/studio/money/payouts/stripe/complete', [\App\Http\Controllers\Studio\MoneyController::class, 'completeStripe'])
        ->middleware('studio')
        ->name('studio.money.payouts.stripe.complete');
    Route::post('/studio/money/payouts/stripe/disconnect', [\App\Http\Controllers\Studio\MoneyController::class, 'disconnectStripe'])
        ->middleware('studio')
        ->name('studio.money.payouts.stripe.disconnect');
    Route::post('/studio/money/payouts/stripe/reactivate', [\App\Http\Controllers\Studio\MoneyController::class, 'reactivateStripe'])
        ->middleware('studio')
        ->name('studio.money.payouts.stripe.reactivate');
    Route::post('/studio/money/payouts/stripe/change-account', [\App\Http\Controllers\Studio\MoneyController::class, 'changePayoutAccount'])
        ->middleware('studio')
        ->name('studio.money.payouts.stripe.change-account');
    Route::post('/studio/money/payouts/stripe/finalize-change-account', [\App\Http\Controllers\Studio\MoneyController::class, 'finalizeChangePayoutAccount'])
        ->middleware('studio')
        ->name('studio.money.payouts.stripe.finalize-change-account');
    Route::middleware('studio')->prefix('studio/onboarding')->name('studio.onboarding.')->group(function () {
        Route::get('/', fn () => redirect()->route('studio.onboarding.profile'));
        Route::get('/profile', [\App\Http\Controllers\Studio\OnboardingController::class, 'profile'])->name('profile');
        Route::post('/profile', [\App\Http\Controllers\Studio\OnboardingController::class, 'updateProfile'])->name('profile.update');
        Route::get('/owner', [\App\Http\Controllers\Studio\OnboardingController::class, 'owner'])->name('owner');
        Route::post('/owner', [\App\Http\Controllers\Studio\OnboardingController::class, 'updateOwner'])->name('owner.update');
        Route::get('/location', [\App\Http\Controllers\Studio\OnboardingController::class, 'location'])->name('location');
        Route::post('/location', [\App\Http\Controllers\Studio\OnboardingController::class, 'updateLocation'])->name('location.update');
        Route::get('/terms', [\App\Http\Controllers\Studio\OnboardingController::class, 'terms'])->name('terms');
        Route::post('/terms', [\App\Http\Controllers\Studio\OnboardingController::class, 'updateTerms'])->name('terms.update');
        Route::get('/payouts', [\App\Http\Controllers\Studio\OnboardingController::class, 'payouts'])->name('payouts');
        Route::post('/payouts', [\App\Http\Controllers\Studio\OnboardingController::class, 'updatePayouts'])->name('payouts.update');
        Route::post('/payouts/stripe/session', [\App\Http\Controllers\Studio\OnboardingController::class, 'createStripeSession'])->name('payouts.stripe.session');
        Route::get('/payouts/stripe/status', [\App\Http\Controllers\Studio\OnboardingController::class, 'stripeStatus'])->name('payouts.stripe.status');
        Route::post('/payouts/stripe/complete', [\App\Http\Controllers\Studio\OnboardingController::class, 'completeStripe'])->name('payouts.stripe.complete');
        Route::get('/artists', [\App\Http\Controllers\Studio\OnboardingController::class, 'artists'])->name('artists');
        Route::post('/artists', [\App\Http\Controllers\Studio\OnboardingController::class, 'finishArtists'])->name('artists.finish');
    });
    Route::middleware('studio')->prefix('studio/account')->name('studio.account.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Studio\AccountController::class, 'profile'])->name('profile');
        Route::post('/', [\App\Http\Controllers\Studio\AccountController::class, 'updateProfile'])->name('profile.update');
        Route::get('/password', [\App\Http\Controllers\Studio\AccountController::class, 'password'])->name('password');
        Route::get('/studio', [\App\Http\Controllers\Studio\AccountController::class, 'studio'])->name('studio');
        Route::post('/studio', [\App\Http\Controllers\Studio\AccountController::class, 'updateStudio'])->name('studio.update');
        Route::get('/regional', [\App\Http\Controllers\Studio\AccountController::class, 'regional'])->name('regional');
        Route::post('/regional', [\App\Http\Controllers\Studio\AccountController::class, 'updateRegional'])->name('regional.update');
    });
    Route::redirect('/studio/coming-soon', '/studio')->name('studio.coming-soon');

    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::get('/onboarding/profile', [OnboardingController::class, 'profile'])->name('onboarding.profile');
    Route::get('/onboarding/styles-social', [OnboardingController::class, 'stylesSocial'])->name('onboarding.styles-social');
    Route::get('/onboarding/studio', [OnboardingController::class, 'studio'])->name('onboarding.studio');
    Route::get('/onboarding/preferences', [OnboardingController::class, 'preferences'])->name('onboarding.preferences');
    Route::get('/onboarding/calendar', [OnboardingController::class, 'calendar'])->name('onboarding.calendar');
    Route::get('/onboarding/payment', [OnboardingController::class, 'payment'])->name('onboarding.payment');
    Route::post('/onboarding/styles-social', [OnboardingController::class, 'saveStylesSocial'])->name('onboarding.styles-social.save');
    Route::post('/onboarding/profile', [OnboardingController::class, 'saveProfile'])->name('onboarding.profile.save');
    Route::post('/onboarding/studio', [OnboardingController::class, 'saveStudio'])->name('onboarding.studio.save');
    Route::post('/onboarding/studio/accept-invite', [OnboardingController::class, 'acceptStudioInvite'])->name('onboarding.studio.accept-invite');
    Route::post('/onboarding/studio/skip-invite', [OnboardingController::class, 'skipStudioInvite'])->name('onboarding.studio.skip-invite');
    Route::post('/onboarding/calendar', [OnboardingController::class, 'saveCalendar'])->name('onboarding.calendar.save');
    Route::post('/onboarding/preferences', [OnboardingController::class, 'savePreferences'])->name('onboarding.preferences.save');
    Route::post('/onboarding/preferences/country', [OnboardingController::class, 'savePreferencesCountry'])->name('onboarding.preferences.country');
    Route::post('/onboarding/payment/skip', [OnboardingController::class, 'skipPayment'])->name('onboarding.payment.skip');
    Route::post('/onboarding/payment/bank-country', [OnboardingController::class, 'savePayoutBankCountry'])->name('onboarding.payment.bank-country');
    Route::post('/onboarding/payment/waiting-list', [OnboardingController::class, 'savePayoutWaitingList'])->name('onboarding.payment.waiting-list');
    Route::get('/onboarding/payment/stripe/countries', [OnboardingController::class, 'stripePayoutCountries'])->name('onboarding.payment.stripe.countries');
    Route::post('/onboarding/payment/stripe/session', [OnboardingController::class, 'createStripeConnectSession'])->name('onboarding.payment.stripe.session');
    Route::get('/onboarding/payment/stripe/status', [OnboardingController::class, 'stripeConnectStatus'])->name('onboarding.payment.stripe.status');
    Route::post('/onboarding/payment', [OnboardingController::class, 'savePayment'])->name('onboarding.payment.save');
    Route::get('/onboarding/progress', [OnboardingController::class, 'getProgress'])->name('onboarding.progress');
    
    // Google Calendar OAuth routes
    Route::get('/auth/google-calendar', [\App\Http\Controllers\GoogleCalendarController::class, 'redirect'])->name('google.calendar.redirect');
    Route::get('/auth/google-calendar/callback', [\App\Http\Controllers\GoogleCalendarController::class, 'callback'])->name('google.calendar.callback');
    // Alias route for Google callback (in case Google Console is configured with /auth/google/callback)
    Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleCalendarController::class, 'callback'])->name('google.callback');
    Route::get('/auth/google-calendar/status', [\App\Http\Controllers\GoogleCalendarController::class, 'checkStatus'])->name('google.calendar.status');
    Route::post('/auth/google-calendar/disconnect', [\App\Http\Controllers\GoogleCalendarController::class, 'disconnect'])->name('google.calendar.disconnect');

    Route::get('/auth/instagram/connect', [\App\Http\Controllers\InstagramController::class, 'connect'])->name('instagram.connect');
    Route::get('/auth/instagram/callback', [\App\Http\Controllers\InstagramController::class, 'callback'])->name('instagram.callback');
    Route::post('/auth/instagram/disconnect', [\App\Http\Controllers\InstagramController::class, 'disconnect'])->name('instagram.disconnect');
    Route::post('/auth/instagram/import-portfolio', [\App\Http\Controllers\InstagramController::class, 'import'])->name('instagram.import-portfolio');
    Route::post('/auth/instagram/import-portfolio/start', [\App\Http\Controllers\InstagramController::class, 'importStart'])->name('instagram.import-portfolio.start');
    Route::post('/auth/instagram/import-portfolio/next', [\App\Http\Controllers\InstagramController::class, 'importNext'])->name('instagram.import-portfolio.next');

});

Route::post('/auth/instagram/deauthorize', [\App\Http\Controllers\InstagramController::class, 'deauthorize'])
    ->name('instagram.deauthorize');

Route::get('/invite/studio/{token}', [\App\Http\Controllers\Studio\StudioArtistInviteAcceptController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('studio.artist-invite.show');
Route::post('/invite/studio/{token}/accept', [\App\Http\Controllers\Studio\StudioArtistInviteAcceptController::class, 'accept'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('studio.artist-invite.accept');
Route::post('/invite/studio/{token}/decline', [\App\Http\Controllers\Studio\StudioArtistInviteAcceptController::class, 'decline'])
    ->middleware('throttle:30,1')
    ->name('studio.artist-invite.decline');

Route::get('/invite/guest-spot/{token}', [\App\Http\Controllers\GuestSpotStudioInviteAcceptController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('guest-spot.studio-invite.show');
Route::post('/invite/guest-spot/{token}/accept', [\App\Http\Controllers\GuestSpotStudioInviteAcceptController::class, 'accept'])
    ->middleware('throttle:30,1')
    ->name('guest-spot.studio-invite.accept');
Route::post('/invite/guest-spot/{token}/decline', [\App\Http\Controllers\GuestSpotStudioInviteAcceptController::class, 'decline'])
    ->middleware('throttle:30,1')
    ->name('guest-spot.studio-invite.decline');
Route::post('/invite/guest-spot/{token}/stripe/session', [\App\Http\Controllers\GuestSpotStudioInviteAcceptController::class, 'createStripeSession'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('guest-spot.studio-invite.stripe.session');
Route::post('/invite/guest-spot/{token}/stripe/complete', [\App\Http\Controllers\GuestSpotStudioInviteAcceptController::class, 'completeStripe'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('guest-spot.studio-invite.stripe.complete');

Route::get('/stripe/delete-account', [StripeConnectDevController::class, 'showDeleteForm'])
    ->name('stripe.delete-account.show');
Route::post('/stripe/delete-account', [StripeConnectDevController::class, 'deleteAccount'])
    ->name('stripe.delete-account.destroy');

Route::get('/studio/payout-info/{userDetail}', [OnboardingController::class, 'showStudioPayoutForm'])
    ->middleware('signed')
    ->name('studio.payout-info.show');
Route::post('/studio/payout-info/{userDetail}/accept', [OnboardingController::class, 'acceptStudioInvitation'])
    ->middleware('signed')
    ->name('studio.payout-info.accept');
Route::post('/studio/payout-info/{userDetail}/stripe/skip', [OnboardingController::class, 'skipStudioStripeSetup'])
    ->middleware('signed')
    ->name('studio.payout-info.stripe.skip');
Route::post('/studio/payout-info/{userDetail}/stripe/session', [OnboardingController::class, 'createStudioStripeSession'])
    ->middleware('signed')
    ->name('studio.payout-info.stripe.session');
Route::get('/studio/payout-info/{userDetail}/stripe/status', [OnboardingController::class, 'studioStripeStatus'])
    ->middleware('signed')
    ->name('studio.payout-info.stripe.status');
Route::post('/studio/payout-info/{userDetail}/stripe/complete', [OnboardingController::class, 'completeStudioStripeOnboarding'])
    ->middleware('signed')
    ->name('studio.payout-info.stripe.complete');
Route::get('/studio/payout-info/{userDetail}/stripe/requirements', [OnboardingController::class, 'showStudioStripeRequirements'])
    ->middleware('signed')
    ->name('studio.payout-info.stripe.requirements');
Route::post('/studio/payout-info/{userDetail}/stripe/requirements/session', [OnboardingController::class, 'createStudioStripeRequirementsSession'])
    ->middleware('signed')
    ->name('studio.payout-info.stripe.requirements.session');

Route::match(['get', 'post'], '/studio/payout-link/{userDetail}/approve', [OnboardingController::class, 'approveStudioArtistBankLink'])
    ->middleware('signed')
    ->name('studio.payout-artist-link.approve');
Route::match(['get', 'post'], '/studio/payout-link/{userDetail}/decline', [OnboardingController::class, 'declineStudioArtistBankLink'])
    ->middleware('signed')
    ->name('studio.payout-artist-link.decline');

Route::get('/feedback', function () {
    return view('public.feedback', [
        'demo' => true,
        'mode' => 'form',
    ]);
})->name('public.feedback');

Route::get('/session-feedback/{review}', [\App\Http\Controllers\SessionFeedbackController::class, 'show'])
    ->middleware(['signed', 'throttle:60,1'])
    ->whereNumber('review')
    ->name('public.session-feedback');

Route::post('/session-feedback/{review}', [\App\Http\Controllers\SessionFeedbackController::class, 'store'])
    ->middleware(['signed', 'throttle:30,1'])
    ->whereNumber('review')
    ->name('public.session-feedback.store');

Route::get('/artist-session-feedback/{feedback}', [\App\Http\Controllers\ArtistClientFeedbackController::class, 'show'])
    ->middleware(['signed', 'throttle:60,1'])
    ->whereNumber('feedback')
    ->name('public.artist-client-feedback');

Route::post('/artist-session-feedback/{feedback}', [\App\Http\Controllers\ArtistClientFeedbackController::class, 'store'])
    ->middleware(['signed', 'throttle:30,1'])
    ->whereNumber('feedback')
    ->name('public.artist-client-feedback.store');

Route::get('/artist-feedback-demo', function () {
    return view('public.artist-client-feedback', [
        'demo' => true,
        'mode' => 'form',
    ]);
})->name('public.artist-client-feedback.demo');

Route::get('/user/access-from-booking/{user}/{booking}', PostBookingAccessController::class)
    ->middleware(['signed', 'throttle:20,1'])
    ->name('user.post-booking.access');

Route::get('/user/access-from-request/{user}/{bookingRequest}', \App\Http\Controllers\Auth\PostManagedRequestAccessController::class)
    ->middleware(['signed', 'throttle:20,1'])
    ->name('user.post-managed-request.access');

Route::get('/user/access-from-custom-request/{user}/{customRequest}', \App\Http\Controllers\Auth\PostCustomRequestAccessController::class)
    ->middleware(['signed', 'throttle:20,1'])
    ->name('user.post-custom-request.access');

//Common routes

Route::middleware(['auth', 'verified', 'onboarding', 'client_password'])->group(function () {
    Route::get('/studio/payment/status', [OnboardingController::class, 'studioPaymentStatus'])->name('studio.payment.status');

    // Dashboard route
    // Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    // Bookings route (for all authenticated users)
    // Route::get('/bookings', [\App\Http\Controllers\BookingsController::class, 'index'])->name('bookings.index');
    
    // Booking cancellation routes
    Route::get('/api/bookings/{id}/cancellation-info', [\App\Http\Controllers\BookingCancellationController::class, 'getCancellationInfo'])->name('api.bookings.cancellation-info');
    Route::post('/api/bookings/{id}/cancel', [\App\Http\Controllers\BookingCancellationController::class, 'cancel'])->name('api.bookings.cancel');
    Route::post('/api/bookings/{id}/mark-no-show', [\App\Http\Controllers\BookingCancellationController::class, 'markNoShow'])->name('api.bookings.mark-no-show');
    Route::post('/api/bookings/{id}/send-completion-code', [\App\Http\Controllers\BookingsController::class, 'sendCompletionCode'])->name('api.bookings.send-completion-code');
    Route::get('/api/bookings/live-status', [\App\Http\Controllers\BookingsController::class, 'liveStatus'])->name('api.bookings.live-status');
    Route::post('/api/bookings/{id}/mark-completed', [\App\Http\Controllers\BookingsController::class, 'markCompleted'])->name('api.bookings.mark-completed');
    Route::get('/api/bookings/{id}/aftercare-preview', [\App\Http\Controllers\ArtistAftercareController::class, 'preview'])->name('api.bookings.aftercare-preview');
    Route::post('/api/bookings/{id}/aftercare-send', [\App\Http\Controllers\ArtistAftercareController::class, 'send'])->name('api.bookings.aftercare-send');
    Route::post('/api/bookings/{id}/balance-collections', [\App\Http\Controllers\BookingsController::class, 'storeBalanceCollection'])->name('api.bookings.balance-collections.store');
    Route::post('/api/bookings/{id}/consent/resend', [\App\Http\Controllers\BookingsController::class, 'resendConsent'])->name('api.bookings.consent.resend');
    Route::get('/api/bookings/{id}/consent/link', [\App\Http\Controllers\BookingsController::class, 'consentLink'])->name('api.bookings.consent.link');
    Route::post('/api/bookings/{id}/consent/age-verified', [\App\Http\Controllers\BookingsController::class, 'updateConsentAgeVerified'])->name('api.bookings.consent.age-verified');
    Route::post('/api/bookings/{id}/consent/guardian', [\App\Http\Controllers\BookingsController::class, 'updateConsentGuardian'])->name('api.bookings.consent.guardian');
    
    // Booking rescheduling routes
    Route::get('/api/bookings/{id}/can-reschedule', [\App\Http\Controllers\ReschedulingController::class, 'checkCanReschedule'])->name('api.bookings.can-reschedule');
    Route::post('/api/bookings/{id}/artist-request-reschedule', [\App\Http\Controllers\ReschedulingController::class, 'artistRequestReschedule'])->name('api.bookings.artist-request-reschedule');
    Route::post('/api/bookings/{id}/reschedule', [\App\Http\Controllers\ReschedulingController::class, 'reschedule'])->name('api.bookings.reschedule');
    Route::post('/api/bookings/{id}/decline-reschedule', [\App\Http\Controllers\ReschedulingController::class, 'declineReschedule'])->name('api.bookings.decline-reschedule');
    Route::get('/bookings/{id}/reschedule', [\App\Http\Controllers\ReschedulingController::class, 'showReschedulePage'])->name('bookings.reschedule');
    Route::get('/bookings/{id}/reschedule-flow', [\App\Http\Controllers\ReschedulingController::class, 'showRescheduleFlow'])->name('bookings.reschedule-flow');
    
});

Route::middleware(['auth', 'verified', 'onboarding'])->prefix('api/chat')->group(function () {
    Route::get('/token', [ApiChatController::class, 'token'])->name('api.chat.token');
    Route::get('/channels', [ApiChatController::class, 'channels'])->name('api.chat.channels');
    Route::post('/channels/artist/{artistUserId}/ensure', [ApiChatController::class, 'ensureForArtist'])->name('api.chat.ensure.artist');
    Route::post('/channels/client/{clientUserId}/ensure', [ApiChatController::class, 'ensureForClient'])->name('api.chat.ensure.client');
    Route::post('/channels/studio-artist/{artistUserId}/ensure', [ApiChatController::class, 'ensureStudioForArtist'])->name('api.chat.ensure.studio-artist');
    Route::post('/channels/studio/{studioUserId}/ensure', [ApiChatController::class, 'ensureStudioForStudio'])->name('api.chat.ensure.studio');
    Route::get('/can-send/{streamChannelId}', [ApiChatController::class, 'canSend'])->name('api.chat.can-send');
    Route::get('/unread-summary', [ApiChatController::class, 'unreadSummary'])->name('api.chat.unread-summary');
});

// Profile routes (accessible even if email not verified, so user can update email)
Route::middleware(['auth', 'onboarding', 'client_password'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// Admin routes
Route::middleware(['auth', 'verified', 'onboarding', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/bookings', [AdminBookingsController::class, 'index'])->name('admin.bookings.index');
    Route::get('/bookings/{booking}', [AdminBookingsController::class, 'show'])->name('admin.bookings.show');
    Route::get('/requests', [AdminRequestsController::class, 'index'])->name('admin.requests.index');
    Route::get('/requests/flash/{bookingRequest}', [AdminRequestsController::class, 'showFlash'])->name('admin.requests.flash.show');
    Route::get('/requests/custom/{customRequest}', [AdminRequestsController::class, 'showCustom'])->name('admin.requests.custom.show');
    Route::get('/settings', [AdminSettingsController::class, 'password'])->name('admin.settings');
    Route::get('/settings/password', [AdminSettingsController::class, 'password'])->name('admin.settings.password');
    Route::get('/revenue', [AdminFinancialController::class, 'revenue'])->name('admin.revenue.index');
    Route::get('/fees', [AdminFinancialController::class, 'fees'])->name('admin.fees.index');
    Route::get('/payouts', [AdminFinancialController::class, 'payouts'])->name('admin.payouts.index');
    Route::get('/stripe-accounts', [AdminStripeConnectedAccountsController::class, 'index'])->name('admin.stripe-accounts.index');

    Route::get('/forms', [FormController::class, 'index'])->name('admin.forms.index');
    Route::post('/forms/questions', [QuestionsController::class, 'store'])->name('admin.forms.questions.store');
    Route::put('/forms/questions/{id}', [QuestionsController::class, 'update'])->name('admin.forms.questions.update');
    Route::post('/forms/questions/reorder', [QuestionsController::class, 'reorder'])->name('admin.forms.questions.reorder');
    Route::delete('/forms/questions/{id}', [QuestionsController::class, 'destroy'])->name('admin.forms.questions.destroy');
    Route::post('/forms/consent-questions', [ConsentFormQuestionController::class, 'store'])->name('admin.forms.consent-questions.store');
    Route::put('/forms/consent-questions/{id}', [ConsentFormQuestionController::class, 'update'])->name('admin.forms.consent-questions.update');
    Route::patch('/forms/consent-questions/{id}/status', [ConsentFormQuestionController::class, 'updateStatus'])->name('admin.forms.consent-questions.status');
    Route::delete('/forms/consent-questions/{id}', [ConsentFormQuestionController::class, 'destroy'])->name('admin.forms.consent-questions.destroy');

    Route::get('/styles', [StyleController::class, 'index'])->name('admin.styles.index');
    Route::post('/styles', [StyleController::class, 'store'])->name('admin.styles.store');
    Route::put('/styles/{style}', [StyleController::class, 'update'])->name('admin.styles.update');
    Route::delete('/styles/{style}', [StyleController::class, 'destroy'])->name('admin.styles.destroy');
    Route::post('/styles/reorder', [StyleController::class, 'reorder'])->name('admin.styles.reorder');

    Route::get('/placements', [PlacementController::class, 'index'])->name('admin.placements.index');
    Route::post('/placements', [PlacementController::class, 'store'])->name('admin.placements.store');
    Route::put('/placements/{placement}', [PlacementController::class, 'update'])->name('admin.placements.update');
    Route::delete('/placements/{placement}', [PlacementController::class, 'destroy'])->name('admin.placements.destroy');
    Route::post('/placements/reorder', [PlacementController::class, 'reorder'])->name('admin.placements.reorder');

    Route::get('/sizes', [SizeController::class, 'index'])->name('admin.sizes.index');
    Route::post('/sizes', [SizeController::class, 'store'])->name('admin.sizes.store');
    Route::put('/sizes/{size}', [SizeController::class, 'update'])->name('admin.sizes.update');
    Route::delete('/sizes/{size}', [SizeController::class, 'destroy'])->name('admin.sizes.destroy');
    Route::post('/sizes/reorder', [SizeController::class, 'reorder'])->name('admin.sizes.reorder');

    // Route::get('/questions', [\App\Http\Controllers\Admin\QuestionController::class, 'index'])->name('admin.questions.index');
    // Route::post('/questions', [\App\Http\Controllers\Admin\QuestionController::class, 'store'])->name('admin.questions.store');
    // Route::put('/questions/{id}', [\App\Http\Controllers\Admin\QuestionController::class, 'update'])->name('admin.questions.update');
    // Route::delete('/questions/{id}', [\App\Http\Controllers\Admin\QuestionController::class, 'destroy'])->name('admin.questions.destroy');
    
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/export', [AdminUserController::class, 'export'])->name('admin.users.export');
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::get('/referrals', [AdminReferralsController::class, 'index'])->name('admin.referrals.index');
    Route::post('/referrals/{referral}/send-reward', [AdminReferralsController::class, 'sendReward'])->name('admin.referrals.send-reward');
    Route::post('/referrals/{referral}/reject', [AdminReferralsController::class, 'reject'])->name('admin.referrals.reject');
});

// Artist routes
Route::middleware(['auth', 'verified', 'onboarding', 'artist'])->prefix('artist')->group(function () {
    
    Route::get('/dashboard', [ArtistDashboardController::class, 'index'])->name('artist.dashboard');
    Route::post('/welcome-seen', [ArtistDashboardController::class, 'markWelcomeSeen'])->name('artist.welcome-seen');
    Route::post('/instagram-bio-added', [ArtistDashboardController::class, 'markInstagramBioAdded'])->name('artist.instagram-bio-added');
    Route::post('/payment-link/validate', [ArtistDashboardController::class, 'validatePaymentLink'])->name('artist.payment-link.validate');
    Route::post('/payment-link', [ArtistDashboardController::class, 'storePaymentLink'])->name('artist.payment-link.store');

    // Settings routes

    // Legacy styles settings URL → My Page (styles edited there now)
    Route::get('/settings/styles', function () {
        return redirect()->route('personal-page.index');
    })->name('settings.styles');

    Route::post('/settings/styles', [OnboardingController::class, 'updateStylesSocial'])->name('settings.styles.update');

    Route::get('/settings/studio', [StudioSettingsController::class, 'show'])->name('settings.studio');

    Route::get('/settings/studio/change', [ChangeStudioController::class, 'show'])->name('settings.studio.change');
    Route::get('/settings/studio/change/search', [ChangeStudioController::class, 'search'])->name('settings.studio.change.search');
    Route::post('/settings/studio/change', [ChangeStudioController::class, 'store'])->name('settings.studio.change.store');

    Route::post('/settings/studio', [StudioSettingsController::class, 'update'])->name('settings.studio.update');
    Route::post('/settings/studio/cancel-join', [StudioSettingsController::class, 'cancelJoinRequest'])->name('settings.studio.cancel-join');
    Route::post('/settings/studio/leave', [StudioSettingsController::class, 'leaveStudio'])->name('settings.studio.leave');
    Route::post('/settings/studio/resend-join', [StudioSettingsController::class, 'resendJoinRequest'])->name('settings.studio.resend-join');
    Route::post('/settings/studio/accept-split', [StudioSettingsController::class, 'acceptSplit'])->name('settings.studio.accept-split');
    Route::post('/settings/studio/decline-split', [StudioSettingsController::class, 'declineSplit'])->name('settings.studio.decline-split');
    Route::post('/settings/studio/invites/{invite}/accept', [StudioSettingsController::class, 'acceptInvite'])->name('settings.studio.accept-invite');
    Route::post('/settings/studio/invites/{invite}/decline', [StudioSettingsController::class, 'declineInvite'])->name('settings.studio.decline-invite');
    
    // Legacy settings calendar URL → Schedule → Calendar sync
    Route::get('/settings/calendar', function () {
        return redirect()->route('schedule.calendar-sync');
    })->name('settings.calendar');

    Route::post('/settings/calendar', [OnboardingController::class, 'updateCalendar'])->name('settings.calendar.update');

    // Schedule → Calendar sync (03c)
    Route::get('/schedule/calendar-sync', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail ?? \App\Models\UserDetail::create(['user_id' => $user->id]);

        return view('artist.schedule.calendar-sync', compact('userDetail'));
    })->name('schedule.calendar-sync');
    Route::post('/schedule/calendar-sync', [OnboardingController::class, 'updateCalendarSync'])->name('schedule.calendar-sync.update');

    // Schedule → Booking rules (03d)
    Route::get('/schedule/booking-rules', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail ?? \App\Models\UserDetail::create(['user_id' => $user->id]);

        return view('artist.schedule.booking-rules', compact('userDetail'));
    })->name('schedule.booking-rules');
    Route::post('/schedule/booking-rules', [OnboardingController::class, 'updateBookingRules'])->name('schedule.booking-rules.update');
    
    Route::get('/settings/payments', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail;
        return view('artist.settings.preferences', compact('userDetail'));
    })->name('settings.preferences');
    
    Route::post('/settings/payments', [OnboardingController::class, 'savePreferences'])->name('settings.preferences.update');

    // Legacy /settings/preferences URLs → /settings/payments
    Route::redirect('/settings/preferences', '/settings/payments', 301);

    Route::get('/settings/payouts', [OnboardingController::class, 'paymentSettings'])->name('settings.payment');
    Route::post('/settings/payouts', [OnboardingController::class, 'updatePayment'])->name('settings.payment.update');
    Route::post('/settings/payouts/propose-split', [\App\Http\Controllers\Artist\RevenueSplitController::class, 'propose'])
        ->name('settings.payment.propose-split');
    Route::post('/settings/payouts/bank-country', [OnboardingController::class, 'savePayoutBankCountry'])->name('settings.payment.bank-country');
    Route::post('/settings/payouts/change-account', [OnboardingController::class, 'changePayoutAccount'])->name('settings.payment.change-account');
    Route::post('/settings/payouts/finalize-change-account', [OnboardingController::class, 'finalizeChangePayoutAccount'])->name('settings.payment.finalize-change-account');
    Route::post('/settings/payouts/activate-account', [OnboardingController::class, 'activatePayoutAccount'])->name('settings.payment.activate-account');
    Route::post('/settings/payouts/waiting-list', [OnboardingController::class, 'savePayoutWaitingList'])->name('settings.payment.waiting-list');
    Route::get('/settings/payouts/stripe/requirements', [OnboardingController::class, 'stripeRequirements'])->name('settings.payment.stripe.requirements');
    Route::post('/settings/payouts/stripe/session', [OnboardingController::class, 'createStripeConnectSession'])->name('settings.payment.stripe.session');
    Route::get('/settings/payouts/stripe/status', [OnboardingController::class, 'stripeConnectStatus'])->name('settings.payment.stripe.status');

    // Legacy /settings/payment URLs → /settings/payouts
    Route::redirect('/settings/payment', '/settings/payouts', 301);
    Route::redirect('/settings/payment/stripe/requirements', '/settings/payouts/stripe/requirements', 301);
    Route::redirect('/settings/other', '/settings/regional', 301);
    Route::get('/settings/regional', [OtherSettingsController::class, 'edit'])->name('settings.regional');
    Route::post('/settings/regional', [OtherSettingsController::class, 'update'])->name('settings.regional.update');

    // Availability routes (for artists)
    Route::get('/availability', [\App\Http\Controllers\AvailabilityController::class, 'index'])->name('availability.index');
    Route::post('/availability/booking-status', [\App\Http\Controllers\AvailabilityController::class, 'saveBookingStatus'])->name('availability.booking-status');
    Route::post('/availability', [\App\Http\Controllers\AvailabilityController::class, 'store'])->name('availability.store');
    Route::delete('/availability/{id}', [\App\Http\Controllers\AvailabilityController::class, 'destroy'])->name('availability.destroy');
    
    // Availability override routes
    Route::post('/availability/override', [\App\Http\Controllers\AvailabilityController::class, 'storeOverride'])->name('availability.override.store');
    Route::get('/availability/override', [\App\Http\Controllers\AvailabilityController::class, 'getOverride'])->name('availability.override.get');
    Route::delete('/availability/override/{id}', [\App\Http\Controllers\AvailabilityController::class, 'destroyOverride'])->name('availability.override.destroy');
    
    // Content
    Route::get('/personal-page', [\App\Http\Controllers\PersonalPageController::class, 'index'])->name('personal-page.index');
    Route::post('/personal-page', [\App\Http\Controllers\PersonalPageController::class, 'update'])->name('personal-page.update');
    Route::get('/policies', [\App\Http\Controllers\PersonalPageController::class, 'policies'])->name('personal-page.policies');
    Route::post('/policies', [\App\Http\Controllers\PersonalPageController::class, 'updatePolicies'])->name('personal-page.policies.update');
    Route::get('/appearance', [\App\Http\Controllers\PersonalPageController::class, 'appearance'])->name('personal-page.appearance');
    Route::post('/appearance', [\App\Http\Controllers\PersonalPageController::class, 'updateAppearanceLook'])->name('personal-page.appearance.update');
    Route::post('/personal-page/client-error', [\App\Http\Controllers\PersonalPageController::class, 'logClientError'])->name('personal-page.client-error');
    Route::post('/personal-page/display-policies', [\App\Http\Controllers\PersonalPageController::class, 'updateDisplayPolicies'])->name('personal-page.display-policies');
    Route::post('/personal-page/display-guest-spots', [\App\Http\Controllers\PersonalPageController::class, 'updateDisplayGuestSpots'])->name('personal-page.display-guest-spots');
    Route::post('/personal-page/display-faq', [\App\Http\Controllers\PersonalPageController::class, 'updateDisplayFaq'])->name('personal-page.display-faq');
    Route::post('/personal-page/display-portfolio', [\App\Http\Controllers\PersonalPageController::class, 'updateDisplayPortfolio'])->name('personal-page.display-portfolio');
    Route::post('/personal-page/display-designs', [\App\Http\Controllers\PersonalPageController::class, 'updateDisplayDesigns'])->name('personal-page.display-designs');
    Route::post('/personal-page/profile-content-visibility', [\App\Http\Controllers\PersonalPageController::class, 'updateProfileContentVisibility'])->name('personal-page.profile-content-visibility');

    Route::get('/aftercare', [\App\Http\Controllers\ArtistAftercareController::class, 'index'])->name('artist.aftercare.index');
    Route::put('/aftercare', [\App\Http\Controllers\ArtistAftercareController::class, 'update'])->name('artist.aftercare.update');

    Route::get('/portfolio', [\App\Http\Controllers\PortfolioController::class, 'index'])->name('portfolio.index');
    Route::post('/portfolio', [\App\Http\Controllers\PortfolioController::class, 'store'])->name('portfolio.store');
    Route::post('/portfolio/ai-suggest', [\App\Http\Controllers\PortfolioController::class, 'suggestWithAi'])->name('portfolio.ai-suggest');
    Route::put('/portfolio/{portfolio}', [\App\Http\Controllers\PortfolioController::class, 'update'])->name('portfolio.update');
    Route::patch('/portfolio/{portfolio}/visibility', [\App\Http\Controllers\PortfolioController::class, 'toggleVisibility'])->name('portfolio.toggle-visibility');
    Route::post('/portfolio/reorder', [\App\Http\Controllers\PortfolioController::class, 'reorder'])->name('portfolio.reorder');
    Route::delete('/portfolio/{portfolio}', [\App\Http\Controllers\PortfolioController::class, 'destroy'])->name('portfolio.destroy');

    Route::get('/guest-spots', [\App\Http\Controllers\GuestSpotsController::class, 'index'])->name('guest-spots.index');
    Route::post('/guest-spots', [\App\Http\Controllers\GuestSpotsController::class, 'store'])->name('guest-spots.store');
    Route::post('/guest-spots/reorder', [\App\Http\Controllers\GuestSpotsController::class, 'reorder'])->name('guest-spots.reorder');
    Route::put('/guest-spots/{guestSpot}', [\App\Http\Controllers\GuestSpotsController::class, 'update'])->name('guest-spots.update');
    Route::delete('/guest-spots/{guestSpot}', [\App\Http\Controllers\GuestSpotsController::class, 'destroy'])->name('guest-spots.destroy');

    Route::get('/faq', [\App\Http\Controllers\ArtistFaqController::class, 'index'])->name('artist.faq.index');
    Route::post('/faq', [\App\Http\Controllers\ArtistFaqController::class, 'store'])->name('artist.faq.store');
    Route::post('/faq/reorder', [\App\Http\Controllers\ArtistFaqController::class, 'reorder'])->name('artist.faq.reorder');
    Route::put('/faq/{faq}', [\App\Http\Controllers\ArtistFaqController::class, 'update'])->name('artist.faq.update');
    Route::delete('/faq/{faq}', [\App\Http\Controllers\ArtistFaqController::class, 'destroy'])->name('artist.faq.destroy');

    Route::get('/artist-designs', [\App\Http\Controllers\ArtistDesignsController::class, 'index'])->name('artist-designs.index');
    Route::post('/artist-designs', [\App\Http\Controllers\ArtistDesignsController::class, 'store'])->name('artist-designs.store');
    Route::post('/artist-designs/ai-suggest', [\App\Http\Controllers\ArtistDesignsController::class, 'suggestWithAi'])->name('artist-designs.ai-suggest');
    Route::post('/artist-designs/reorder', [\App\Http\Controllers\ArtistDesignsController::class, 'reorder'])->name('artist-designs.reorder');
    Route::put('/artist-designs/settings/whats-included', [\App\Http\Controllers\ArtistDesignsController::class, 'updateWhatsIncluded'])->name('artist-designs.whats-included.update');
    Route::put('/artist-designs/settings/pricing-type', [\App\Http\Controllers\ArtistDesignsController::class, 'updatePricingType'])->name('artist-designs.pricing-type.update');
    Route::post('/artist-designs/settings/smart-pricing/validate', [\App\Http\Controllers\ArtistDesignsController::class, 'validateSmartPricing'])->name('artist-designs.smart-pricing.validate');
    Route::post('/artist-designs/settings/smart-pricing', [\App\Http\Controllers\ArtistDesignsController::class, 'validateSmartPricing'])->name('artist-designs.smart-pricing.update');
    Route::put('/artist-designs/{artistDesign}', [\App\Http\Controllers\ArtistDesignsController::class, 'update'])->name('artist-designs.update');
    Route::patch('/artist-designs/{artistDesign}/availability', [\App\Http\Controllers\ArtistDesignsController::class, 'toggleAvailability'])->name('artist-designs.toggle-availability');
    Route::patch('/artist-designs/{artistDesign}/visibility', [\App\Http\Controllers\ArtistDesignsController::class, 'toggleVisibility'])->name('artist-designs.toggle-visibility');
    Route::delete('/artist-designs/{artistDesign}', [\App\Http\Controllers\ArtistDesignsController::class, 'destroy'])->name('artist-designs.destroy');

    Route::get('/forms', [QuestionsController::class, 'index'])->name('artist.forms.index');
    Route::get('/forms/consent', [\App\Http\Controllers\ConsentFormController::class, 'index'])->name('artist.forms.consent');
    Route::put('/forms/consent/settings', [\App\Http\Controllers\ConsentFormController::class, 'updateSettings'])->name('artist.forms.consent.settings');
    Route::post('/forms/consent-questions', [\App\Http\Controllers\ArtistConsentFormQuestionController::class, 'store'])->name('artist.forms.consent-questions.store');
    Route::put('/forms/consent-questions/{id}', [\App\Http\Controllers\ArtistConsentFormQuestionController::class, 'update'])->name('artist.forms.consent-questions.update');
    Route::patch('/forms/consent-questions/{id}/status', [\App\Http\Controllers\ArtistConsentFormQuestionController::class, 'updateStatus'])->name('artist.forms.consent-questions.status');
    Route::post('/forms/consent-questions/reorder', [\App\Http\Controllers\ArtistConsentFormQuestionController::class, 'reorder'])->name('artist.forms.consent-questions.reorder');
    Route::delete('/forms/consent-questions/{id}', [\App\Http\Controllers\ArtistConsentFormQuestionController::class, 'destroy'])->name('artist.forms.consent-questions.destroy');
    Route::post('/forms/questions', [QuestionsController::class, 'store'])->name('artist.forms.questions.store');
    Route::put('/forms/questions/{id}', [QuestionsController::class, 'update'])->name('artist.forms.questions.update');
    Route::patch('/forms/questions/{id}/status', [QuestionsController::class, 'updateSystemQuestionStatus'])->name('artist.forms.questions.status');
    Route::post('/forms/questions/reorder', [QuestionsController::class, 'reorder'])->name('artist.forms.questions.reorder');
    Route::delete('/forms/questions/{id}', [QuestionsController::class, 'destroy'])->name('artist.forms.questions.destroy');

    // Booking routes
    Route::get('/bookings', [ArtistBookingsController::class, 'index'])->name('artist.bookings.index');
    Route::get('/bookings/{booking}/detail-data', [ArtistBookingsController::class, 'detailData'])
        ->whereNumber('booking')
        ->name('artist.bookings.detail-data');
    Route::get('/bookings/payment-links', [ArtistBookingsController::class, 'paymentLinks'])->name('artist.bookings.payment-links');
    Route::post('/bookings/payment-links/{source}/{id}/expire', [ArtistBookingsController::class, 'expirePaymentLink'])
        ->whereIn('source', ['standalone', 'booking'])
        ->whereNumber('id')
        ->name('artist.bookings.payment-links.expire');

    // Requests
    Route::get('/requests', [RequestsController::class, 'index'])->name('artist.requests.index');
    Route::get('/requests/{bookingRequest}/panel-data', [RequestsController::class, 'designPanelData'])
        ->whereNumber('bookingRequest')
        ->name('artist.requests.panel-data');
    Route::get('/custom-requests', [ArtistCustomRequestsController::class, 'index'])->name('artist.custom-requests.index');
    Route::get('/guest-requests', [ArtistCustomRequestsController::class, 'index'])->name('artist.guest-requests.index');
    Route::get('/custom-requests/{customRequest}/panel-data', [RequestsController::class, 'customPanelData'])
        ->whereNumber('customRequest')
        ->name('artist.custom-requests.panel-data');
    Route::post('/custom-requests/{customRequest}/decline', [ArtistCustomRequestsController::class, 'decline'])->name('artist.custom-requests.decline');
    Route::post('/custom-requests/{customRequest}/send-quote', [ArtistCustomRequestsController::class, 'sendQuote'])->name('artist.custom-requests.send-quote');
    Route::post('/studio-offers/{studioRequest}/send-quote', [\App\Http\Controllers\ArtistStudioOffersController::class, 'sendQuote'])
        ->whereNumber('studioRequest')
        ->name('artist.studio-offers.send-quote');
    Route::post('/studio-offers/{studioRequest}/send-back', [\App\Http\Controllers\ArtistStudioOffersController::class, 'sendBack'])
        ->whereNumber('studioRequest')
        ->name('artist.studio-offers.send-back');
    Route::post('/requests/{bookingRequest}/decline', [RequestsController::class, 'decline'])->name('artist.requests.decline');
    Route::post('/requests/{bookingRequest}/offer-slots', [RequestsController::class, 'offerSlots'])->name('artist.requests.offer-slots');

    Route::get('/chat', [ArtistChatController::class, 'index'])->name('artist.chat.index');
    Route::get('/nav-badges', [\App\Http\Controllers\Artist\NavBadgesController::class, 'show'])->name('artist.nav-badges');
    Route::get('/clients', [ArtistClientsController::class, 'index'])->name('artist.clients.index');
    Route::post('/clients/waitlist/notify', [ArtistClientsController::class, 'notifyWaitlist'])->name('artist.clients.waitlist.notify');
    Route::put('/clients/waitlist/signup-mode', [ArtistClientsController::class, 'updateWaitlistSignup'])->name('artist.clients.waitlist.signup-mode');
    Route::put('/clients/guest-waitlist/auto-notify', [ArtistClientsController::class, 'updateGuestWaitlistAutoNotify'])->name('artist.clients.guest-waitlist.auto-notify');
    Route::post('/clients/guest-waitlist/notify', [ArtistClientsController::class, 'notifyGuestWaitlist'])->name('artist.clients.guest-waitlist.notify');
    Route::get('/refer-earn', [ArtistReferEarnController::class, 'index'])->name('artist.refer-earn.index');
    Route::get('/payments', [ArtistPaymentsController::class, 'index'])->name('artist.payments.index');
    Route::post('/payments/request-payout', [ArtistPaymentsController::class, 'requestPayout'])->name('artist.payments.request-payout');
});

// User routes
Route::middleware(['auth', 'verified', 'onboarding', 'user', 'client_password'])->prefix('user')->group(function () {
    Route::post('/password/booking-initial', [ClientPasswordController::class, 'storeBookingInitial'])
        ->name('user.password.booking-initial.store');

    Route::get('/dashboard', [\App\Http\Controllers\UserController\DashboardController::class, 'index'])
        ->name('user.dashboard');

    Route::get('/settings', [UserSettingsController::class, 'edit'])->name('user.settings');
    Route::post('/settings/avatar', [UserSettingsController::class, 'updateAvatar'])->name('user.settings.avatar');

    Route::get('/custom-requests/{customRequest}/confirm-times', [\App\Http\Controllers\UserController\CustomRequestsController::class, 'confirmTimes'])
        ->name('user.custom-requests.confirm-times');
    Route::post('/custom-requests/{customRequest}/confirm-times', [\App\Http\Controllers\UserController\CustomRequestsController::class, 'storeConfirmedTimes'])
        ->name('user.custom-requests.confirm-times.store');
    Route::get('/custom-requests/{customRequest}/calendar-data', [\App\Http\Controllers\UserController\CustomRequestsController::class, 'calendarData'])
        ->name('user.custom-requests.calendar-data');
    Route::get('/custom-requests/{customRequest}/payment', [\App\Http\Controllers\UserController\CustomRequestsController::class, 'payment'])
        ->name('user.custom-requests.payment');
    Route::post('/custom-requests/{customRequest}/payment/intent', [\App\Http\Controllers\UserController\CustomRequestsController::class, 'createPaymentIntent'])
        ->name('user.custom-requests.payment.intent');
    Route::post('/custom-requests/{customRequest}/payment/viva/order', [\App\Http\Controllers\UserController\CustomRequestsController::class, 'createVivaOrder'])
        ->name('user.custom-requests.payment.viva.order');
    Route::get('/custom-requests/{customRequest}/payment/viva/status', [\App\Http\Controllers\UserController\CustomRequestsController::class, 'vivaPaymentStatus'])
        ->name('user.custom-requests.payment.viva.status');
    Route::post('/custom-requests/{customRequest}/payment/confirm', [\App\Http\Controllers\UserController\CustomRequestsController::class, 'confirmPayment'])
        ->name('user.custom-requests.payment.confirm');

    Route::get('/chat', [UserChatController::class, 'index'])->name('user.chat.index');

    Route::get('/requests', [\App\Http\Controllers\UserController\RequestsController::class, 'index'])
        ->name('user.requests.index');
    Route::get('/requests/{bookingRequest}/confirm-times', [\App\Http\Controllers\UserController\RequestsController::class, 'confirmTimes'])
        ->name('user.requests.confirm-times');
    Route::post('/requests/{bookingRequest}/confirm-times', [\App\Http\Controllers\UserController\RequestsController::class, 'storeConfirmedTimes'])
        ->name('user.requests.confirm-times.store');
    Route::get('/requests/{bookingRequest}/payment', [\App\Http\Controllers\UserController\RequestsController::class, 'payment'])
        ->name('user.requests.payment');
    Route::post('/requests/{bookingRequest}/payment/intent', [\App\Http\Controllers\UserController\RequestsController::class, 'createPaymentIntent'])
        ->name('user.requests.payment.intent');
    Route::post('/requests/{bookingRequest}/payment/viva/order', [\App\Http\Controllers\UserController\RequestsController::class, 'createVivaOrder'])
        ->name('user.requests.payment.viva.order');
    Route::get('/requests/{bookingRequest}/payment/viva/status', [\App\Http\Controllers\UserController\RequestsController::class, 'vivaPaymentStatus'])
        ->name('user.requests.payment.viva.status');
    Route::post('/requests/{bookingRequest}/payment/confirm', [\App\Http\Controllers\UserController\RequestsController::class, 'confirmPayment'])
        ->name('user.requests.payment.confirm');

    // Bookings
    Route::get('/bookings', [BookingsController::class, 'index'])->name('user.bookings.index');
    Route::get('/bookings/{booking_id}/reschedule-calendar-data', [BookingsController::class, 'rescheduleCalendarData'])
        ->name('user.bookings.reschedule-calendar-data');
});

require __DIR__.'/auth.php';

// Route::get('/artists', [InkJinController::class, 'publicArtistsList'])->name('public.artists.list');
Route::get('/chat', function () {
    if (Auth::check()) {
        return match (Auth::user()->role) {
            'artist' => redirect()->route('artist.chat.index'),
            'user' => redirect()->route('user.chat.index'),
            'studio' => redirect()->route('studio.chat.index'),
            default => redirect()->to(authenticated_home_url()),
        };
    }

    return redirect()->route('login');
})->name('public.chat');
// Client consent form (live token + artist preview)
Route::get('/consent/artist/{artist}', [\App\Http\Controllers\ClientConsentController::class, 'preview'])
    ->whereNumber('artist')
    ->name('public.consent.preview');
Route::get('/consent/{token}', [\App\Http\Controllers\ClientConsentController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{32,64}')
    ->name('public.consent');
Route::post('/consent/{token}', [\App\Http\Controllers\ClientConsentController::class, 'submit'])
    ->where('token', '[A-Za-z0-9]{32,64}')
    ->middleware('throttle:30,1')
    ->name('public.consent.submit');

Route::get('/p/{code}', [ArtistDashboardController::class, 'publicPaymentLink'])->name('public.payment-link');
Route::post('/p/{code}/otp/send', [ArtistDashboardController::class, 'sendPaymentLinkOtp'])->name('public.payment-link.otp.send');
Route::post('/p/{code}/otp/verify', [ArtistDashboardController::class, 'verifyPaymentLinkOtp'])->name('public.payment-link.otp.verify');
Route::post('/p/{code}/payment-intent', [ArtistDashboardController::class, 'createPaymentLinkPaymentIntent'])->name('public.payment-link.payment-intent');
Route::post('/p/{code}/payment/confirm', [ArtistDashboardController::class, 'confirmPaymentLinkPayment'])->name('public.payment-link.payment.confirm');
Route::post('/p/{code}/payment/viva/order', [ArtistDashboardController::class, 'createPaymentLinkVivaOrder'])->name('public.payment-link.viva.order');
Route::get('/p/{code}/payment/viva/status', [ArtistDashboardController::class, 'paymentLinkVivaStatus'])->name('public.payment-link.viva.status');
Route::get('/p/{code}/session-details/{booking}', [ArtistDashboardController::class, 'paymentLinkSessionDetails'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('public.payment-link.session-details');
Route::post('/p/{code}/session-details/{booking}', [ArtistDashboardController::class, 'storePaymentLinkSessionDetails'])
    ->name('public.payment-link.session-details.store');
Route::post('/p/{code}/session-details/{booking}/image', [ArtistDashboardController::class, 'uploadPaymentLinkSessionDetailsImage'])
    ->name('public.payment-link.session-details.image');
Route::get('/@{username}', [InkJinController::class, 'publicArtistProfile'])->name('public.artist');

// studio + custom request (before tattoo slug catch-all)
Route::get('/@{user_name}/request', [\App\Http\Controllers\StudioRequestController::class, 'show'])
    ->name('public.studio-request');
Route::get('/@{user_name}/request/custom', [CustomRequestController::class, 'requestCustom'])->name('public.request-custom');
Route::post('/api/public/submit-studio-request', [\App\Http\Controllers\StudioRequestController::class, 'submit'])
    ->name('public.studio-request.submit');

Route::get('/@{user_name}/guest/{guestSpotSlug}', [InkJinController::class, 'publicGuestSpot'])
    ->where('guestSpotSlug', '[A-Za-z0-9\-]+')
    ->name('public.guest-spot');

Route::get('/@{user_name}/{tattoo_slug}', [InkJinController::class, 'publicTattooPage'])->name('public.tattoo');

Route::get('/@{user_name}/{tattoo_slug}/book', [InkJinController::class, 'bookTattoo'])->name('public.tattoo.book');
Route::get('/api/public/check-email-availability', [InkJinController::class, 'checkEmailAvailability'])->name('public.email.availability');
Route::post('/api/public/send-booking-otp', [InkJinController::class, 'sendBookingOtp'])->name('public.booking.otp.send');
Route::post('/api/public/verify-booking-otp', [InkJinController::class, 'verifyBookingOtp'])->name('public.booking.otp.verify');
Route::post('/api/public/upload-booking-question-image', [InkJinController::class, 'uploadBookingQuestionImage'])->name('public.booking.question_image.upload');
Route::post('/api/public/create-booking-payment-intent', [InkJinController::class, 'createBookingPaymentIntent'])->name('public.booking.payment_intent.create');
Route::post('/api/public/booking/payment/viva/order', [InkJinController::class, 'createPublicVivaOrder'])->name('public.booking.payment.viva.order');
Route::get('/api/public/booking/payment/viva/status', [InkJinController::class, 'publicVivaPaymentStatus'])->name('public.booking.payment.viva.status');
Route::post('/api/public/confirm-booking-payment', [InkJinController::class, 'confirmBookingAfterPayment'])->name('public.booking.payment.confirm');
Route::get('/webhooks/viva', [\App\Http\Controllers\Webhooks\VivaWebhookController::class, 'verify'])->name('webhooks.viva.verify');
Route::post('/webhooks/viva', [\App\Http\Controllers\Webhooks\VivaWebhookController::class, 'handle'])->name('webhooks.viva');
Route::post('/webhooks/stream-chat', [\App\Http\Controllers\Webhooks\StreamChatWebhookController::class, 'handle'])
    ->name('webhooks.stream-chat');
Route::get('/viva/success', [VivaReturnController::class, 'success'])->name('viva.success');
Route::get('/viva/fail', [VivaReturnController::class, 'fail'])->name('viva.fail');
Route::post('/api/public/submit-managed-booking', [InkJinController::class, 'submitManagedBooking'])->name('public.booking.managed.submit');
Route::post('/api/public/submit-waitlist', [InkJinController::class, 'submitWaitlist'])->name('public.waitlist.submit');
Route::post('/api/public/submit-guest-waitlist', [InkJinController::class, 'submitGuestWaitlist'])->name('public.guest-waitlist.submit');
Route::post('/api/public/submit-custom-request', [CustomRequestController::class, 'submitCustomRequest'])->name('public.custom-request.submit');
