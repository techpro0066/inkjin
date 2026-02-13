<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InkJinController;
use Illuminate\Support\Facades\Route;

// Public InkJin API routes
Route::get('/api/tattoo/{id}', [InkJinController::class, 'getTattoo'])->name('api.tattoo.show');
Route::get('/api/artist/{id}', [InkJinController::class, 'getArtist'])->name('api.artist.show');
Route::get('/api/artists', [InkJinController::class, 'getArtistsList'])->name('api.artists.list');
Route::get('/api/tattoos', [InkJinController::class, 'getTattoosList'])->name('api.tattoos.list');

// Countries and Cities API routes
Route::get('/api/countries', [\App\Http\Controllers\CountriesController::class, 'getCountries'])->name('api.countries');
Route::get('/api/cities', [\App\Http\Controllers\CountriesController::class, 'getCities'])->name('api.cities');
Route::get('/api/countries/all', [\App\Http\Controllers\CountriesController::class, 'getAll'])->name('api.countries.all');

// Public InkJin Database routes
Route::get('/db/tattoo/{id}', [InkJinController::class, 'getTattooFromDb'])->name('db.tattoo.show');
Route::get('/db/artist/{id}', [InkJinController::class, 'getArtistFromDb'])->name('db.artist.show');

Route::get('/', function () {
    // login page
    return redirect()->route('login');
});

// Onboarding routes (must be before other auth routes)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding/step/1', [\App\Http\Controllers\OnboardingController::class, 'saveStep1'])->name('onboarding.step1');
    Route::post('/onboarding/step/2', [\App\Http\Controllers\OnboardingController::class, 'saveStep2'])->name('onboarding.step2');
    Route::post('/onboarding/step/3', [\App\Http\Controllers\OnboardingController::class, 'saveStep3'])->name('onboarding.step3');
    Route::post('/onboarding/step/4', [\App\Http\Controllers\OnboardingController::class, 'saveStep4'])->name('onboarding.step4');
    Route::post('/onboarding/step/5', [\App\Http\Controllers\OnboardingController::class, 'saveStep5'])->name('onboarding.step5');
    Route::get('/onboarding/progress', [\App\Http\Controllers\OnboardingController::class, 'getProgress'])->name('onboarding.progress');
    
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

//Common routes

