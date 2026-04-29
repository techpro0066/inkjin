<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InkJinController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\QuestionsController;

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
    Route::post('/onboarding/payment', [OnboardingController::class, 'savePayment'])->name('onboarding.payment.save');
    Route::get('/onboarding/progress', [OnboardingController::class, 'getProgress'])->name('onboarding.progress');
    
    // Google Calendar OAuth routes
    Route::get('/auth/google-calendar', [\App\Http\Controllers\GoogleCalendarController::class, 'redirect'])->name('google.calendar.redirect');
    Route::get('/auth/google-calendar/callback', [\App\Http\Controllers\GoogleCalendarController::class, 'callback'])->name('google.calendar.callback');
    // Alias route for Google callback (in case Google Console is configured with /auth/google/callback)
    Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleCalendarController::class, 'callback'])->name('google.callback');
    Route::get('/auth/google-calendar/status', [\App\Http\Controllers\GoogleCalendarController::class, 'checkStatus'])->name('google.calendar.status');
    Route::post('/auth/google-calendar/disconnect', [\App\Http\Controllers\GoogleCalendarController::class, 'disconnect'])->name('google.calendar.disconnect');

    // Stripe Connect routes
    Route::get('/connect-stripe', [\App\Http\Controllers\StripeConnectController::class, 'connectStripe'])->name('connect.stripe');
    Route::get('/connect-stripe/callback', [\App\Http\Controllers\StripeConnectController::class, 'callback'])->name('connect.stripe.callback');
    Route::get('/connect-stripe/status', [\App\Http\Controllers\StripeConnectController::class, 'getAccountStatus'])->name('connect.stripe.status');
    Route::post('/connect-stripe/disconnect', [\App\Http\Controllers\StripeConnectController::class, 'disconnect'])->name('connect.stripe.disconnect');
});

Route::get('/studio/payment/decision/{userDetail}/{decision}', [OnboardingController::class, 'studioPaymentDecision'])
    ->whereIn('decision', ['allow', 'decline'])
    ->name('studio.payment.decision');
Route::get('/studio/stripe/connect/{userDetail}', [\App\Http\Controllers\StripeConnectController::class, 'studioConnect'])
    ->name('studio.stripe.connect');
Route::get('/studio/stripe/callback/{userDetail}', [\App\Http\Controllers\StripeConnectController::class, 'studioCallback'])
    ->name('studio.stripe.callback');

//Common routes

Route::middleware(['auth', 'verified', 'onboarding'])->group(function () {
    Route::get('/studio/payment/status', [OnboardingController::class, 'studioPaymentStatus'])->name('studio.payment.status');

    // Dashboard route
    // Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
    // Bookings route (for all authenticated users)
    Route::get('/bookings', [\App\Http\Controllers\BookingsController::class, 'index'])->name('bookings.index');
    
    // Booking cancellation routes
    Route::get('/api/bookings/{id}/cancellation-info', [\App\Http\Controllers\BookingCancellationController::class, 'getCancellationInfo'])->name('api.bookings.cancellation-info');
    Route::post('/api/bookings/{id}/cancel', [\App\Http\Controllers\BookingCancellationController::class, 'cancel'])->name('api.bookings.cancel');
    Route::post('/api/bookings/{id}/mark-no-show', [\App\Http\Controllers\BookingCancellationController::class, 'markNoShow'])->name('api.bookings.mark-no-show');
    
    // Booking rescheduling routes
    Route::get('/api/bookings/{id}/can-reschedule', [\App\Http\Controllers\ReschedulingController::class, 'checkCanReschedule'])->name('api.bookings.can-reschedule');
    Route::post('/api/bookings/{id}/artist-request-reschedule', [\App\Http\Controllers\ReschedulingController::class, 'artistRequestReschedule'])->name('api.bookings.artist-request-reschedule');
    Route::post('/api/bookings/{id}/reschedule', [\App\Http\Controllers\ReschedulingController::class, 'reschedule'])->name('api.bookings.reschedule');
    Route::post('/api/bookings/{id}/decline-reschedule', [\App\Http\Controllers\ReschedulingController::class, 'declineReschedule'])->name('api.bookings.decline-reschedule');
    Route::get('/bookings/{id}/reschedule', [\App\Http\Controllers\ReschedulingController::class, 'showReschedulePage'])->name('bookings.reschedule');
    Route::get('/bookings/{id}/reschedule-flow', [\App\Http\Controllers\ReschedulingController::class, 'showRescheduleFlow'])->name('bookings.reschedule-flow');
    
});

