<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserBankDetail;
use App\Models\Studio;
use App\Models\Style;
use App\Mail\ArtistWelcomeMail;
use App\Mail\StudioPayoutDeclinedArtistMail;
use App\Mail\StudioPayoutInfoRequestMail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use App\Rules\ReservedArtistUsername;
use App\Models\QuestionSorting;
use App\Services\LocationPreferencesService;
use App\Services\StripeConnectService;
use App\Services\StripeCountrySpecService;
use App\Services\StripeRequirementSyncService;
use App\Support\SocialLinks;
use App\Support\StripeConnectCountries;
use Stripe\Exception\ApiErrorException;

class OnboardingController extends Controller
{
    /** Slugs allowed for primary + other tattoo styles (must match onboarding UI). */
    private const TATTOO_STYLE_SLUGS = [
        'traditional', 'neo-traditional', 'japanese', 'realism', 'blackwork',
        'minimalist', 'geometric', 'watercolor', 'tribal', 'dotwork', 'new-school', 'illustrative',
    ];

    private function activeStyleOptions(): array
    {
        $fromDb = Style::query()
            ->active()
            ->ordered()
            ->pluck('name', 'name')
            ->toArray();

        if (! empty($fromDb)) {
            return $fromDb;
        }

        // Fallback for environments where styles table is not seeded yet.
        $fallback = [];
        foreach (self::TATTOO_STYLE_SLUGS as $slug) {
            $label = ucwords(str_replace('-', ' ', $slug));
            $fallback[$slug] = $label;
        }

        return $fallback;
    }

    private function activeStyleValues(): array
    {
        return array_keys($this->activeStyleOptions());
    }

