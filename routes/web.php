<?php

use App\Http\Controllers\VivaReturnController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InkJinController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FinancialController as AdminFinancialController;
use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\StyleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
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
use App\Http\Controllers\Api\ChatController as ApiChatController;
use App\Http\Controllers\CustomRequestController;
use App\Http\Controllers\RequestsController;
use App\Http\Controllers\StripeConnectDevController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->to(authenticated_home_url());
    }

    return redirect()->route('login');
});

// Onboarding routes (must be before other auth routes)
Route::middleware(['auth', 'verified'])->group(function () {
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
    Route::post('/onboarding/calendar', [OnboardingController::class, 'saveCalendar'])->name('onboarding.calendar.save');
    Route::post('/onboarding/preferences', [OnboardingController::class, 'savePreferences'])->name('onboarding.preferences.save');
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

});

Route::get('/stripe/delete-account', [StripeConnectDevController::class, 'showDeleteForm'])
    ->name('stripe.delete-account.show');
Route::post('/stripe/delete-account', [StripeConnectDevController::class, 'deleteAccount'])
    ->name('stripe.delete-account.destroy');

Route::get('/studio/payout-info/{userDetail}', [OnboardingController::class, 'showStudioPayoutForm'])
    ->middleware('signed')
    ->name('studio.payout-info.show');
Route::post('/studio/payout-info/{userDetail}/stripe/session', [OnboardingController::class, 'createStudioStripeSession'])
    ->middleware('signed')
    ->name('studio.payout-info.stripe.session');
Route::get('/studio/payout-info/{userDetail}/stripe/status', [OnboardingController::class, 'studioStripeStatus'])
    ->middleware('signed')
    ->name('studio.payout-info.stripe.status');
Route::post('/studio/payout-info/{userDetail}/stripe/complete', [OnboardingController::class, 'completeStudioStripeOnboarding'])
    ->middleware('signed')
    ->name('studio.payout-info.stripe.complete');

Route::get('/studio/payout-link/{userDetail}/approve', [OnboardingController::class, 'approveStudioArtistBankLink'])
    ->middleware('signed')
    ->name('studio.payout-artist-link.approve');
Route::get('/studio/payout-link/{userDetail}/decline', [OnboardingController::class, 'declineStudioArtistBankLink'])
    ->middleware('signed')
    ->name('studio.payout-artist-link.decline');

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
    Route::post('/api/bookings/{id}/mark-completed', [\App\Http\Controllers\BookingsController::class, 'markCompleted'])->name('api.bookings.mark-completed');
    
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
    Route::get('/can-send/{streamChannelId}', [ApiChatController::class, 'canSend'])->name('api.chat.can-send');
    Route::get('/unread-summary', [ApiChatController::class, 'unreadSummary'])->name('api.chat.unread-summary');
});