Route::middleware(['auth', 'verified', 'onboarding'])->group(function () {
    // Dashboard route
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
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
Route::middleware(['auth', 'verified', 'onboarding', 'admin'])->group(function () {
    Route::get('/admin/questions', [\App\Http\Controllers\Admin\QuestionController::class, 'index'])->name('admin.questions.index');
    Route::post('/admin/questions', [\App\Http\Controllers\Admin\QuestionController::class, 'store'])->name('admin.questions.store');
    Route::put('/admin/questions/{id}', [\App\Http\Controllers\Admin\QuestionController::class, 'update'])->name('admin.questions.update');
    Route::delete('/admin/questions/{id}', [\App\Http\Controllers\Admin\QuestionController::class, 'destroy'])->name('admin.questions.destroy');
    
    Route::get('/admin/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('admin.users.show');
});

// Artist routes
Route::middleware(['auth', 'verified', 'onboarding', 'artist'])->group(function () {
    // Settings routes
    Route::get('/settings/studio', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail;
        return view('artist.settings.studio', compact('userDetail'));
    })->name('settings.studio');
    
    Route::get('/settings/calendar', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail;
        return view('artist.settings.calendar', compact('userDetail'));
    })->name('settings.calendar');
    
    Route::get('/settings/profile', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail;
        return view('artist.settings.profile', compact('userDetail'));
    })->name('settings.profile');
    
    Route::post('/settings/profile', [\App\Http\Controllers\OnboardingController::class, 'updateProfile'])->name('settings.profile.update');
    
    Route::get('/settings/preferences', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $userDetail = $user->userDetail;
        return view('artist.settings.preferences', compact('userDetail'));
    })->name('settings.preferences');
    
    Route::post('/settings/preferences', [\App\Http\Controllers\OnboardingController::class, 'saveStep3'])->name('settings.preferences.update');
    
    // Availability routes (for artists)
    Route::get('/availability', [\App\Http\Controllers\AvailabilityController::class, 'index'])->name('availability.index');
    Route::post('/availability', [\App\Http\Controllers\AvailabilityController::class, 'store'])->name('availability.store');
    Route::delete('/availability/{id}', [\App\Http\Controllers\AvailabilityController::class, 'destroy'])->name('availability.destroy');
    
    // Availability override routes
    Route::post('/availability/override', [\App\Http\Controllers\AvailabilityController::class, 'storeOverride'])->name('availability.override.store');
    Route::get('/availability/override', [\App\Http\Controllers\AvailabilityController::class, 'getOverride'])->name('availability.override.get');
    Route::delete('/availability/override/{id}', [\App\Http\Controllers\AvailabilityController::class, 'destroyOverride'])->name('availability.override.destroy');
    
    // Questions routes (for artists)
    Route::get('/questions', [\App\Http\Controllers\QuestionsController::class, 'index'])->name('questions.index');
    Route::post('/questions', [\App\Http\Controllers\QuestionsController::class, 'store'])->name('questions.store');
    Route::put('/questions/{id}', [\App\Http\Controllers\QuestionsController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{id}', [\App\Http\Controllers\QuestionsController::class, 'destroy'])->name('questions.destroy');
});

// User routes
Route::middleware(['auth', 'verified', 'onboarding', 'user'])->prefix('dashboard')->group(function () {
    Route::get('/artists', [\App\Http\Controllers\DashboardController::class, 'artists'])->name('dashboard.artists');
    Route::get('/artists/{username}', [\App\Http\Controllers\DashboardController::class, 'artistShow'])->name('dashboard.artists.show');
    Route::get('/tattoo/{id}', [\App\Http\Controllers\DashboardController::class, 'tattooShow'])->name('dashboard.tattoo.show');
});

require __DIR__.'/auth.php';

// Public database routes (using actual names from database)
// These routes must be before the catch-all API routes
// Route::get('/artists', [InkJinController::class, 'publicArtistsList'])
//     ->name('public.artists.list');

// Route::get('/artist/{username}', [InkJinController::class, 'publicArtistProfileFromDb'])
//     ->where(['username' => '[a-zA-Z0-9_.-]+'])
//     ->name('public.artist.db');

// Route::get('/tattoo/{artist_display_name}/{tattoo_title}/{tattoo_id}', [InkJinController::class, 'publicTattooPageFromDb'])
//     ->where(['tattoo_id' => '[0-9]+'])
//     ->name('public.tattoo.db');

Route::middleware(['auth', 'verified', 'onboarding'])->group(function () {
Route::get('/tattoo/{artist_display_name}/{tattoo_title}/{tattoo_id}/book', [InkJinController::class, 'bookTattoo'])
    ->where(['tattoo_id' => '[0-9]+'])
    ->name('public.tattoo.book');
});

// Public API route for getting availability slots (no auth required)
Route::get('/api/availability/{tattoo_id}', [InkJinController::class, 'getAvailabilitySlots'])
    ->where(['tattoo_id' => '[0-9]+'])
    ->name('api.availability.slots');

// Public API route for submitting booking (no auth required)
Route::post('/api/booking/{tattoo_id}', [InkJinController::class, 'submitBooking'])
    ->where(['tattoo_id' => '[0-9]+'])
    ->name('api.booking.submit');

// Public API route for creating payment intent (no auth required)
Route::post('/api/booking/{tattoo_id}/payment-intent', [InkJinController::class, 'createPaymentIntent'])
    ->where(['tattoo_id' => '[0-9]+'])
    ->name('api.booking.payment-intent');

Route::post('/api/booking/{tattoo_id}/confirm', [InkJinController::class, 'confirmBooking'])
    ->where(['tattoo_id' => '[0-9]+'])
    ->name('api.booking.confirm');

// Separate consultation timing API routes
Route::get('/api/tattoos/{tattoo_id}/consultation-slots', [InkJinController::class, 'getConsultationSlots'])
    ->where(['tattoo_id' => '[0-9]+'])
    ->name('api.consultation.slots');

Route::get('/api/tattoos/{tattoo_id}/tattoo-session-slots', [InkJinController::class, 'getTattooSessionSlots'])
    ->where(['tattoo_id' => '[0-9]+'])
    ->name('api.tattoo-session.slots');

Route::post('/api/bookings/{tattoo_id}/book-separate', [InkJinController::class, 'bookSeparateConsultation'])
    ->where(['tattoo_id' => '[0-9]+'])
    ->name('api.booking.separate');

// Public API routes (must be before catch-all routes)
Route::get('/{username}', [InkJinController::class, 'publicArtistProfile'])
    ->where(['username' => '[a-zA-Z0-9_.-]+'])
    ->name('public.artist');

// Public tattoo page route (must be at the end to avoid conflicts with other routes)
// Only matches if tattoo_id is numeric (prevents matching routes like /verify-email/{id}/{hash})
Route::get('/{artist_name}/{tattoo_name}/{tattoo_id}', [InkJinController::class, 'publicTattooPage'])
    ->where(['tattoo_id' => '[0-9]+'])
    ->name('public.tattoo');