    /**
     * Legacy entry: send users to the correct step in the multi-page onboarding flow.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->on_boarding === 'yes') {
            return redirect()->intended(authenticated_home_url());
        }

        $step = (int) ($user->userDetail?->current_step ?? 1);

        return redirect()->to($this->onboardingUrlForStep($step));
    }

    /**
     * Resolve onboarding URL for step 1–6 (profile → … → payment).
     */
    protected function onboardingUrlForStep(int $step): string
    {
        return match ($step) {
            1 => route('onboarding.profile'),
            2 => route('onboarding.styles-social'),
            3 => route('onboarding.studio'),
            4 => route('onboarding.preferences'),
            5 => route('onboarding.calendar'),
            6 => route('onboarding.payment'),
            default => route('onboarding.profile'),
        };
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|null
     */
    /**
     * Allow Stripe payout API calls from onboarding step 6 or dashboard payment settings.
     */
    protected function ensurePaymentStripeAccess(Request $request): mixed
    {
        if ($request->routeIs('settings.payment.*')) {
            return null;
        }

        return $this->ensureOnboardingPage($request, 6);
    }

    protected function ensureOnboardingPage(Request $request, int $pageStep)
    {
        $user = $request->user();

        if ($user->on_boarding === 'yes') {
            return redirect()->intended(authenticated_home_url());
        }

        $userDetail = $user->userDetail;
        $current = $userDetail ? (int) ($userDetail->current_step ?? 1) : 1;

        if ($pageStep > $current) {
            return redirect()->to($this->onboardingUrlForStep($current))
                ->with('info', 'Complete the previous steps first.');
        }

        return null;
    }

    /**
     * @return array{userDetail: UserDetail, currentStep: int, completedSteps: array}
     */
    protected function onboardingViewData(Request $request): array
    {
        $user = $request->user();
        $userDetail = $user->userDetail;
        if (! $userDetail) {
            $userDetail = new UserDetail();
            $userDetail->user_id = $user->id;
            $userDetail->current_step = 1;
            $userDetail->completed_steps = [];
        }

        return [
            'userDetail' => $userDetail,
            'currentStep' => (int) ($userDetail->current_step ?? 1),
            'completedSteps' => $userDetail->completed_steps ?? [],
        ];
    }

    public function profile(Request $request)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 1)) {
            return $redirect;
        }

        return view('onboarding.profile', $this->onboardingViewData($request) + ['activeNav' => 'profile']);
    }

    public function stylesSocial(Request $request)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 2)) {
            return $redirect;
        }

        return view('onboarding.styles-social', $this->onboardingViewData($request) + [
            'activeNav' => 'styles-social',
            'styleOptions' => $this->activeStyleOptions(),
        ]);
    }

    public function studio(Request $request)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 3)) {
            return $redirect;
        }

        return view('onboarding.studio', $this->onboardingViewData($request) + ['activeNav' => 'studio']);
    }

    public function preferences(Request $request)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 4)) {
            return $redirect;
        }

        return view('onboarding.preferences', $this->onboardingViewData($request) + ['activeNav' => 'preferences']);
    }

    public function calendar(Request $request)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 5)) {
            return $redirect;
        }

        return view('onboarding.calendar', $this->onboardingViewData($request) + ['activeNav' => 'calendar']);
    }

    public function payment(Request $request, StripeConnectService $stripeConnect)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 6)) {
            return $redirect;
        }

        $userDetail = $request->user()->userDetail;
        $this->ensureDefaultArtistPaymentType($userDetail);
        $stripeStatus = null;
        if ($userDetail?->stripe_account_id && $stripeConnect->isConfigured()) {
            try {
                $stripeStatus = $stripeConnect->getOnboardingStatus($userDetail->stripe_account_id);
            } catch (ApiErrorException $e) {
                Log::warning('Could not load Stripe Connect status for onboarding payment step', [
                    'user_id' => $request->user()->id,
                    'stripe_account_id' => $userDetail->stripe_account_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('onboarding.payment', $this->onboardingViewData($request) + $this->paymentStripeViewData($userDetail, $stripeConnect, $stripeStatus));
    }

    public function paymentSettings(Request $request, StripeConnectService $stripeConnect, StripeRequirementSyncService $stripeRequirementSync)
    {
        $userDetail = $request->user()->userDetail;
        $this->ensureDefaultArtistPaymentType($userDetail);
        $stripeStatus = null;
        $studioStripeStatus = null;

        if (
            $userDetail
            && ($userDetail->payment_type ?? '') === 'artist_account'
            && $userDetail->stripe_account_id
            && $stripeConnect->isConfigured()
        ) {
            try {
                $stripeRequirementSync->syncUserDetail($userDetail);
                $userDetail->refresh();
            } catch (\Throwable $e) {
                Log::warning('Could not sync Stripe requirements for payment settings', [
                    'user_id' => $request->user()->id,
                    'stripe_account_id' => $userDetail->stripe_account_id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $stripeStatus = $stripeConnect->getOnboardingStatus($userDetail->stripe_account_id);
            } catch (ApiErrorException $e) {
                Log::warning('Could not load Stripe Connect status for payment settings', [
                    'user_id' => $request->user()->id,
                    'stripe_account_id' => $userDetail->stripe_account_id,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif (
            $userDetail
            && ($userDetail->payment_type ?? '') === 'studio_account'
            && ($userDetail->payment_status ?? '') === 'approved'
            && $userDetail->studio_id
            && $stripeConnect->isConfigured()
        ) {
            $studio = $userDetail->studio;
            if ($studio) {
                try {
                    $stripeRequirementSync->syncStudio($studio);
                    $userDetail->refresh();
                    $studio->refresh();
                } catch (\Throwable $e) {
                    Log::warning('Could not sync studio Stripe requirements for payment settings', [
                        'user_id' => $request->user()->id,
                        'studio_id' => $studio->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $accountId = $studio->resolveStripeAccountId();
                if ($accountId) {
                    try {
                        $studioStripeStatus = $stripeConnect->getOnboardingStatus($accountId);
                    } catch (ApiErrorException $e) {
                        Log::warning('Could not load studio Stripe status for payment settings', [
                            'user_id' => $request->user()->id,
                            'studio_id' => $studio->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return view('artist.settings.payment', [
            'userDetail' => $userDetail,
            ...$this->paymentStripeViewData($userDetail, $stripeConnect, $stripeStatus, $studioStripeStatus),
        ]);
    }

    /**
     * Dedicated page for Stripe-required follow-up information (already connected accounts).
     */
    public function stripeRequirements(Request $request, StripeConnectService $stripeConnect, StripeRequirementSyncService $stripeRequirementSync)
    {
        $userDetail = $request->user()->userDetail;
        $this->ensureDefaultArtistPaymentType($userDetail);

        if (
            ! $userDetail
            || ($userDetail->payment_type ?? '') !== 'artist_account'
            || empty($userDetail->stripe_account_id)
        ) {
            return redirect()
                ->route('settings.payment')
                ->with('error', 'Connect your Stripe payout account first.');
        }

        if (! $stripeConnect->isConfigured()) {
            return redirect()
                ->route('settings.payment')
                ->with('error', 'Stripe is not configured. Please contact support.');
        }

        try {
            $stripeRequirementSync->syncUserDetail($userDetail);
            $userDetail->refresh();
        } catch (\Throwable $e) {
            Log::warning('Could not sync Stripe requirements page', [
                'user_id' => $request->user()->id,
                'stripe_account_id' => $userDetail->stripe_account_id,
                'error' => $e->getMessage(),
            ]);
        }

        $stripeStatus = null;
        try {
            $stripeStatus = $stripeConnect->getOnboardingStatus($userDetail->stripe_account_id);
        } catch (ApiErrorException $e) {
            Log::warning('Could not load Stripe status for requirements page', [
                'user_id' => $request->user()->id,
                'stripe_account_id' => $userDetail->stripe_account_id,
                'error' => $e->getMessage(),
            ]);
        }

        $needsAction = $stripeStatus !== null
            ? $stripeConnect->accountNeedsUserSubmission($stripeStatus)
            : $this->artistStripeNeedsAction($userDetail, $stripeStatus, $stripeConnect);

        if (! $this->hasActiveArtistStripe($userDetail, $stripeStatus)) {
            return redirect()->route('settings.payment');
        }

        if (! $needsAction) {
            return redirect()
                ->route('settings.payment')
                ->with('success', 'Stripe does not need any more information from you right now.');
        }

        return view('artist.settings.stripe-requirements', [
            'userDetail' => $userDetail,
            'needsAction' => true,
            'stripeStatus' => $stripeStatus,
            'stripePublishableKey' => config('services.stripe.key'),
            'stripeConnectConfigured' => true,
            'stripeConnectLocale' => $stripeConnect->connectLocale(),
            'payoutBankCountryName' => $userDetail->payout_bank_country
                ? StripeConnectCountries::nameFor($userDetail->payout_bank_country)
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function paymentStripeViewData(
        ?UserDetail $userDetail,
        StripeConnectService $stripeConnect,
        ?array $stripeStatus,
        ?array $studioStripeStatus = null,
    ): array {
        $lockState = $this->paymentPayoutLockState($userDetail, $stripeStatus, $studioStripeStatus);

        return [
            'stripePublishableKey' => config('services.stripe.key'),
            'stripeConnectConfigured' => $stripeConnect->isConfigured(),
            'stripeStatus' => $stripeStatus,
            'studioStripeStatus' => $studioStripeStatus,
            'stripeConnectLocale' => $stripeConnect->connectLocale(),
            'payoutBankCountry' => $userDetail?->payout_bank_country,
            'payoutBankCountryName' => $userDetail?->payout_bank_country
                ? StripeConnectCountries::nameFor($userDetail->payout_bank_country)
                : null,
            'payoutWaitingListCountry' => $userDetail?->payout_waiting_list_country,
            'payoutRegistrationCountries' => StripeConnectCountries::registrationCountriesForSelect(),
            'stripeSupportedCountries' => StripeConnectCountries::supportedForSelect(),
            'stripeUnsupportedCountries' => StripeConnectCountries::unsupportedCountryNamesForWaitingList(),
            'userCountryUserBelongsIn' => auth()->user()?->country_user_belongs_in,
            ...$lockState,
        ];
    }

    /**
     * @return array{
     *     artistStripeConnected: bool,
     *     artistStripeNeedsAction: bool,
     *     studioStripeNeedsAction: bool,
     *     studioPayoutConnected: bool,
     *     payoutOptionLocked: bool
     * }
     */
    protected function paymentPayoutLockState(
        ?UserDetail $userDetail,
        ?array $stripeStatus = null,
        ?array $studioStripeStatus = null,
    ): array {
        $stripeConnect = app(StripeConnectService::class);
        $artistStripeConnected = $this->hasActiveArtistStripe($userDetail, $stripeStatus);
        $studioPayoutConnected = $this->hasActiveStudioPayout($userDetail);
        $studioPayoutCommitted = $this->hasStudioPayoutCommitted($userDetail);

        return [
            'artistStripeConnected' => $artistStripeConnected,
            'artistStripeNeedsAction' => $artistStripeConnected
                && $this->artistStripeNeedsAction($userDetail, $stripeStatus, $stripeConnect),
            'studioStripeNeedsAction' => $studioPayoutConnected
                && $this->studioStripeNeedsAction($userDetail, $studioStripeStatus, $stripeConnect),
            'studioPayoutConnected' => $studioPayoutConnected,
            'studioPayoutCommitted' => $studioPayoutCommitted,
            'payoutOptionLocked' => $artistStripeConnected || $studioPayoutCommitted,
        ];
    }

    protected function hasActiveArtistStripe(?UserDetail $userDetail, ?array $stripeStatus = null): bool
    {
        if (($userDetail?->payment_type ?? null) !== 'artist_account' || empty($userDetail->stripe_account_id)) {
            return false;
        }

        // Already linked/saved — still "connected" even if Stripe later needs more info.
        if (! empty($userDetail->stripe_requirement) || ($userDetail->payment_status ?? '') === 'approved') {
            return true;
        }

        if ($stripeStatus !== null) {
            return (bool) ($stripeStatus['details_submitted'] ?? $stripeStatus['submitted'] ?? false)
                || (bool) ($stripeStatus['complete'] ?? false);
        }

        $stripeConnect = app(StripeConnectService::class);
        if (! $stripeConnect->isConfigured()) {
            return false;
        }

        try {
            $status = $stripeConnect->getOnboardingStatus($userDetail->stripe_account_id);

            return (bool) ($status['details_submitted'] ?? $status['submitted'] ?? false)
                || (bool) ($status['complete'] ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>|null  $stripeStatus
     */
    protected function artistStripeNeedsAction(?UserDetail $userDetail, ?array $stripeStatus, StripeConnectService $stripeConnect): bool
    {
        if (($userDetail?->payment_type ?? null) !== 'artist_account' || empty($userDetail->stripe_account_id)) {
            return false;
        }

        // Button/form only when Stripe still has fields for the artist to submit.
        if ($stripeStatus !== null) {
            return $stripeConnect->accountNeedsUserSubmission($stripeStatus);
        }

        return ! empty($userDetail->stripe_requirement);
    }

    /**
     * @param  array<string, mixed>|null  $studioStripeStatus
     */
    protected function studioStripeNeedsAction(
        ?UserDetail $userDetail,
        ?array $studioStripeStatus,
        StripeConnectService $stripeConnect,
    ): bool {
        if (($userDetail?->payment_type ?? null) !== 'studio_account' || empty($userDetail->studio_id)) {
            return false;
        }

        if (($userDetail->payment_status ?? '') !== 'approved') {
            return false;
        }

        if ($studioStripeStatus !== null) {
            return $stripeConnect->accountNeedsUserSubmission($studioStripeStatus);
        }

        $studio = $userDetail->studio;

        return (bool) ($studio?->stripe_requirement || $userDetail->stripe_requirement);
    }

    protected function syncStripeRequirementsAfterConnect(UserDetail $userDetail): void
    {
        try {
            app(StripeRequirementSyncService::class)->syncUserDetail($userDetail);
        } catch (\Throwable $e) {
            Log::warning('Stripe requirement sync after connect failed', [
                'user_detail_id' => $userDetail->id,
                'stripe_account_id' => $userDetail->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            $userDetail->payment_status = 'pending';
            $userDetail->stripe_requirement = true;
            $userDetail->save();
        }
    }

    protected function hasActiveStudioPayout(?UserDetail $userDetail): bool
    {
        return ($userDetail?->payment_type ?? null) === 'studio_account'
            && ($userDetail->payment_status ?? null) === 'approved'
            && ! empty($userDetail->studio_id);
    }

    protected function hasStudioPayoutCommitted(?UserDetail $userDetail): bool
    {
        return ($userDetail?->payment_type ?? null) === 'studio_account'
            && ! empty($userDetail->studio_id)
            && ($userDetail->payment_status ?? null) !== 'rejected';
    }

    protected function disconnectStudioPayout(UserDetail $userDetail): void
    {
        $userDetail->payment_type = 'artist_account';
        $userDetail->studio_id = null;
        $userDetail->stripe_account_id = null;
        $userDetail->payment_status = null;
        $userDetail->save();
    }

    protected function ensureDefaultArtistPaymentType(?UserDetail $userDetail): void
    {
        if (! $userDetail) {
            return;
        }

        $current = (string) ($userDetail->payment_type ?? '');
        if (in_array($current, ['artist_account', 'studio_account'], true)) {
            return;
        }

        $userDetail->payment_type = 'artist_account';
        $userDetail->save();
    }

    protected function resolveOrCreateStudio(string $email, ?string $studioName = null): Studio
    {
        $studioEmail = strtolower(trim($email));
        $studio = Studio::firstWhere('email', $studioEmail);

        if (! $studio) {
            $studio = Studio::create([
                'name' => $studioName ?: 'Studio',
                'email' => $studioEmail,
            ]);
        }

        return $studio;
    }

    protected function hasLockedPayoutConnection(?UserDetail $userDetail, ?array $stripeStatus = null): bool
    {
        return $this->hasActiveArtistStripe($userDetail, $stripeStatus)
            || $this->hasStudioPayoutCommitted($userDetail);
    }

    protected function assertPaymentTypeCanChange(?UserDetail $userDetail, string $requestedType, ?array $stripeStatus = null): void
    {
        $currentType = $userDetail?->payment_type;
        if ($currentType === null || $currentType === '' || $currentType === $requestedType) {
            return;
        }

        if ($this->hasLockedPayoutConnection($userDetail, $stripeStatus)) {
            throw ValidationException::withMessages([
                'payment_type' => ['Disconnect your current payout setup before switching between Artist and Studio.'],
            ]);
        }
    }

    /**
     * Supported bank-account countries for this platform (from Stripe Country Spec API).
     */
    public function stripePayoutCountries(StripeCountrySpecService $countrySpecService)
    {
        if (! $countrySpecService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe is not configured.',
            ], 500);
        }

        $supported = $countrySpecService->supportedPayoutCountries();
        $countries = [];
        foreach ($supported as $code => $meta) {
            $countries[] = [
                'code' => $code,
                'name' => $meta['name'],
                'currency' => $meta['currency'],
            ];
        }

        usort($countries, fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        return response()->json([
            'success' => true,
            'countries' => $countries,
            'source' => 'stripe_country_spec',
        ]);
    }

    /**
     * Save the artist's bank account country before Stripe embedded onboarding.
     */
    public function savePayoutBankCountry(Request $request, StripeConnectService $stripeConnect)
    {
        if ($redirect = $this->ensurePaymentStripeAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to save bank country from this page.',
                'redirect' => $redirect->getTargetUrl(),
            ], 409);
        }

        $supportedCodes = array_map(
            fn (array $country) => $country['code'],
            StripeConnectCountries::registrationCountriesForSelect()
        );

        $validated = $request->validate([
            'payout_bank_country' => ['required', 'string', 'size:2', Rule::in($supportedCodes)],
        ], [
            'payout_bank_country.required' => 'Please select where your bank account is based.',
            'payout_bank_country.in' => 'The selected country is not supported for payouts.',
        ]);

        $user = $request->user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        $country = strtoupper($validated['payout_bank_country']);

        $user->country_user_belongs_in = $country;
        $user->save();

        if ($userDetail->payout_bank_country !== $country && $userDetail->stripe_account_id) {
            try {
                if ($stripeConnect->isConfigured()) {
                    $status = $stripeConnect->getOnboardingStatus($userDetail->stripe_account_id);
                    if (! ($status['complete'] ?? false)) {
                        $userDetail->stripe_account_id = null;
                    }
                }
            } catch (\Throwable $e) {
                $userDetail->stripe_account_id = null;
            }
        }

        $userDetail->payout_bank_country = $country;
        $userDetail->payout_waiting_list_country = null;
        $userDetail->payout_waiting_list_at = null;
        $currencySync = StripeConnectCountries::syncCurrencyFromBankCountry($userDetail);
        $userDetail->save();

        return response()->json([
            'success' => true,
            'payout_bank_country' => $country,
            'payout_bank_country_name' => StripeConnectCountries::nameFor($country),
            'currency_updated' => $currencySync['updated'],
            'currency' => $currencySync['currency'],
            'previous_currency' => $currencySync['previous'],
        ]);
    }

    /**
     * Join the payout waiting list when the artist's bank country is not supported.
     */
    public function savePayoutWaitingList(Request $request)
    {
        if ($redirect = $this->ensurePaymentStripeAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to join the waiting list from this page.',
                'redirect' => $redirect->getTargetUrl(),
            ], 409);
        }

        $unsupportedNames = StripeConnectCountries::unsupportedCountryNamesForWaitingList();

        $validated = $request->validate([
            'payout_waiting_list_country' => ['required', 'string', 'max:120', Rule::in($unsupportedNames)],
        ], [
            'payout_waiting_list_country.required' => 'Please select your country.',
            'payout_waiting_list_country.in' => 'Please select a valid country.',
        ]);

        $user = $request->user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        $countryName = $validated['payout_waiting_list_country'];

        $userDetail->payout_bank_country = null;
        $userDetail->stripe_account_id = null;
        $userDetail->payout_waiting_list_country = $countryName;
        $userDetail->payout_waiting_list_at = now();
        $userDetail->save();

        Log::info('Artist joined payout waiting list', [
            'user_id' => $user->id,
            'email' => $user->email,
            'country_name' => $countryName,
        ]);

        return response()->json([
            'success' => true,
            'country_name' => $countryName,
            'message' => 'Thanks! We will notify you when payouts become available in your country.',
        ]);
    }

    /**
     * Create (or reuse) a connected account and return an Account Session for embedded onboarding.
     */
    public function createStripeConnectSession(Request $request, StripeConnectService $stripeConnect)
    {
        if ($redirect = $this->ensurePaymentStripeAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to start Stripe onboarding from this page.',
                'redirect' => $redirect->getTargetUrl(),
            ], 409);
        }

        if (! $stripeConnect->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe is not configured. Please contact support.',
            ], 500);
        }

        try {
            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            if (! $userDetail->payout_bank_country || ! StripeConnectCountries::isSupported($userDetail->payout_bank_country)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your signup country is missing or not supported for payouts. Please contact support.',
                    'errors' => [
                        'payout_bank_country' => ['Your signup country is required for Stripe payouts.'],
                    ],
                ], 422);
            }

            if ($userDetail->payout_waiting_list_country) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payouts are not available in your country yet.',
                ], 422);
            }

            $phase = $request->input('phase');
            if ($phase !== null && $phase !== '') {
                $phase = (string) $phase;
                if (! in_array($phase, ['personal', 'identity', 'bank'], true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid Stripe onboarding phase.',
                    ], 422);
                }
            } else {
                $phase = null;
            }

            $session = $stripeConnect->createOnboardingSession(
                $user,
                $userDetail,
                $phase,
                $request->boolean('requirements_only')
            );

            return response()->json([
                'success' => true,
                'client_secret' => $session['client_secret'],
                'account_id' => $session['account_id'],
                'publishable_key' => config('services.stripe.key'),
                'collection_options' => $session['collection_options'],
                'phase' => $session['phase'],
                'phased' => $session['phased'],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect session creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not start Stripe onboarding: '.$e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Stripe Connect session creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not start Stripe onboarding.',
            ], 500);
        }
    }

    /**
     * Poll embedded onboarding completion for the connected account.
     */
    public function stripeConnectStatus(Request $request, StripeConnectService $stripeConnect)
    {
        if ($redirect = $this->ensurePaymentStripeAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to check Stripe status from this page.',
                'redirect' => $redirect->getTargetUrl(),
            ], 409);
        }

        $userDetail = $request->user()->userDetail;
        $accountId = $request->query('account_id');
        if (! is_string($accountId) || $accountId === '') {
            $accountId = $userDetail?->stripe_account_id;
        } elseif ($userDetail?->stripe_account_id !== null && $userDetail->stripe_account_id !== $accountId) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe account does not belong to this user.',
            ], 403);
        }

        if (! $accountId) {
            return response()->json([
                'success' => true,
                'complete' => false,
                'message' => 'No Stripe account linked yet.',
            ]);
        }

        if (! $stripeConnect->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe is not configured.',
            ], 500);
        }

        try {
            $status = $stripeConnect->getOnboardingStatus($accountId);
            $phaseStatus = $stripeConnect->getOnboardingPhaseStatus($accountId);

            return response()->json([
                'success' => true,
                ...$status,
                'phases' => $phaseStatus,
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not load Stripe account status.',
            ], 422);
        }
    }

    /**
     * Save onboarding profile step (avatar, name, username, mobile).
     */
    public function saveProfile(Request $request)
    {
        try {
            $user = $request->user();
            $userDetail = $user->userDetail;
            
            // Make avatar optional if user already has one (for profile update)
            $avatarRule = $userDetail && $userDetail->avatar 
                ? ['nullable'] 
                : ['required'];
            
            $validated = $request->validate([
                'avatar' => array_merge($avatarRule, ['image', 'mimes:jpg,jpeg,png,heif,heic', 'max:2048']),
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'user_name' => [
                    'required', 
                    'string', 
                    'min:1',
                    'max:30',
                    'regex:/^[A-Za-z0-9._]+$/',
                    'unique:user_details,user_name,' . ($userDetail ? $userDetail->id : 'NULL') . ',id',
                    new ReservedArtistUsername($user->email),
                ],
                'mobile_number' => [
                    'required', 
                    'string', 
                    'regex:/^\+[1-9]\d{1,14}$/',
                    'unique:user_details,mobile_number,' . ($userDetail ? $userDetail->id : 'NULL') . ',id'
                ],
            ], [
                'user_name.regex' => 'Username can only include letters, numbers, periods (.) and underscores (_).',
                'user_name.max' => 'Username must not be greater than 30 characters.',
                'mobile_number.regex' => 'Mobile number must be in E.164 format (example: +447911123456) with no spaces, dashes, or parentheses.',
            ]);

            $userDetail = $userDetail ?? UserDetail::create(['user_id' => $user->id]);

            // Handle avatar upload using helper function
            $avatarPath = $userDetail->avatar;
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($userDetail->avatar && file_exists(public_path($userDetail->avatar))) {
                    File::delete(public_path($userDetail->avatar));
                }
                
                $avatarPath = $this->imageUploader($request->file('avatar'), 'avatars');
            }

            // Update user's first_name and last_name
            $user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
            ]);

            $userDetail->update([
                'avatar' => $avatarPath,
                'user_name' => $validated['user_name'],
                'mobile_number' => $validated['mobile_number'],
                'current_step' => 2,
                'completed_steps' => array_unique(array_merge($userDetail->completed_steps ?? [], [1])),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile saved',
                'nextStep' => 2,
                'avatar' => $avatarPath ? asset($avatarPath) : null,
                'redirect' => route('onboarding.styles-social'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save styles & social (step 2 of 6).
     */
    public function saveStylesSocial(Request $request)
    {
        try {
            $maxYear = (int) date('Y');
            $allowedStyles = $this->activeStyleValues();
            $validated = $request->validate([
                'tattooing_since' => ['required', 'integer', 'min:1970', 'max:'.$maxYear],
                'primary_style' => ['required', 'string', Rule::in($allowedStyles)],
                'other_styles' => ['nullable', 'string', 'max:2000'],
                'social_links' => ['nullable', 'array'],
                'social_links.instagram' => ['nullable', 'string', 'max:255'],
                'social_links.tiktok' => ['nullable', 'string', 'max:255'],
                'social_links.youtube' => ['nullable', 'string', 'max:255'],
                'social_links.facebook' => ['nullable', 'string', 'max:255'],
                'social_links.website' => ['nullable', 'string', 'max:500'],
            ], [
                'tattooing_since.required' => 'Please select the year you started tattooing.',
                'primary_style.required' => 'Please select your primary style.',
            ]);

            $other = array_filter(array_map('trim', explode(',', (string) ($validated['other_styles'] ?? ''))));
            foreach ($other as $slug) {
                if (! in_array($slug, $allowedStyles, true)) {
                    throw ValidationException::withMessages([
                        'other_styles' => ['One or more additional styles are invalid. Please pick styles from the list.'],
                    ]);
                }
            }

            $social = SocialLinks::normalize($validated['social_links'] ?? []);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $stylePayload = array_filter([
                'tattooing_since' => $validated['tattooing_since'] ?? null,
                'primary_style' => $validated['primary_style'] ?? null,
                'other_styles' => $other ?: null,
            ], fn ($v) => $v !== null && $v !== [] && $v !== '');

            $userDetail->update([
                'tattoo_styles' => ! empty($stylePayload) ? $stylePayload : null,
                'social_links' => ! empty($social) ? $social : null,
                'current_step' => 3,
                'completed_steps' => array_unique(array_merge($userDetail->completed_steps ?? [], [2])),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Styles & social saved',
                'nextStep' => 3,
                'redirect' => route('onboarding.studio'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update styles & social (artist settings page).
     */
    public function updateStylesSocial(Request $request)
    {
        try {
            $maxYear = (int) date('Y');
            $allowedStyles = $this->activeStyleValues();
            $validated = $request->validate([
                'tattooing_since' => ['required', 'integer', 'min:1970', 'max:'.$maxYear],
                'primary_style' => ['required', 'string', Rule::in($allowedStyles)],
                'other_styles' => ['nullable', 'string', 'max:2000'],
                'social_links' => ['nullable', 'array'],
                'social_links.instagram' => ['nullable', 'string', 'max:255'],
                'social_links.tiktok' => ['nullable', 'string', 'max:255'],
                'social_links.youtube' => ['nullable', 'string', 'max:255'],
                'social_links.facebook' => ['nullable', 'string', 'max:255'],
                'social_links.website' => ['nullable', 'string', 'max:500'],
            ], [
                'tattooing_since.required' => 'Please select the year you started tattooing.',
                'primary_style.required' => 'Please select your primary style.',
            ]);

            $other = array_filter(array_map('trim', explode(',', (string) ($validated['other_styles'] ?? ''))));
            foreach ($other as $slug) {
                if (! in_array($slug, $allowedStyles, true)) {
                    throw ValidationException::withMessages([
                        'other_styles' => ['One or more additional styles are invalid. Please pick styles from the list.'],
                    ]);
                }
            }

            $social = SocialLinks::normalize($validated['social_links'] ?? []);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
            $stylePayload = array_filter([
                'tattooing_since' => $validated['tattooing_since'] ?? null,
                'primary_style' => $validated['primary_style'] ?? null,
                'other_styles' => $other ?: null,
            ], fn ($v) => $v !== null && $v !== [] && $v !== '');

            $userDetail->update([
                'tattoo_styles' => ! empty($stylePayload) ? $stylePayload : null,
                'social_links' => ! empty($social) ? $social : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Styles & social updated successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save onboarding studio / workspace address step.
     */
    public function saveStudio(Request $request)
    {
        try {
            $validated = $request->validate([
                'studio_name' => ['required', 'string', 'max:255'],
                'studio_address' => ['required', 'string'],
                'street_name' => ['required', 'string', 'max:255'],
                'street_number' => ['required', 'string', 'max:50'],
                'city' => ['required', 'string', 'max:255'],
                'state' => ['required', 'string', 'max:255'],
                'postal_code' => ['required', 'string', 'max:50'],
                'country' => ['required', 'string', 'max:255'],
                'google_maps_link' => ['nullable', 'url', 'max:500'],
                'workspace_type' => ['required', 'string', Rule::in(['private', 'shop', 'home'])],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ], [
                'workspace_type.required' => 'Please select a workspace type.',
                'workspace_type.in' => 'Please select a valid workspace type.',
            ]);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $studioData = [
                'studio_name' => $validated['studio_name'],
                'studio_address' => $validated['studio_address'],
                'street_name' => $validated['street_name'],
                'street_number' => $validated['street_number'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country'],
                'google_maps_link' => $validated['google_maps_link'] ?? null,
                'workspace_type' => $validated['workspace_type'] ?? null,
                'current_step' => 4,
                'completed_steps' => array_unique(array_merge($userDetail->completed_steps ?? [], [3])),
            ];

            $userDetail->update(array_merge(
                $studioData,
                $this->resolveLocationPreferences(
                    $validated['country'],
                    isset($validated['latitude']) ? (float) $validated['latitude'] : null,
                    isset($validated['longitude']) ? (float) $validated['longitude'] : null,
                    $validated['studio_address']
                )
            ));

            return response()->json([
                'success' => true,
                'message' => 'Studio information saved',
                'nextStep' => 4,
                'redirect' => route('onboarding.preferences'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Derive locale preferences from the studio location.
     *
     * Timezone comes from the Google Time Zone API (via coordinates, falling
     * back to geocoding the address). Date format and units follow the
     * United States (MM/DD/YYYY + inches) vs. rest-of-world (DD/MM/YYYY + cm)
     * rule.
     *
     * @return array{timezone?: string, date_time_format: string, size_unit: string}
     */
    private function resolveLocationPreferences(?string $country, ?float $latitude, ?float $longitude, ?string $address): array
    {
        $service = app(LocationPreferencesService::class);

        $preferences = [
            'date_time_format' => $service->dateFormatForCountry($country),
            'size_unit' => $service->sizeUnitForCountry($country),
        ];

        $timezone = $service->resolveTimezone($latitude, $longitude, $address);
        if ($timezone !== null) {
            $preferences['timezone'] = $timezone;
        }

        return $preferences;
    }

    /**
     * Update studio information (for settings page)
     */
    public function updateStudio(Request $request)
    {
        try {
            $validated = $request->validate([
                'studio_name' => ['required', 'string', 'max:255'],
                'studio_address' => ['required', 'string'],
                'street_name' => ['required', 'string', 'max:255'],
                'street_number' => ['required', 'string', 'max:50'],
                'city' => ['required', 'string', 'max:255'],
                'state' => ['required', 'string', 'max:255'],
                'postal_code' => ['required', 'string', 'max:50'],
                'country' => ['required', 'string', 'max:255'],
                'google_maps_link' => ['nullable', 'url', 'max:500'],
                'workspace_type' => ['required', 'string', Rule::in(['private', 'shop', 'home'])],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ], [
                'workspace_type.required' => 'Please select a workspace type.',
                'workspace_type.in' => 'Please select a valid workspace type.',
            ]);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $studioData = [
                'studio_name' => $validated['studio_name'],
                'studio_address' => $validated['studio_address'],
                'street_name' => $validated['street_name'],
                'street_number' => $validated['street_number'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country'],
                'google_maps_link' => $validated['google_maps_link'] ?? null,
                'workspace_type' => $validated['workspace_type'],
            ];

            // Only fill locale preferences that the artist hasn't set yet so we
            // never override their manual choices on the "Other" settings tab.
            $derived = $this->resolveLocationPreferences(
                $validated['country'],
                isset($validated['latitude']) ? (float) $validated['latitude'] : null,
                isset($validated['longitude']) ? (float) $validated['longitude'] : null,
                $validated['studio_address']
            );
            foreach ($derived as $key => $value) {
                if (empty($userDetail->{$key})) {
                    $studioData[$key] = $value;
                }
            }

            $userDetail->update($studioData);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Studio information updated successfully!',
                ]);
            }

            return redirect()->route('settings.studio')
                ->with('success', 'Studio information updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please fix the validation errors',
                    'errors' => $e->errors(),
                ], 422);
            }
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'An error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update calendar/scheduling type (for settings page)
     */
    /**
     * Update calendar / scheduling settings (settings page).
     */
    public function updateCalendar(Request $request)
    {
        try {
            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $validated = $this->validateCalendarPayload($request);
            $schedulingType = $validated['scheduling_type'];

            if ($schedulingType === 'auto') {
                $calendarConnected = ! empty($userDetail->google_calendar_token);

                if (! $calendarConnected) {
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Please connect your Google Calendar for auto scheduling.',
                            'errors' => ['google_calendar_connected' => ['Google Calendar connection is required for auto scheduling.']],
                        ], 422);
                    }

                    return redirect()->back()
                        ->with('error', 'Please connect your Google Calendar for auto scheduling.')
                        ->withInput();
                }
            } elseif ($schedulingType === 'managed') {
                $userDetail->update([
                    'google_calendar_token' => null,
                    'google_calendar_id' => null,
                ]);
            }

            $userDetail->update(array_merge(
                ['scheduling_type' => $schedulingType],
                $this->scheduleConsultationUpdateData($validated, $request)
            ));

            $message = $schedulingType === 'auto'
                ? 'Calendar settings updated successfully. Auto scheduling with Google Calendar is enabled.'
                : 'Calendar settings updated successfully. Managed scheduling is enabled.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('settings.calendar')
                ->with('success', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please fix the validation errors',
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'An error occurred: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update payment settings (for settings page)
     */
    public function updatePayment(Request $request)
    {
        try {
            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            if ((int) $request->input('disconnect_stripe', 0) === 1) {
                if (! $this->hasActiveArtistStripe($userDetail)) {
                    $msg = 'No connected Stripe payout to disconnect.';
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }

                    return redirect()->back()->with('error', $msg);
                }

                $userDetail->stripe_account_id = null;
                $userDetail->payment_status = null;
                $userDetail->stripe_requirement = false;
                $userDetail->stripe_requirement_email_sent_at = null;
                $userDetail->save();

                $msg = 'Stripe payout disconnected. You can now switch payout options or connect again.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => true, 'message' => $msg]);
                }

                return redirect()->route('settings.payment')->with('success', $msg);
            }

            if ((int) $request->input('resend_studio_email', 0) === 1) {
                return $this->resendStudioPayoutEmail($request, $user, $userDetail);
            }

            if ((int) $request->input('disconnect_studio', 0) === 1) {
                if (! $this->hasStudioPayoutCommitted($userDetail)) {
                    $msg = 'No linked studio payout to disconnect.';
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }

                    return redirect()->back()->with('error', $msg);
                }

                $this->disconnectStudioPayout($userDetail);

                $msg = 'Studio payout disconnected. You can now switch payout options or connect again.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => true, 'message' => $msg]);
                }

                return redirect()->route('settings.payment')->with('success', $msg);
            }

            if ((int) $request->input('update_payout_mode', 0) === 1) {
                $validated = $request->validate([
                    'payout_mode' => ['required', 'in:manual,automatic'],
                ], [
                    'payout_mode.required' => 'Please select a payout mode.',
                    'payout_mode.in' => 'Invalid payout mode selected.',
                ]);

                $userDetail->payout_mode = $validated['payout_mode'];
                $userDetail->save();

                $msg = $validated['payout_mode'] === 'automatic'
                    ? 'Payout mode updated to automatic.'
                    : 'Payout mode updated to manual.';

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $msg,
                        'payout_mode' => $validated['payout_mode'],
                    ]);
                }

                return redirect()->route('settings.payment')->with('success', $msg);
            }

            $rules = [
                'payment_type' => ['required', 'in:artist_account,studio_account'],
            ];

            $messages = [
                'payment_type.required' => 'Please select a payment type.',
                'payment_type.in' => 'Invalid payment type selected.',
            ];

            $paymentType = $request->payment_type;
            if ($paymentType === 'artist_account') {
                $rules['stripe_account_id'] = ['nullable', 'string', 'regex:/^acct_[a-zA-Z0-9]+$/'];
            } elseif ($paymentType === 'studio_account') {
                $rules['studio_email'] = ['required', 'email', 'max:255'];
                $messages['studio_email.required'] = 'Studio email is required.';
                $messages['studio_email.email'] = 'Please enter a valid email address.';
            }

            $validated = $request->validate($rules, $messages);
            $studio = null;

            $this->assertPaymentTypeCanChange($userDetail, $validated['payment_type']);

            $userDetail->payment_type = $validated['payment_type'];

            if ($paymentType === 'artist_account') {
                if ($userDetail->payout_waiting_list_country) {
                    $userDetail->stripe_account_id = null;
                    $userDetail->studio_id = null;
                    $userDetail->payment_status = 'pending';
                } else {
                    $stripeConnect = app(StripeConnectService::class);
                    if (! $stripeConnect->isConfigured()) {
                        throw ValidationException::withMessages([
                            'stripe_connect' => ['Stripe payout setup is not available right now. Please try again later.'],
                        ]);
                    }

                    $accountId = $request->input('stripe_account_id') ?: $userDetail->stripe_account_id;
                    if (! is_string($accountId) || $accountId === '' || ! $stripeConnect->isOnboardingSubmitted($accountId)) {
                        throw ValidationException::withMessages([
                            'stripe_connect' => ['Please complete Stripe payout setup before saving.'],
                        ]);
                    }

                    $userDetail->stripe_account_id = $accountId;
                    $userDetail->studio_id = null;

                    $currency = $stripeConnect->resolveCurrencyForAccount($accountId);
                    if ($currency !== null) {
                        $userDetail->currency = $currency;
                    }

                    $this->syncStripeRequirementsAfterConnect($userDetail);
                }
            } elseif ($paymentType === 'studio_account') {
                $studioName = $userDetail->studio_name ?? 'Studio';
                $studioEmail = strtolower(trim($validated['studio_email']));
                $studio = Studio::firstWhere('email', $studioEmail);
                if (!$studio) {
                    $studio = Studio::create([
                        'name' => $studioName,
                        'email' => $studioEmail,
                    ]);
                }

                $userDetail->studio_id = $studio->id;
                $userDetail->stripe_account_id = null;
                $userDetail->payment_status = 'pending';
            }

            $userDetail->save();

            if ($paymentType === 'studio_account') {
                $this->sendStudioPayoutInfoRequestEmail($user, $userDetail, $studio);
            }

            $userDetail->refresh();

            if ($request->expectsJson() || $request->ajax()) {
                $stripeConnect = app(StripeConnectService::class);
                $liveStripeStatus = null;
                if (
                    $paymentType === 'artist_account'
                    && $userDetail->stripe_account_id
                    && $stripeConnect->isConfigured()
                ) {
                    try {
                        $liveStripeStatus = $stripeConnect->getOnboardingStatus($userDetail->stripe_account_id);
                    } catch (\Throwable) {
                        $liveStripeStatus = null;
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment settings updated successfully!',
                    'artist_stripe_needs_action' => $paymentType === 'artist_account'
                        && $this->artistStripeNeedsAction($userDetail, $liveStripeStatus, $stripeConnect),
                    'payment_status' => $userDetail->payment_status,
                    'stripe_requirement' => (bool) ($userDetail->stripe_requirement ?? false),
                ]);
            }

            return redirect()->route('settings.payment')->with('success', 'Payment settings updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please fix the validation errors',
                    'errors' => $e->errors(),
                ], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Save onboarding calendar / scheduling type plus schedule & consultation rules.
     */
    public function saveCalendar(Request $request)
    {
        try {
            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $validated = $this->validateCalendarPayload($request);
            $schedulingType = $validated['scheduling_type'];

            if ($schedulingType === 'auto') {
                $calendarConnected = ! empty($userDetail->google_calendar_token);

                if (! $calendarConnected) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please connect your Google Calendar for auto scheduling.',
                        'errors' => [
                            'google_calendar_connected' => ['Google Calendar connection is required for auto scheduling.'],
                        ],
                    ], 422);
                }
            } elseif ($schedulingType === 'managed') {
                $userDetail->update([
                    'google_calendar_token' => null,
                    'google_calendar_id' => null,
                ]);
            }

            $completedSteps = $userDetail->completed_steps ?? [];
            if (! in_array(5, $completedSteps)) {
                $completedSteps[] = 5;
            }

            $userDetail->update(array_merge(
                [
                    'scheduling_type' => $schedulingType,
                    'current_step' => 6,
                    'completed_steps' => $completedSteps,
                ],
                $this->scheduleConsultationUpdateData($validated, $request)
            ));

            $message = $schedulingType === 'auto'
                ? 'Scheduling saved. Auto scheduling with Google Calendar is enabled.'
                : 'Scheduling saved. Managed scheduling is enabled.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'nextStep' => 6,
                'redirect' => route('onboarding.payment'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate scheduling type plus schedule rules and consultation settings.
     *
     * @return array<string, mixed>
     */
    private function validateCalendarPayload(Request $request): array
    {
        $requireConsultation = $request->has('require_consultation') && $request->require_consultation == '1';

        $validationRules = [
            'scheduling_type' => ['required', 'in:auto,managed'],
            'cancellation_window' => ['required'],
            'reschedule_times' => ['required'],
            'session_buffer_period' => ['required', 'integer', 'min:0'],
            'require_consultation' => ['nullable', 'boolean'],
        ];

        if ($requireConsultation) {
            $validationRules['session_type'] = ['required', 'in:online,physical,both'];
            $validationRules['session_duration_minutes'] = ['required', 'integer', 'min:15', 'max:480'];
            $validationRules['consultation_timing'] = ['required', 'in:combined,separate'];

            $consultationTiming = $request->input('consultation_timing');
            if ($consultationTiming === 'separate') {
                $validationRules['require_gap_between_consultation_tattoo'] = ['nullable', 'boolean'];
                $requireGap = $request->has('require_gap_between_consultation_tattoo') && $request->require_gap_between_consultation_tattoo == '1';
                if ($requireGap) {
                    $validationRules['consultation_tattoo_gap_value'] = ['required', 'integer', 'min:1'];
                } else {
                    $validationRules['consultation_tattoo_gap_value'] = ['nullable', 'integer', 'min:1'];
                }
            } else {
                $validationRules['require_gap_between_consultation_tattoo'] = ['nullable', 'boolean'];
                $validationRules['consultation_tattoo_gap_value'] = ['nullable', 'integer', 'min:1'];
            }
        } else {
            $validationRules['session_type'] = ['nullable', 'in:online,physical,both'];
            $validationRules['session_duration_minutes'] = ['nullable', 'integer', 'min:15', 'max:480'];
            $validationRules['consultation_timing'] = ['nullable', 'in:combined,separate'];
            $validationRules['require_gap_between_consultation_tattoo'] = ['nullable', 'boolean'];
            $validationRules['consultation_tattoo_gap_value'] = ['nullable', 'integer', 'min:1'];
        }

        return $request->validate($validationRules);
    }

    /**
     * Build schedule rules + consultation fields for UserDetail update.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function scheduleConsultationUpdateData(array $validated, Request $request): array
    {
        $requireConsultation = $request->has('require_consultation') && $request->require_consultation == '1';

        $updateData = [
            'cancellation_window' => $validated['cancellation_window'],
            'reschedule_times' => $validated['reschedule_times'],
            'session_buffer_period' => (int) $validated['session_buffer_period'],
            'require_consultation' => $requireConsultation,
        ];

        if ($requireConsultation) {
            $updateData['session_type'] = $validated['session_type'];
            $updateData['session_duration_minutes'] = (int) $validated['session_duration_minutes'];
            $updateData['consultation_timing'] = $validated['consultation_timing'];

            $consultationTiming = $validated['consultation_timing'] ?? null;
            if ($consultationTiming === 'separate') {
                $requireGap = isset($validated['require_gap_between_consultation_tattoo']) && $validated['require_gap_between_consultation_tattoo'];
                $updateData['require_gap_between_consultation_tattoo'] = $requireGap;
                if ($requireGap && isset($validated['consultation_tattoo_gap_value'])) {
                    $updateData['consultation_tattoo_gap_value'] = (int) $validated['consultation_tattoo_gap_value'];
                } else {
                    $updateData['consultation_tattoo_gap_value'] = null;
                }
                $updateData['consultation_tattoo_gap_unit'] = null;
            } else {
                $updateData['require_gap_between_consultation_tattoo'] = false;
                $updateData['consultation_tattoo_gap_value'] = null;
                $updateData['consultation_tattoo_gap_unit'] = null;
            }
        } else {
            $updateData['session_type'] = null;
            $updateData['session_duration_minutes'] = null;
            $updateData['consultation_timing'] = null;
            $updateData['require_gap_between_consultation_tattoo'] = false;
            $updateData['consultation_tattoo_gap_value'] = null;
            $updateData['consultation_tattoo_gap_unit'] = null;
        }

        return $updateData;
    }

    /**
     * Save payment preferences (currency, deposits, rates, booking fee).
     */
    public function savePreferences(Request $request)
    {
        try {
            $validationRules = [
                'currency' => ['required'],
                'timezone' => ['required', Rule::in(\DateTimeZone::listIdentifiers())],
                'date_time_format' => ['required', Rule::in(['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'])],
                'size_unit' => ['required', Rule::in(['cm', 'in'])],
                'minimum_deposit_amount' => ['required', 'numeric', 'min:0'],
                'minimum_deposit_type' => ['required'],
                'hourly_rate' => ['nullable', 'numeric', 'min:0'],
                'half_day_rate' => ['nullable', 'numeric', 'min:0'],
                'full_day_rate' => ['nullable', 'numeric', 'min:0'],
                'booking_fee_type' => ['required', 'in:client,artist,split'],
            ];

            $request->merge([
                'hourly_rate' => $request->filled('hourly_rate') ? $request->input('hourly_rate') : null,
                'half_day_rate' => $request->filled('half_day_rate') ? $request->input('half_day_rate') : null,
                'full_day_rate' => $request->filled('full_day_rate') ? $request->input('full_day_rate') : null,
            ]);

            $validated = $request->validate($validationRules);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $minimumDepositAmount = (float) $validated['minimum_deposit_amount'];

            $updateData = [
                'currency' => $validated['currency'],
                'timezone' => $validated['timezone'],
                'date_time_format' => $validated['date_time_format'],
                'size_unit' => $validated['size_unit'],
                'minimum_deposit_amount' => $minimumDepositAmount,
                'minimum_deposit_type' => $validated['minimum_deposit_type'],
                'hourly_rate' => isset($validated['hourly_rate']) ? (float) $validated['hourly_rate'] : null,
                'half_day_rate' => isset($validated['half_day_rate']) ? (float) $validated['half_day_rate'] : null,
                'full_day_rate' => isset($validated['full_day_rate']) ? (float) $validated['full_day_rate'] : null,
                'booking_fee_type' => $validated['booking_fee_type'],
            ];

            if ($user->on_boarding !== 'yes') {
                $updateData['current_step'] = 5;
                $updateData['completed_steps'] = array_unique(array_merge($userDetail->completed_steps ?? [], [4]));
            }

            $userDetail->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Payment settings saved',
                'nextStep' => 5,
                'redirect' => route('onboarding.calendar'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save onboarding payment / payout configuration (final step).
     */
    public function savePayment(Request $request)
    {
        try {
            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            if ((int) $request->input('resend_studio_email', 0) === 1) {
                return $this->resendStudioPayoutEmail($request, $user, $userDetail);
            }

            if ((int) $request->input('disconnect_studio', 0) === 1) {
                if (! $this->hasStudioPayoutCommitted($userDetail)) {
                    return response()->json(['success' => false, 'message' => 'No linked studio payout to disconnect.'], 422);
                }

                $this->disconnectStudioPayout($userDetail);

                return response()->json([
                    'success' => true,
                    'message' => 'Studio payout disconnected. You can now switch payout options or connect again.',
                ]);
            }

            if ($user->on_boarding === 'yes') {
                return response()->json([
                    'success' => true,
                    'message' => 'Onboarding already completed.',
                    'redirect' => authenticated_home_url(),
                ]);
            }

            // Base validation - payment_type is always required
            $rules = [
                'payment_type' => ['required', 'in:artist_account,studio_account'],
            ];

            $messages = [
                'payment_type.required' => 'Please select a payment type.',
                'payment_type.in' => 'Invalid payment type selected.',
            ];

            // Conditional validation based on payment_type
            $paymentType = $request->payment_type;

            if ($paymentType === 'studio_account') {
                $rules['studio_email'] = ['required', 'email', 'max:255'];
                $messages['studio_email.required'] = 'Studio email is required.';
                $messages['studio_email.email'] = 'Please enter a valid email address.';
            }

            $validated = $request->validate($rules, $messages);
            $studio = null;

            $stripeConnect = app(StripeConnectService::class);
            $stripeStatus = null;
            if ($userDetail->stripe_account_id && $stripeConnect->isConfigured()) {
                try {
                    $stripeStatus = $stripeConnect->getOnboardingStatus($userDetail->stripe_account_id);
                } catch (\Throwable) {
                    $stripeStatus = null;
                }
            }

            $this->assertPaymentTypeCanChange($userDetail, $validated['payment_type'], $stripeStatus);

            // Always set payment_type and completed_steps
            $userDetail->payment_type = $validated['payment_type'];
            $userDetail->completed_steps = array_unique(array_merge($userDetail->completed_steps ?? [], [6]));

            if ($paymentType === 'artist_account') {
                if ($userDetail->payout_waiting_list_country) {
                    $userDetail->studio_id = null;
                    $userDetail->stripe_account_id = null;
                    $userDetail->payment_status = 'pending';
                } else {
                    $stripeConnect = app(StripeConnectService::class);
                    if (! $stripeConnect->isConfigured()) {
                        throw ValidationException::withMessages([
                            'stripe_connect' => ['Stripe payout setup is not available right now. Please try again later or skip for now.'],
                        ]);
                    }

                    $userDetail->refresh();
                    $accountId = $userDetail->stripe_account_id;
                    if (! $accountId) {
                        try {
                            $accountId = $stripeConnect->ensureConnectedAccount($user, $userDetail);
                            $userDetail->refresh();
                        } catch (\Throwable) {
                            $accountId = null;
                        }
                    }

                    if (! $accountId || ! $stripeConnect->isOnboardingSubmitted($accountId)) {
                        throw ValidationException::withMessages([
                            'stripe_connect' => ['Please complete Stripe payout setup before finishing onboarding.'],
                        ]);
                    }

                    $userDetail->studio_id = null;
                    $this->syncStripeRequirementsAfterConnect($userDetail);
                }
            } elseif ($paymentType === 'studio_account') {
                // Studio account: find or create studio record
                $studioName = $userDetail->studio_name ?? 'Studio';
                $studioEmail = strtolower(trim($validated['studio_email']));
                $studio = Studio::firstWhere('email', $studioEmail);
                if (!$studio) {
                    $studio = Studio::create([
                        'name' => $studioName,
                        'email' => $studioEmail,
                    ]);
                }

                // Link artist to studio; studio submits bank details via emailed link
                $userDetail->studio_id = $studio->id;
                $userDetail->stripe_account_id = null;
                $userDetail->payment_status = 'pending';
            }

            $userDetail->save();

            if ($paymentType === 'studio_account') {
                $this->sendStudioPayoutInfoRequestEmail($user, $userDetail, $studio);
            }

            // Mark onboarding as complete
            $completingOnboarding = $user->on_boarding !== 'yes';
            $user->update(['on_boarding' => 'yes']);

            if ($completingOnboarding) {
                $this->sendArtistWelcomeEmail($user);
                app(\App\Services\MailcoachSubscriberService::class)
                    ->queueSubscribeUser($user, \App\Services\MailcoachSubscriberService::TAG_ARTIST);

                $this->seedDefaultQuestionSortingForArtist($user);
            }

            return response()->json([
                'success' => true,
                'message' => 'Onboarding completed successfully!',
                'redirect' => authenticated_home_url(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please fix the validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in savePayment', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Skip the payout step and finish onboarding (payout can be completed later in settings).
     */
    public function skipPayment(Request $request)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 6)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to skip from this page.',
                    'redirect' => $redirect->getTargetUrl(),
                ], 409);
            }

            return $redirect;
        }

        try {
            $user = $request->user();

            if ($user->on_boarding === 'yes') {
                return response()->json([
                    'success' => true,
                    'redirect' => authenticated_home_url(),
                ]);
            }

            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
            $userDetail->completed_steps = array_unique(array_merge($userDetail->completed_steps ?? [], [6]));
            $userDetail->save();

            $user->update(['on_boarding' => 'yes']);
            $this->sendArtistWelcomeEmail($user);
            app(\App\Services\MailcoachSubscriberService::class)
                ->queueSubscribeUser($user, \App\Services\MailcoachSubscriberService::TAG_ARTIST);

            $this->seedDefaultQuestionSortingForArtist($user);

            return response()->json([
                'success' => true,
                'message' => __('You can set up payouts later in your dashboard.'),
                'redirect' => authenticated_home_url(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in skipPayment', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Signed link: studio fills payout bank details (no login).
     */
    public function showStudioPayoutForm(Request $request, UserDetail $userDetail)
    {
        if (! $request->hasValidSignature()) {
            return view('studio.payout-form-result', [
                'success' => false,
                'message' => 'This link is invalid or has expired. Ask the artist to resend the payout request email.',
            ]);
        }

        if ($userDetail->payment_type !== 'studio_account' || empty($userDetail->studio_id)) {
            return view('studio.payout-form-result', [
                'success' => false,
                'message' => 'This payout request is no longer active.',
            ]);
        }

        $studio = Studio::find($userDetail->studio_id);
        if (! $studio) {
            return view('studio.payout-form-result', [
                'success' => false,
                'message' => 'Studio record was not found.',
            ]);
        }

        $artist = $userDetail->user;
        $artistName = trim(($artist->first_name ?? '').' '.($artist->last_name ?? ''));
        if ($artistName === '') {
            $artistName = $userDetail->user_name ?? $artist->email ?? 'Artist';
        }

        $stripeConnect = app(StripeConnectService::class);
        $studioAlreadyConnected = $studio->hasStripeConnect();
        $paymentStatus = (string) ($userDetail->payment_status ?? 'pending');

        if ($studioAlreadyConnected && $paymentStatus === 'approved' && $stripeConnect->isConfigured()) {
            try {
                app(StripeRequirementSyncService::class)->syncStudio($studio);
                $studio->refresh();
                $accountId = $studio->resolveStripeAccountId();
                $status = $accountId ? $stripeConnect->getOnboardingStatus($accountId) : null;
                if ($status && $stripeConnect->accountNeedsUserSubmission($status)) {
                    return redirect()->to(URL::temporarySignedRoute(
                        'studio.payout-info.stripe.requirements',
                        now()->addDays(14),
                        ['userDetail' => $userDetail->id]
                    ));
                }
            } catch (\Throwable) {
                // Fall through to normal payout form.
            }
        }

        $studioProfile = null;
        if ($studioAlreadyConnected) {
            $accountId = $studio->resolveStripeAccountId();
            if ($accountId) {
                $studioProfile = $stripeConnect->resolveStudioDisplayProfile($accountId, $studio);
            }
        }

        $approveUrl = URL::temporarySignedRoute(
            'studio.payout-artist-link.approve',
            now()->addDays(30),
            ['userDetail' => $userDetail->id]
        );
        $declineUrl = URL::temporarySignedRoute(
            'studio.payout-artist-link.decline',
            now()->addDays(30),
            ['userDetail' => $userDetail->id]
        );

        return view('studio.payout-form', [
            'userDetail' => $userDetail,
            'studio' => $studio,
            'artistName' => $artistName,
            'studioAlreadyConnected' => $studioAlreadyConnected,
            'studioProfile' => $studioProfile,
            'paymentStatus' => $paymentStatus,
            'approveUrl' => $approveUrl,
            'declineUrl' => $declineUrl,
            'stripeConnectConfigured' => $stripeConnect->isConfigured(),
            'stripePublishableKey' => config('services.stripe.key'),
            'stripeConnectLocale' => $stripeConnect->connectLocale(),
            'stripeSessionUrl' => URL::temporarySignedRoute(
                'studio.payout-info.stripe.session',
                now()->addDays(14),
                ['userDetail' => $userDetail->id]
            ),
            'stripeStatusUrl' => URL::temporarySignedRoute(
                'studio.payout-info.stripe.status',
                now()->addDays(14),
                ['userDetail' => $userDetail->id]
            ),
            'stripeCompleteUrl' => URL::temporarySignedRoute(
                'studio.payout-info.stripe.complete',
                now()->addDays(14),
                ['userDetail' => $userDetail->id]
            ),
            'stripeSupportedCountries' => StripeConnectCountries::supportedForSelect(),
        ]);
    }

    public function createStudioStripeSession(Request $request, UserDetail $userDetail, StripeConnectService $stripeConnect)
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['success' => false, 'message' => 'This link is invalid or has expired.'], 403);
        }

        if ($userDetail->payment_type !== 'studio_account' || empty($userDetail->studio_id)) {
            return response()->json(['success' => false, 'message' => 'This payout request is no longer active.'], 422);
        }

        $studio = Studio::find($userDetail->studio_id);
        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio record was not found.'], 404);
        }

        if (! $stripeConnect->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Stripe is not configured.'], 500);
        }

        $supportedCodes = array_map(
            fn (array $country) => $country['code'],
            StripeConnectCountries::supportedForSelect()
        );

        $validated = $request->validate([
            'business_type' => ['required', 'string', Rule::in(['individual', 'company'])],
            'country' => ['required', 'string', 'size:2', Rule::in($supportedCodes)],
            'industry' => ['required', 'string', Rule::in(['tattoo_studio', 'tattoo_beauty', 'other'])],
        ], [
            'business_type.required' => 'Please select whether you are an individual or a business.',
            'business_type.in' => 'Please select a valid account type.',
            'country.required' => 'Please select your country.',
            'country.in' => 'The selected country is not supported for payouts.',
            'industry.required' => 'Please select what best describes you.',
            'industry.in' => 'Please select a valid industry.',
        ]);

        $setup = [
            'business_type' => $validated['business_type'],
            'country' => strtoupper($validated['country']),
            'industry' => $validated['industry'],
        ];

        try {
            $session = $stripeConnect->createStudioOnboardingSession($studio, $userDetail, $setup);

            return response()->json([
                'success' => true,
                'client_secret' => $session['client_secret'],
                'account_id' => $session['account_id'],
                'collection_options' => $session['collection_options'],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Studio Stripe session creation failed', [
                'user_detail_id' => $userDetail->id,
                'studio_id' => $studio->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not start Stripe onboarding: '.$e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Studio Stripe session creation failed', [
                'user_detail_id' => $userDetail->id,
                'studio_id' => $studio->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Could not start Stripe onboarding.'], 500);
        }
    }

    public function studioStripeStatus(Request $request, UserDetail $userDetail, StripeConnectService $stripeConnect)
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired link.'], 403);
        }

        $accountId = $request->query('account_id');
        if (! is_string($accountId) || $accountId === '') {
            $studio = $userDetail->studio_id ? Studio::find($userDetail->studio_id) : null;
            $accountId = $studio?->resolveStripeAccountId() ?? $userDetail->stripe_account_id;
        }

        if (! $accountId) {
            return response()->json(['success' => true, 'complete' => false]);
        }

        try {
            $status = $stripeConnect->getOnboardingStatus($accountId);

            return response()->json([
                'success' => true,
                ...$status,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Could not read Stripe status.'], 500);
        }
    }

    /**
     * Signed link: studio submits currently-due Stripe requirements.
     */
    public function showStudioStripeRequirements(
        Request $request,
        UserDetail $userDetail,
        StripeConnectService $stripeConnect,
        StripeRequirementSyncService $stripeRequirementSync,
    ) {
        if (! $request->hasValidSignature()) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Invalid link',
                'message' => 'This link is invalid or has expired. Ask the artist to resend the requirements email.',
            ]);
        }

        if ($userDetail->payment_type !== 'studio_account' || empty($userDetail->studio_id)) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Request inactive',
                'message' => 'This payout request is no longer active.',
            ]);
        }

        $studio = Studio::find($userDetail->studio_id);
        if (! $studio || ! $studio->resolveStripeAccountId()) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Stripe not connected',
                'message' => 'Your studio has not completed Stripe payout setup yet.',
            ]);
        }

        if (! $stripeConnect->isConfigured()) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Unavailable',
                'message' => 'Stripe is not configured. Please contact support.',
            ]);
        }

        try {
            $stripeRequirementSync->syncStudio($studio);
            $studio->refresh();
        } catch (\Throwable $e) {
            Log::warning('Could not sync studio Stripe requirements page', [
                'studio_id' => $studio->id,
                'error' => $e->getMessage(),
            ]);
        }

        $stripeStatus = null;
        try {
            $stripeStatus = $stripeConnect->getOnboardingStatus($studio->resolveStripeAccountId());
        } catch (\Throwable $e) {
            Log::warning('Could not load studio Stripe status for requirements page', [
                'studio_id' => $studio->id,
                'error' => $e->getMessage(),
            ]);
        }

        $needsAction = $stripeStatus !== null
            ? $stripeConnect->accountNeedsUserSubmission($stripeStatus)
            : (bool) $studio->stripe_requirement;

        if (! $needsAction) {
            return view('studio.payout-form-result', [
                'success' => true,
                'title' => 'All set',
                'message' => 'Stripe does not need any more information from your studio right now.',
            ]);
        }

        $artist = $userDetail->user;
        $artistName = trim(($artist->first_name ?? '').' '.($artist->last_name ?? ''));
        if ($artistName === '') {
            $artistName = $userDetail->user_name ?? $artist->email ?? 'Artist';
        }

        return view('studio.stripe-requirements', [
            'userDetail' => $userDetail,
            'studio' => $studio,
            'artistName' => $artistName,
            'stripePublishableKey' => config('services.stripe.key'),
            'stripeConnectConfigured' => true,
            'stripeConnectLocale' => $stripeConnect->connectLocale(),
            'stripeSessionUrl' => URL::temporarySignedRoute(
                'studio.payout-info.stripe.requirements.session',
                now()->addDays(14),
                ['userDetail' => $userDetail->id]
            ),
            'stripeStatusUrl' => URL::temporarySignedRoute(
                'studio.payout-info.stripe.status',
                now()->addDays(14),
                ['userDetail' => $userDetail->id]
            ),
            'requirementsUrl' => URL::temporarySignedRoute(
                'studio.payout-info.stripe.requirements',
                now()->addDays(14),
                ['userDetail' => $userDetail->id]
            ),
        ]);
    }

    public function createStudioStripeRequirementsSession(
        Request $request,
        UserDetail $userDetail,
        StripeConnectService $stripeConnect,
    ) {
        if (! $request->hasValidSignature()) {
            return response()->json(['success' => false, 'message' => 'This link is invalid or has expired.'], 403);
        }

        if ($userDetail->payment_type !== 'studio_account' || empty($userDetail->studio_id)) {
            return response()->json(['success' => false, 'message' => 'This payout request is no longer active.'], 422);
        }

        $studio = Studio::find($userDetail->studio_id);
        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio record was not found.'], 404);
        }

        if (! $stripeConnect->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Stripe is not configured.'], 500);
        }

        try {
            $session = $stripeConnect->createStudioRequirementsSession($studio);

            return response()->json([
                'success' => true,
                'client_secret' => $session['client_secret'],
                'account_id' => $session['account_id'],
                'collection_options' => $session['collection_options'],
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not start Stripe requirements form: '.$e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Could not start Stripe requirements form.',
            ], 500);
        }
    }

    public function completeStudioStripeOnboarding(Request $request, UserDetail $userDetail, StripeConnectService $stripeConnect)
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['success' => false, 'message' => 'This link is invalid or has expired.'], 403);
        }

        if ($userDetail->payment_type !== 'studio_account' || empty($userDetail->studio_id)) {
            return response()->json(['success' => false, 'message' => 'This payout request is no longer active.'], 422);
        }

        $studio = Studio::find($userDetail->studio_id);
        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio record was not found.'], 404);
        }

        $validated = $request->validate([
            'account_id' => ['required', 'string', 'regex:/^acct_[a-zA-Z0-9]+$/'],
        ]);

        try {
            $stripeConnect->finalizeStudioOnboarding($studio, $userDetail, $validated['account_id']);

            return response()->json([
                'success' => true,
                'message' => 'Stripe payout setup completed successfully.',
                'redirect' => URL::temporarySignedRoute(
                    'studio.payout-info.show',
                    now()->addDays(14),
                    ['userDetail' => $userDetail->id, 'completed' => 1]
                ),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Signed link: studio approves this artist receiving payouts through the studio (no artist user_bank_details changes).
     */
    public function approveStudioArtistBankLink(Request $request, UserDetail $userDetail, StripeConnectService $stripeConnect)
    {
        if (! $request->hasValidSignature()) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Invalid link',
                'message' => 'This link is invalid or has expired. Ask the artist to resend the payout email.',
            ]);
        }

        if ($userDetail->payment_type !== 'studio_account' || empty($userDetail->studio_id)) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Request inactive',
                'message' => 'This payout request is no longer active.',
            ]);
        }

        $studio = Studio::find($userDetail->studio_id);
        if (! $studio || ! $studio->hasStripeConnect()) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Stripe not connected',
                'message' => 'Your studio has not completed Stripe payout setup yet. Please use the secure link from the latest email to connect Stripe first.',
            ]);
        }

        if (($userDetail->payment_status ?? '') === 'approved') {
            return view('studio.payout-form-result', [
                'success' => true,
                'title' => 'Already approved',
                'message' => 'This artist was already approved to receive payouts through your studio.',
            ]);
        }

        if (($userDetail->payment_status ?? '') === 'rejected') {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Request declined',
                'message' => 'This payout request was already declined.',
            ]);
        }

        $accountId = $studio->resolveStripeAccountId();
        $userDetail->stripe_account_id = $accountId;
        $userDetail->payment_status = 'approved';
        $userDetail->stripe_requirement = false;

        $currency = $stripeConnect->resolveCurrencyForAccount($accountId);
        if ($currency !== null) {
            $userDetail->currency = $currency;
        }

        $userDetail->save();

        return view('studio.payout-form-result', [
            'success' => true,
            'title' => 'Approved',
            'message' => 'Thank you. This artist is approved to receive payouts through your studio.',
        ]);
    }

    /**
     * Signed link: studio declines linking payout details for this artist.
     */
    public function declineStudioArtistBankLink(Request $request, UserDetail $userDetail)
    {
        if (! $request->hasValidSignature()) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Invalid link',
                'message' => 'This link is invalid or has expired. Ask the artist to resend the payout email.',
            ]);
        }

        if ($userDetail->payment_type !== 'studio_account' || empty($userDetail->studio_id)) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Request inactive',
                'message' => 'This payout request is no longer active.',
            ]);
        }

        if (($userDetail->payment_status ?? '') === 'approved') {
            return view('studio.payout-form-result', [
                'success' => true,
                'title' => 'Already linked',
                'message' => 'This artist was already approved to use your studio’s payout details. Nothing was changed.',
            ]);
        }

        if (($userDetail->payment_status ?? '') === 'rejected') {
            return view('studio.payout-form-result', [
                'success' => true,
                'title' => 'Already declined',
                'message' => 'You have already declined this request.',
            ]);
        }

        $artistUser = $userDetail->user;
        $studio = Studio::find($userDetail->studio_id);
        $studioName = $studio?->name ?? 'Studio';

        $userDetail->payment_status = 'rejected';
        $userDetail->studio_id = null;
        $userDetail->save();

        if ($artistUser) {
            $this->sendStudioPayoutDeclinedArtistEmail($artistUser, $studioName);
        }

        return view('studio.payout-form-result', [
            'success' => true,
            'title' => 'Declined',
            'message' => 'You declined linking your payout details to this artist. Their bank information on our platform was not updated.',
        ]);
    }

    public function studioPaymentStatus(Request $request)
    {
        $user = $request->user();
        $userDetail = $user->userDetail;

        if (!$userDetail || $userDetail->payment_type !== 'studio_account') {
            return redirect()->intended(authenticated_home_url());
        }

        $status = (string) ($userDetail->payment_status ?? 'pending');
        $message = match ($status) {
            'approved' => 'Your studio has submitted payout details. You have full access.',
            'rejected' => 'Your studio declined your payout request. You can connect your own bank account or invite a different studio from payment settings.',
            default => 'We are waiting for your studio to connect Stripe or approve your payout request via the email we sent them.',
        };

        return view('studio.payment-request-status', [
            'status' => $status,
            'message' => $message,
            'hideSidebar' => true,
        ]);
    }

    /**
     * Copy default consultation questions for a newly onboarded artist (once).
     */
    private function seedDefaultQuestionSortingForArtist(User $user): void
    {
        try {
            if (QuestionSorting::query()->where('user_id', $user->id)->exists()) {
                return;
            }

            $questions = QuestionSorting::query()
                ->where('user_id', 1)
                ->where('is_active', true)
                ->orderBy('order')
                ->get();

            foreach ($questions as $question) {
                QuestionSorting::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'question_id' => $question->question_id,
                    ],
                    [
                        'order' => $question->order,
                        'is_active' => $question->is_active,
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::error('Failed to seed default question sorting after onboarding', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendArtistWelcomeEmail(User $user): void
    {
        if ($user->role !== 'artist') {
            return;
        }

        try {
            Mail::to($user->email)->send(
                new ArtistWelcomeMail(
                    url(authenticated_home_url($user)),
                    route('settings.payment'),
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send artist welcome email after onboarding', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resend the studio payout setup email (reminder) without changing payout settings.
     */
    protected function resendStudioPayoutEmail(Request $request, User $user, UserDetail $userDetail)
    {
        if (($userDetail->payment_type ?? null) !== 'studio_account' || empty($userDetail->studio_id)) {
            $msg = 'No studio payout request to resend yet.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        if (($userDetail->payment_status ?? null) === 'approved') {
            $studio = Studio::find($userDetail->studio_id);
            $needsRequirements = (bool) ($studio?->stripe_requirement || $userDetail->stripe_requirement);

            if (! $needsRequirements && ! (int) $request->input('requirements_reminder', 0)) {
                $msg = 'Your studio payout is already connected.';

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }

                return redirect()->back()->with('error', $msg);
            }
        }

        $studio = Studio::find($userDetail->studio_id);
        if (! $studio) {
            $msg = 'Studio record not found. Please send a new studio email.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $requestedEmail = strtolower(trim((string) $request->input('studio_email', '')));
        $emailChanged = false;

        if ($requestedEmail !== '') {
            $validator = validator(['studio_email' => $requestedEmail], [
                'studio_email' => ['required', 'email', 'max:255'],
            ], [
                'studio_email.email' => 'Please enter a valid email address.',
            ]);

            if ($validator->fails()) {
                $msg = $validator->errors()->first('studio_email');

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg, 'errors' => $validator->errors()], 422);
                }

                return redirect()->back()->withErrors($validator)->withInput();
            }

            if ($requestedEmail !== strtolower(trim((string) $studio->email))) {
                $studio = $this->resolveOrCreateStudio($requestedEmail, $userDetail->studio_name ?? 'Studio');
                $userDetail->studio_id = $studio->id;
                $userDetail->payment_status = 'pending';
                $userDetail->save();
                $emailChanged = true;
            }
        }

        $requirementsReminder = (int) $request->input('requirements_reminder', 0) === 1
            || (
                ($userDetail->payment_status ?? '') === 'approved'
                && (bool) ($studio->stripe_requirement || $userDetail->stripe_requirement)
            );

        $this->sendStudioPayoutInfoRequestEmail($user, $userDetail, $studio, $requirementsReminder);

        $msg = $emailChanged
            ? 'Studio email updated. An invitation was sent to '.$studio->email.'.'
            : ($requirementsReminder
                ? 'Requirements reminder sent to your studio.'
                : 'Reminder sent to your studio.');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'studio_email' => $studio->email,
                'email_changed' => $emailChanged,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    private function sendStudioPayoutDeclinedArtistEmail(User $artistUser, string $studioName): void
    {
        $artistName = trim(($artistUser->first_name ?? '').' '.($artistUser->last_name ?? ''));
        if ($artistName === '') {
            $artistName = $artistUser->user_name ?? $artistUser->email ?? 'Artist';
        }

        try {
            Mail::to($artistUser->email)->send(new StudioPayoutDeclinedArtistMail(
                $artistName,
                $studioName,
                route('settings.payment'),
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send studio payout declined email to artist', [
                'user_id' => $artistUser->id,
                'email' => $artistUser->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendStudioPayoutInfoRequestEmail(
        User $artistUser,
        UserDetail $userDetail,
        Studio $studio,
        bool $requirementsReminder = false,
    ): void {
        $studio->refresh();

        $artistName = trim(($artistUser->first_name ?? '').' '.($artistUser->last_name ?? ''));
        if ($artistName === '') {
            $artistName = $artistUser->user_name ?? $artistUser->email ?? 'Artist';
        }

        $showApproveDecline = $studio->hasStripeConnect() && ! $requirementsReminder;

        $formUrl = $requirementsReminder
            ? URL::temporarySignedRoute(
                'studio.payout-info.stripe.requirements',
                now()->addDays(30),
                ['userDetail' => $userDetail->id]
            )
            : URL::temporarySignedRoute(
                'studio.payout-info.show',
                now()->addDays(30),
                ['userDetail' => $userDetail->id]
            );

        $approveUrl = $showApproveDecline
            ? URL::temporarySignedRoute(
                'studio.payout-artist-link.approve',
                now()->addDays(30),
                ['userDetail' => $userDetail->id]
            )
            : null;

        $declineUrl = $showApproveDecline
            ? URL::temporarySignedRoute(
                'studio.payout-artist-link.decline',
                now()->addDays(30),
                ['userDetail' => $userDetail->id]
            )
            : null;

        try {
            Mail::to($studio->email)->send(new StudioPayoutInfoRequestMail(
                $studio->name ?? 'Studio',
                $artistName,
                $formUrl,
                $showApproveDecline,
                $approveUrl,
                $declineUrl,
                $requirementsReminder,
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send studio payout info request email', [
                'studio_id' => $studio->id,
                'user_detail_id' => $userDetail->id,
                'requirements_reminder' => $requirementsReminder,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Uppercase and strip spaces / hyphens for validation and storage of account or IBAN.
     */
    private function normalizedPayoutAccountNumber(string $value): string
    {
        return strtoupper(preg_replace('/[\s\-]+/', '', trim($value)));
    }

    private function looksLikeIban(string $normalized): bool
    {
        // Shortest IBANs are 15 characters (e.g. NO); longest 34.
        return (bool) preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $normalized);
    }

    /**
     * ISO 13616 mod-97-10 check (IBAN).
     */
    private function isValidIban(string $iban): bool
    {
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }
        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }
        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $numeric = '';
        for ($i = 0, $len = strlen($rearranged); $i < $len; $i++) {
            $c = $rearranged[$i];
            $numeric .= ctype_alpha($c) ? (string) (ord($c) - 55) : $c;
        }

        return $this->mod97String($numeric) === 1;
    }

    private function mod97String(string $numeric): int
    {
        if (function_exists('gmp_init')) {
            return (int) gmp_intval(gmp_mod(gmp_init($numeric), '97'));
        }
        $parts = str_split($numeric, 7);
        $rem = 0;
        foreach ($parts as $part) {
            $rem = (int) (($rem.$part) % 97);
        }

        return $rem;
    }

    private function upsertUserBankDetails($user, array $validated): void
    {
        UserBankDetail::updateOrCreate(
            ['user_id' => $user->id],
            [
                'account_holder_name' => trim((string) $validated['account_holder_name']),
                'bank_name' => trim((string) $validated['bank_name']),
                'account_number' => trim((string) $validated['account_number']),
                'swift_bic' => strtoupper(trim((string) $validated['swift_bic'])),
                'bank_currency' => strtoupper(trim((string) $validated['currency'])),
            ]
        );
    }

    /**
     * Get current progress
     */
    public function getProgress(Request $request)
    {
        $user = $request->user();
        $userDetail = $user->userDetail;

        if (!$userDetail) {
            return response()->json([
                'currentStep' => 1,
                'completedSteps' => [],
            ]);
        }

        return response()->json([
            'currentStep' => $userDetail->current_step ?? 1,
            'completedSteps' => $userDetail->completed_steps ?? [],
        ]);
    }

    public function imageUploader($file,$path)
    {
            $extension = $file->getClientOriginalExtension();
            $extension=time().'.'.$extension;
            $file->move(public_path('uploads/'.$path.'/'),$extension);
            $fileName = '/uploads/'.$path.'/'.$extension;
            return $fileName;
    }
}