// Profile routes (accessible even if email not verified, so user can update email)
Route::middleware(['auth', 'onboarding', 'client_password'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// Admin routes
Route::middleware(['auth', 'verified', 'onboarding', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/revenue', [AdminFinancialController::class, 'revenue'])->name('admin.revenue.index');
    Route::get('/fees', [AdminFinancialController::class, 'fees'])->name('admin.fees.index');
    Route::get('/payouts', [AdminFinancialController::class, 'payouts'])->name('admin.payouts.index');

    Route::get('/forms', [FormController::class, 'index'])->name('admin.forms.index');
    Route::post('/forms/questions', [QuestionsController::class, 'store'])->name('admin.forms.questions.store');
    Route::put('/forms/questions/{id}', [QuestionsController::class, 'update'])->name('admin.forms.questions.update');
    Route::post('/forms/questions/reorder', [QuestionsController::class, 'reorder'])->name('admin.forms.questions.reorder');
    Route::delete('/forms/questions/{id}', [QuestionsController::class, 'destroy'])->name('admin.forms.questions.destroy');

    Route::get('/styles', [StyleController::class, 'index'])->name('admin.styles.index');
    Route::post('/styles', [StyleController::class, 'store'])->name('admin.styles.store');
    Route::put('/styles/{style}', [StyleController::class, 'update'])->name('admin.styles.update');
    Route::delete('/styles/{style}', [StyleController::class, 'destroy'])->name('admin.styles.destroy');
    Route::post('/styles/reorder', [StyleController::class, 'reorder'])->name('admin.styles.reorder');

    // Route::get('/questions', [\App\Http\Controllers\Admin\QuestionController::class, 'index'])->name('admin.questions.index');
    // Route::post('/questions', [\App\Http\Controllers\Admin\QuestionController::class, 'store'])->name('admin.questions.store');
    // Route::put('/questions/{id}', [\App\Http\Controllers\Admin\QuestionController::class, 'update'])->name('admin.questions.update');
    // Route::delete('/questions/{id}', [\App\Http\Controllers\Admin\QuestionController::class, 'destroy'])->name('admin.questions.destroy');
    
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('admin.users.show');
});

// Artist routes
Route::middleware(['auth', 'verified', 'onboarding', 'artist'])->prefix('artist')->group(function () {
    
    Route::get('/dashboard', [ArtistDashboardController::class, 'index'])->name('artist.dashboard');

    // Settings routes

    Route::get('/settings/styles', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail;
        $styleOptions = \App\Models\Style::query()
            ->active()
            ->ordered()
            ->pluck('name', 'name')
            ->toArray();

        if (empty($styleOptions)) {
            $fallbackSlugs = [
                'traditional', 'neo-traditional', 'japanese', 'realism', 'blackwork',
                'minimalist', 'geometric', 'watercolor', 'tribal', 'dotwork', 'new-school', 'illustrative',
            ];
            foreach ($fallbackSlugs as $slug) {
                $styleOptions[$slug] = ucwords(str_replace('-', ' ', $slug));
            }
        }

        return view('artist.settings.styles', compact('userDetail', 'styleOptions'));
    })->name('settings.styles');
    
    Route::post('/settings/styles', [OnboardingController::class, 'updateStylesSocial'])->name('settings.styles.update');

    Route::get('/settings/studio', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail;
        return view('artist.settings.studio', compact('userDetail'));
    })->name('settings.studio');
    
    Route::post('/settings/studio', [OnboardingController::class, 'updateStudio'])->name('settings.studio.update');
    
    Route::get('/settings/calendar', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail;
        return view('artist.settings.calendar', compact('userDetail'));
    })->name('settings.calendar');
    
    Route::post('/settings/calendar', [OnboardingController::class, 'updateCalendar'])->name('settings.calendar.update');
    
    Route::get('/settings/preferences', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail;
        return view('artist.settings.preferences', compact('userDetail'));
    })->name('settings.preferences');
    
    Route::post('/settings/preferences', [OnboardingController::class, 'savePreferences'])->name('settings.preferences.update');

    Route::get('/settings/payment', [OnboardingController::class, 'paymentSettings'])->name('settings.payment');
    Route::post('/settings/payment', [OnboardingController::class, 'updatePayment'])->name('settings.payment.update');
    Route::post('/settings/payment/bank-country', [OnboardingController::class, 'savePayoutBankCountry'])->name('settings.payment.bank-country');
    Route::post('/settings/payment/waiting-list', [OnboardingController::class, 'savePayoutWaitingList'])->name('settings.payment.waiting-list');
    Route::post('/settings/payment/stripe/session', [OnboardingController::class, 'createStripeConnectSession'])->name('settings.payment.stripe.session');
    Route::get('/settings/payment/stripe/status', [OnboardingController::class, 'stripeConnectStatus'])->name('settings.payment.stripe.status');

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
    Route::post('/personal-page/display-policies', [\App\Http\Controllers\PersonalPageController::class, 'updateDisplayPolicies'])->name('personal-page.display-policies');
    Route::post('/personal-page/profile-content-visibility', [\App\Http\Controllers\PersonalPageController::class, 'updateProfileContentVisibility'])->name('personal-page.profile-content-visibility');

    Route::get('/portfolio', [\App\Http\Controllers\PortfolioController::class, 'index'])->name('portfolio.index');
    Route::post('/portfolio', [\App\Http\Controllers\PortfolioController::class, 'store'])->name('portfolio.store');
    Route::put('/portfolio/{portfolio}', [\App\Http\Controllers\PortfolioController::class, 'update'])->name('portfolio.update');
    Route::delete('/portfolio/{portfolio}', [\App\Http\Controllers\PortfolioController::class, 'destroy'])->name('portfolio.destroy');

    Route::get('/artist-designs', [\App\Http\Controllers\ArtistDesignsController::class, 'index'])->name('artist-designs.index');
    Route::post('/artist-designs', [\App\Http\Controllers\ArtistDesignsController::class, 'store'])->name('artist-designs.store');
    Route::put('/artist-designs/{artistDesign}', [\App\Http\Controllers\ArtistDesignsController::class, 'update'])->name('artist-designs.update');
    Route::put('/artist-designs/settings/whats-included', [\App\Http\Controllers\ArtistDesignsController::class, 'updateWhatsIncluded'])->name('artist-designs.whats-included.update');
    Route::patch('/artist-designs/{artistDesign}/availability', [\App\Http\Controllers\ArtistDesignsController::class, 'toggleAvailability'])->name('artist-designs.toggle-availability');
    Route::patch('/artist-designs/{artistDesign}/visibility', [\App\Http\Controllers\ArtistDesignsController::class, 'toggleVisibility'])->name('artist-designs.toggle-visibility');
    Route::delete('/artist-designs/{artistDesign}', [\App\Http\Controllers\ArtistDesignsController::class, 'destroy'])->name('artist-designs.destroy');

    Route::get('/forms', [QuestionsController::class, 'index'])->name('artist.forms.index');
    Route::post('/forms/questions', [QuestionsController::class, 'store'])->name('artist.forms.questions.store');
    Route::put('/forms/questions/{id}', [QuestionsController::class, 'update'])->name('artist.forms.questions.update');
    Route::patch('/forms/questions/{id}/status', [QuestionsController::class, 'updateSystemQuestionStatus'])->name('artist.forms.questions.status');
    Route::post('/forms/questions/reorder', [QuestionsController::class, 'reorder'])->name('artist.forms.questions.reorder');
    Route::delete('/forms/questions/{id}', [QuestionsController::class, 'destroy'])->name('artist.forms.questions.destroy');

    // Booking routes
    Route::get('/bookings', [ArtistBookingsController::class, 'index'])->name('artist.bookings.index');

    // Requests
    Route::get('/requests', [RequestsController::class, 'index'])->name('artist.requests.index');
    Route::get('/custom-requests', [ArtistCustomRequestsController::class, 'index'])->name('artist.custom-requests.index');
    Route::post('/custom-requests/{customRequest}/decline', [ArtistCustomRequestsController::class, 'decline'])->name('artist.custom-requests.decline');
    Route::post('/custom-requests/{customRequest}/send-quote', [ArtistCustomRequestsController::class, 'sendQuote'])->name('artist.custom-requests.send-quote');
    Route::post('/requests/{bookingRequest}/decline', [RequestsController::class, 'decline'])->name('artist.requests.decline');
    Route::post('/requests/{bookingRequest}/offer-slots', [RequestsController::class, 'offerSlots'])->name('artist.requests.offer-slots');

    Route::get('/chat', [ArtistChatController::class, 'index'])->name('artist.chat.index');
    Route::get('/clients', [ArtistClientsController::class, 'index'])->name('artist.clients.index');
    Route::post('/clients/waitlist/notify', [ArtistClientsController::class, 'notifyWaitlist'])->name('artist.clients.waitlist.notify');
    Route::get('/payments', [ArtistPaymentsController::class, 'index'])->name('artist.payments.index');
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
            default => redirect()->to(authenticated_home_url()),
        };
    }

    return redirect()->route('login');
})->name('public.chat');
Route::get('/@{username}', [InkJinController::class, 'publicArtistProfile'])->name('public.artist');

Route::get('/@{user_name}/{tattoo_slug}', [InkJinController::class, 'publicTattooPage'])->name('public.tattoo');

// custom request
Route::get('/@{user_name}/request/custom', [CustomRequestController::class, 'requestCustom'])->name('public.request-custom');

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
Route::get('/viva/success', [VivaReturnController::class, 'success'])->name('viva.success');
Route::get('/viva/fail', [VivaReturnController::class, 'fail'])->name('viva.fail');
Route::post('/api/public/submit-managed-booking', [InkJinController::class, 'submitManagedBooking'])->name('public.booking.managed.submit');
Route::post('/api/public/submit-waitlist', [InkJinController::class, 'submitWaitlist'])->name('public.waitlist.submit');
Route::post('/api/public/submit-custom-request', [CustomRequestController::class, 'submitCustomRequest'])->name('public.custom-request.submit');