// Profile routes (accessible even if email not verified, so user can update email)
Route::middleware(['auth', 'onboarding'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// Admin routes
Route::middleware(['auth', 'verified', 'onboarding', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/forms', [FormController::class, 'index'])->name('admin.forms.index');
    Route::post('/forms/questions', [QuestionsController::class, 'store'])->name('admin.forms.questions.store');
    Route::put('/forms/questions/{id}', [QuestionsController::class, 'update'])->name('admin.forms.questions.update');
    Route::post('/forms/questions/reorder', [QuestionsController::class, 'reorder'])->name('admin.forms.questions.reorder');
    Route::delete('/forms/questions/{id}', [QuestionsController::class, 'destroy'])->name('admin.forms.questions.destroy');

    // Route::get('/questions', [\App\Http\Controllers\Admin\QuestionController::class, 'index'])->name('admin.questions.index');
    // Route::post('/questions', [\App\Http\Controllers\Admin\QuestionController::class, 'store'])->name('admin.questions.store');
    // Route::put('/questions/{id}', [\App\Http\Controllers\Admin\QuestionController::class, 'update'])->name('admin.questions.update');
    // Route::delete('/questions/{id}', [\App\Http\Controllers\Admin\QuestionController::class, 'destroy'])->name('admin.questions.destroy');
    
    // Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
    // Route::get('/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('admin.users.show');
});

// Artist routes
Route::middleware(['auth', 'verified', 'onboarding', 'artist'])->prefix('artist')->group(function () {
    
    Route::get('/dashboard', function () {
        return view('artist.dashboard');
    })->name('artist.dashboard');

    // Settings routes

    Route::get('/settings/styles', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail;
        return view('artist.settings.styles', compact('userDetail'));
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

    Route::get('/settings/payment', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail;
        return view('artist.settings.payment', compact('userDetail'));
    })->name('settings.payment');
    
    Route::post('/settings/payment', [OnboardingController::class, 'updatePayment'])->name('settings.payment.update');

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

    Route::get('/portfolio', [\App\Http\Controllers\PortfolioController::class, 'index'])->name('portfolio.index');
    Route::post('/portfolio', [\App\Http\Controllers\PortfolioController::class, 'store'])->name('portfolio.store');
    Route::put('/portfolio/{portfolio}', [\App\Http\Controllers\PortfolioController::class, 'update'])->name('portfolio.update');
    Route::delete('/portfolio/{portfolio}', [\App\Http\Controllers\PortfolioController::class, 'destroy'])->name('portfolio.destroy');

    Route::get('/artist-designs', [\App\Http\Controllers\ArtistDesignsController::class, 'index'])->name('artist-designs.index');
    Route::post('/artist-designs', [\App\Http\Controllers\ArtistDesignsController::class, 'store'])->name('artist-designs.store');
    Route::put('/artist-designs/{artistDesign}', [\App\Http\Controllers\ArtistDesignsController::class, 'update'])->name('artist-designs.update');
    Route::delete('/artist-designs/{artistDesign}', [\App\Http\Controllers\ArtistDesignsController::class, 'destroy'])->name('artist-designs.destroy');

    Route::get('/forms', [QuestionsController::class, 'index'])->name('artist.forms.index');
    Route::post('/forms/questions', [QuestionsController::class, 'store'])->name('artist.forms.questions.store');
    Route::put('/forms/questions/{id}', [QuestionsController::class, 'update'])->name('artist.forms.questions.update');
    Route::patch('/forms/questions/{id}/status', [QuestionsController::class, 'updateSystemQuestionStatus'])->name('artist.forms.questions.status');
    Route::post('/forms/questions/reorder', [QuestionsController::class, 'reorder'])->name('artist.forms.questions.reorder');
    Route::delete('/forms/questions/{id}', [QuestionsController::class, 'destroy'])->name('artist.forms.questions.destroy');
});

require __DIR__.'/auth.php';

Route::get('/{username}', [InkJinController::class, 'publicArtistProfile'])->name('public.artist');

Route::get('/{user_name}/{tattoo_slug}', [InkJinController::class, 'publicTattooPage'])->name('public.tattoo');

Route::get('/{user_name}/{tattoo_slug}/book', [InkJinController::class, 'bookTattoo'])->name('public.tattoo.book');
Route::get('/api/public/check-email-availability', [InkJinController::class, 'checkEmailAvailability'])->name('public.email.availability');
Route::post('/api/public/send-booking-otp', [InkJinController::class, 'sendBookingOtp'])->name('public.booking.otp.send');
Route::post('/api/public/verify-booking-otp', [InkJinController::class, 'verifyBookingOtp'])->name('public.booking.otp.verify');
