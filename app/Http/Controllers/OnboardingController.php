<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserBankDetail;
use App\Models\UserStudio;
use App\Models\UserPayout;
use App\Models\Studio;
use App\Models\Style;
use App\Mail\ArtistSwitchedToDirectStudioMail;
use App\Mail\ArtistWelcomeMail;
use App\Mail\StudioPayoutDeclinedArtistMail;
use App\Mail\StudioPayoutInfoRequestMail;
use App\Services\ArtistStudioJoinRequestNotifier;
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
use Illuminate\Support\Facades\Schema;
use App\Rules\ReservedArtistUsername;
use App\Rules\UniquePublicUsername;
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

    /** Optional services offered alongside styles (must match onboarding UI). */
    private const EXTRA_SERVICE_OPTIONS = [
        'Touch-ups',
        'Cover-ups',
        'Blackout',
        'Other',
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
     * Currency is locked from the artist's signup / studio country (matches O4-payments UI).
     *
     * @return array{code: string, symbol: string, country_name: string, label: string}
     */
    private function resolveLockedPaymentCurrency(?User $user, ?UserDetail $userDetail): array
    {
        $iso = strtoupper(trim((string) ($user?->country_user_belongs_in ?? '')));
        $countryName = null;
        $currency = null;

        if (strlen($iso) === 2 && ctype_alpha($iso)) {
            $countryName = StripeConnectCountries::nameFor($iso);
            $currency = StripeConnectCountries::currencyForCountry($iso);
        }

        if (! $currency && $userDetail) {
            $studioCountry = trim((string) ($userDetail->country ?? ''));
            if ($studioCountry !== '') {
                if (strlen($studioCountry) === 2 && ctype_alpha($studioCountry)) {
                    $iso = strtoupper($studioCountry);
                    $countryName = StripeConnectCountries::nameFor($iso) ?: $studioCountry;
                    $currency = StripeConnectCountries::currencyForCountry($iso);
                } else {
                    $countryName = $studioCountry;
                    foreach (config('stripe_connect_countries.supported', []) as $code => $meta) {
                        if (strcasecmp((string) ($meta['name'] ?? ''), $studioCountry) === 0) {
                            $currency = strtoupper((string) ($meta['currency'] ?? ''));
                            $countryName = (string) ($meta['name'] ?? $studioCountry);
                            break;
                        }
                    }
                }
            }
        }

        if (! $currency) {
            $currency = strtoupper(trim((string) ($userDetail?->currency ?: 'EUR'))) ?: 'EUR';
        }
        if (! $countryName) {
            $countryName = 'your country';
        }

        $symbol = $this->currencySymbolForCode($currency);

        return [
            'code' => $currency,
            'symbol' => $symbol,
            'country_name' => $countryName,
            'label' => $currency.' ('.$symbol.')',
        ];
    }

    private function currencySymbolForCode(string $code): string
    {
        $map = [
            'EUR' => '€', 'GBP' => '£', 'USD' => '$', 'CHF' => 'CHF', 'SEK' => 'kr',
            'DKK' => 'kr', 'NOK' => 'kr', 'PLN' => 'zł', 'CZK' => 'Kč', 'HUF' => 'Ft',
            'RON' => 'lei', 'BGN' => 'лв', 'CAD' => 'C$', 'AUD' => 'A$', 'NZD' => 'NZ$',
            'SGD' => 'S$', 'HKD' => 'HK$', 'JPY' => '¥', 'MXN' => 'MX$', 'BRL' => 'R$',
            'AED' => 'د.إ', 'ISK' => 'kr',
        ];
        if (isset($map[$code])) {
            return $map[$code];
        }
        try {
            $parts = (new \NumberFormatter('en', \NumberFormatter::CURRENCY))->formatCurrency(0, $code);
            if (is_string($parts)) {
                return trim(preg_replace('/[\d\s.,]/u', '', $parts) ?: $code);
            }
        } catch (\Throwable $e) {
        }

        return $code;
    }

    private function isoCodeFromCountryName(string $countryName): ?string
    {
        $needle = strtolower(trim($countryName));
        if ($needle === '') {
            return null;
        }

        if (strlen($needle) === 2 && ctype_alpha($needle)) {
            return strtoupper($needle);
        }

        $aliases = [
            'united states of america' => 'US',
            'usa' => 'US',
            'uk' => 'GB',
            'great britain' => 'GB',
            'czech republic' => 'CZ',
            'czechia' => 'CZ',
        ];
        if (isset($aliases[$needle])) {
            return $aliases[$needle];
        }

        foreach (array_merge(
            config('stripe_connect_countries.supported', []),
            StripeConnectCountries::supported()
        ) as $code => $meta) {
            if (strcasecmp((string) ($meta['name'] ?? ''), $countryName) === 0) {
                return strtoupper((string) $code);
            }
        }

        foreach (StripeConnectCountries::registrationCountriesForSelect() as $item) {
            if (strcasecmp((string) ($item['name'] ?? ''), $countryName) === 0) {
                return strtoupper((string) ($item['code'] ?? ''));
            }
        }

        return null;
    }

    /**
     * Normalize extra services from the styles-social form into JSON for user_details.extra_services.
     *
     * @return array{services: list<string>, other: string|null}|null
     */
    private function normalizeExtraServices(Request $request): ?array
    {
        $selected = $request->input('extra_services', []);
        if (! is_array($selected)) {
            $selected = [];
        }

        $services = [];
        foreach ($selected as $value) {
            $value = trim((string) $value);
            if ($value !== '' && in_array($value, self::EXTRA_SERVICE_OPTIONS, true)) {
                $services[] = $value;
            }
        }
        $services = array_values(array_unique($services));

        $hasOther = in_array('Other', $services, true);
        $otherText = $hasOther
            ? trim((string) $request->input('extra_service_other', ''))
            : '';

        if ($otherText !== '') {
            $otherText = mb_substr($otherText, 0, 40);
        } else {
            $otherText = null;
        }

        if ($services === [] && $otherText === null) {
            return null;
        }

        return [
            'services' => $services,
            'other' => $otherText,
        ];
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
            'activeNav' => 'styles',
            'styleOptions' => $this->activeStyleOptions(),
        ]);
    }

    public function studio(Request $request)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 3)) {
            return $redirect;
        }

        $inviteService = app(\App\Services\StudioArtistInviteService::class);
        $user = $request->user();
        $lockedLink = $inviteService->activeInviteLinkedStudio($user);
        $pendingInvite = ($lockedLink || $inviteService->wasSkippedInOnboarding())
            ? null
            : $inviteService->pendingInviteForUser($user);

        return view('onboarding.studio', $this->onboardingViewData($request) + [
            'activeNav' => 'studio',
            'pendingStudioInvite' => $pendingInvite
                ? $inviteService->presentInviteForOnboarding($pendingInvite)
                : null,
            'lockedStudioFromInvite' => $lockedLink
                ? $inviteService->presentLockedStudio($lockedLink)
                : null,
        ]);
    }

    public function acceptStudioInvite(Request $request)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 3)) {
            return $redirect;
        }

        $inviteService = app(\App\Services\StudioArtistInviteService::class);
        $user = $request->user();
        $invite = $inviteService->pendingInviteForUser($user);

        if (! $invite) {
            return response()->json([
                'success' => false,
                'message' => 'No pending studio invite found.',
            ], 404);
        }

        try {
            $inviteService->accept($invite, $user);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Could not accept invite',
                'errors' => $e->errors(),
            ], 422);
        }

        $userDetail = $user->userDetail ?? \App\Models\UserDetail::create(['user_id' => $user->id]);
        $userDetail->update([
            'current_step' => 4,
            'completed_steps' => array_values(array_unique(array_merge($userDetail->completed_steps ?? [], [3]))),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invite accepted. Studio details are locked.',
            'nextStep' => 4,
            'redirect' => route('onboarding.preferences'),
        ]);
    }

    public function skipStudioInvite(Request $request)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 3)) {
            return $redirect;
        }

        app(\App\Services\StudioArtistInviteService::class)->markSkippedInOnboarding();

        return response()->json([
            'success' => true,
            'message' => 'Continue with your own studio details.',
            'reload' => true,
        ]);
    }

    public function preferences(Request $request)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 4)) {
            return $redirect;
        }

        $data = $this->onboardingViewData($request);
        $lockedCurrency = $this->resolveLockedPaymentCurrency($request->user(), $data['userDetail']);

        return view('onboarding.preferences', $data + [
            'activeNav' => 'payments',
            'lockedCurrency' => $lockedCurrency,
        ]);
    }

    /**
     * Update signup country (and locked currency) from Payments onboarding "Change country".
     */
    public function savePreferencesCountry(Request $request, LocationPreferencesService $locationPreferences)
    {
        try {
            $validated = $request->validate([
                'country_name' => ['required', 'string', 'max:120'],
            ], [
                'country_name.required' => 'Please select a country.',
            ]);

            $iso = $this->isoCodeFromCountryName($validated['country_name']);
            if ($iso === null || ! StripeConnectCountries::isRegistrationCountry($iso)) {
                throw ValidationException::withMessages([
                    'country_name' => ['Please select a valid country.'],
                ]);
            }

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $user->country_user_belongs_in = $iso;
            $user->save();

            $prefs = $locationPreferences->preferencesForCountryCode($iso);
            $locked = $this->resolveLockedPaymentCurrency($user->fresh(), $userDetail->fresh());

            $userDetail->update([
                'currency' => $locked['code'],
                'timezone' => $prefs['timezone'],
                'date_time_format' => $prefs['date_time_format'],
                'size_unit' => $prefs['size_unit'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Country updated',
                'country_name' => $locked['country_name'],
                'currency' => $locked['code'],
                'currency_symbol' => $locked['symbol'],
                'currency_label' => $locked['label'],
                'timezone' => $prefs['timezone'],
                'date_time_format' => $prefs['date_time_format'],
                'size_unit' => $prefs['size_unit'],
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
        $payout = $userDetail?->activePayout();
        $stripeStatus = null;
        if ($payout?->stripe_account_id && $stripeConnect->isConfigured()) {
            try {
                $stripeStatus = $stripeConnect->getOnboardingStatus($payout->stripe_account_id);
            } catch (ApiErrorException $e) {
                Log::warning('Could not load Stripe Connect status for onboarding payment step', [
                    'user_id' => $request->user()->id,
                    'stripe_account_id' => $payout->stripe_account_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('onboarding.payment', $this->onboardingViewData($request) + $this->paymentStripeViewData($userDetail, $stripeConnect, $stripeStatus) + [
            'activeNav' => 'payouts',
        ]);
    }

    public function paymentSettings(Request $request, StripeConnectService $stripeConnect, StripeRequirementSyncService $stripeRequirementSync)
    {
        $userDetail = $request->user()->userDetail;
        $this->ensureDefaultArtistPaymentType($userDetail);
        $stripeStatus = null;
        $studioStripeStatus = null;
        $payout = $userDetail?->activePayout();
        $artistStripeAccountId = trim((string) ($payout?->stripe_account_id ?? ''));
        $paymentType = $this->resolvedPaymentType($userDetail);

        if (
            $userDetail
            && $artistStripeAccountId !== ''
            && $stripeConnect->isConfigured()
        ) {
            try {
                $stripeRequirementSync->syncUserDetail($userDetail);
                $userDetail->refresh();
                $payout = $userDetail->activePayout();
                $artistStripeAccountId = trim((string) ($payout?->stripe_account_id ?? ''));
            } catch (\Throwable $e) {
                Log::warning('Could not sync Stripe requirements for payment settings', [
                    'user_id' => $request->user()->id,
                    'stripe_account_id' => $artistStripeAccountId,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($artistStripeAccountId !== '') {
                try {
                    $stripeStatus = $stripeConnect->getOnboardingStatus($artistStripeAccountId);
            } catch (ApiErrorException $e) {
                Log::warning('Could not load Stripe Connect status for payment settings', [
                    'user_id' => $request->user()->id,
                        'stripe_account_id' => $artistStripeAccountId,
                    'error' => $e->getMessage(),
                ]);
                }
            }
        }

        if (
            $userDetail
            && $paymentType === 'studio_account'
            && ($payout?->status ?? '') === 'approved'
            && $userDetail->resolvedStudioId()
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
        $payout = $userDetail?->activePayout();
        $stripeAccountId = trim((string) ($payout?->stripe_account_id ?? ''));

        if (! $userDetail || $stripeAccountId === '') {
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
            $payout = $userDetail->activePayout();
            $stripeAccountId = trim((string) ($payout?->stripe_account_id ?? ''));
        } catch (\Throwable $e) {
            Log::warning('Could not sync Stripe requirements page', [
                'user_id' => $request->user()->id,
                'stripe_account_id' => $stripeAccountId,
                'error' => $e->getMessage(),
            ]);
        }

        if ($stripeAccountId === '') {
            return redirect()
                ->route('settings.payment')
                ->with('error', 'Connect your Stripe payout account first.');
        }

        $stripeStatus = null;
        try {
            $stripeStatus = $stripeConnect->getOnboardingStatus($stripeAccountId);
        } catch (ApiErrorException $e) {
            Log::warning('Could not load Stripe status for requirements page', [
                'user_id' => $request->user()->id,
                'stripe_account_id' => $stripeAccountId,
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

        $bankCountry = $payout?->payout_bank_country;

        return view('artist.settings.stripe-requirements', [
            'userDetail' => $userDetail,
            'needsAction' => true,
            'stripeStatus' => $stripeStatus,
            'stripePublishableKey' => config('services.stripe.key'),
            'stripeConnectConfigured' => true,
            'stripeConnectLocale' => $stripeConnect->connectLocale(),
            'payoutBankCountryName' => $bankCountry
                ? StripeConnectCountries::nameFor($bankCountry)
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

        $disconnectedPayouts = $userDetail?->user_id
            ? UserPayout::disconnectedForUser((int) $userDetail->user_id)
            : collect();

        return [
            'stripePublishableKey' => config('services.stripe.key'),
            'stripeConnectConfigured' => $stripeConnect->isConfigured(),
            'stripeStatus' => $stripeStatus,
            'studioStripeStatus' => $studioStripeStatus,
            'stripeConnectLocale' => $stripeConnect->connectLocale(),
            'payoutBankCountry' => $userDetail?->activePayout()?->payout_bank_country,
            'payoutBankCountryName' => ($userDetail?->activePayout()?->payout_bank_country)
                ? StripeConnectCountries::nameFor($userDetail->activePayout()->payout_bank_country)
                : null,
            'payoutWaitingListCountry' => $userDetail?->activePayout()?->payout_waiting_list_country,
            'payoutRegistrationCountries' => StripeConnectCountries::registrationCountriesForSelect(),
            'stripeSupportedCountries' => StripeConnectCountries::supportedForSelect(),
            'stripeUnsupportedCountries' => StripeConnectCountries::unsupportedCountryNamesForWaitingList(),
            'userCountryUserBelongsIn' => auth()->user()?->country_user_belongs_in,
            'disconnectedPayouts' => $disconnectedPayouts,
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
            // Stripe alone must not lock Studio out — artist can still invite a studio.
            // Lock only after the studio invite is saved/sent.
            'payoutOptionLocked' => $studioPayoutCommitted,
        ];
    }

    /**
     * Artist vs studio payout intent — user_studios (payout=1 + revenue_split), else pending join for UI.
     */
    protected function resolvedPaymentType(?UserDetail $userDetail): string
    {
        if (! $userDetail?->user_id) {
            return 'artist_account';
        }

        if ($userDetail->resolvedPayoutPaymentType() === 'studio_account') {
            return 'studio_account';
        }

        if (UserStudio::tableReady() && UserStudio::pendingJoinForUser((int) $userDetail->user_id)) {
            return 'studio_account';
        }

        return 'artist_account';
    }

    protected function hasActiveArtistStripe(?UserDetail $userDetail, ?array $stripeStatus = null): bool
    {
        $payout = $userDetail?->activePayout();
        if (empty($payout?->stripe_account_id)) {
            return false;
        }

        if (! empty($payout->stripe_requirement) || ($payout->status ?? '') === 'approved') {
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
            $status = $stripeConnect->getOnboardingStatus($payout->stripe_account_id);

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
        $payout = $userDetail?->activePayout();
        if (empty($payout?->stripe_account_id)) {
            return false;
        }

        if ($stripeStatus !== null) {
            return $stripeConnect->accountNeedsUserSubmission($stripeStatus);
        }

        return ! empty($payout->stripe_requirement);
    }

    /**
     * @param  array<string, mixed>|null  $studioStripeStatus
     */
    protected function studioStripeNeedsAction(
        ?UserDetail $userDetail,
        ?array $studioStripeStatus,
        StripeConnectService $stripeConnect,
    ): bool {
        $payout = $userDetail?->activePayout();
        if ($this->resolvedPaymentType($userDetail) !== 'studio_account' || ! $userDetail?->resolvedStudioId()) {
            return false;
        }

        if (($payout?->status ?? '') !== 'approved') {
            return false;
        }

        if ($studioStripeStatus !== null) {
            return $stripeConnect->accountNeedsUserSubmission($studioStripeStatus);
        }

        $studio = $userDetail?->studio;

        return (bool) ($studio?->stripe_requirement || $payout?->stripe_requirement);
    }

    protected function syncStripeRequirementsAfterConnect(UserDetail $userDetail): void
    {
        $payout = $userDetail->activePayout();
        try {
            app(StripeRequirementSyncService::class)->syncUserDetail($userDetail);
        } catch (\Throwable $e) {
            Log::warning('Stripe requirement sync after connect failed', [
                'user_detail_id' => $userDetail->id,
                'stripe_account_id' => $payout?->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            UserPayout::upsertActiveForUser((int) $userDetail->user_id, [
                'status' => UserPayout::STATUS_PENDING,
                'stripe_requirement' => true,
            ]);
        }
    }

    protected function hasActiveStudioPayout(?UserDetail $userDetail): bool
    {
        if (! $userDetail || $this->resolvedPaymentType($userDetail) !== 'studio_account') {
            return false;
        }

        if (! UserStudio::tableReady()) {
            return false;
        }

        $link = UserStudio::payoutLinkForUser((int) $userDetail->user_id);
        if (! $link) {
            return false;
        }

        // Confirmed only after the studio accepts (connection_type flipped on accept).
        if (($link->connection_type ?? '') !== UserStudio::CONNECTION_ON_BOOKPAY) {
            return false;
        }

        $studio = $link->relationLoaded('studio')
            ? $link->studio
            : Studio::query()->find($link->studio_id);

        return filled($studio?->resolveStripeAccountId());
    }

    protected function hasStudioPayoutCommitted(?UserDetail $userDetail): bool
    {
        if (! $userDetail) {
            return false;
        }

        $payout = $userDetail->activePayout();
        if (($payout?->status ?? null) === 'rejected') {
            return false;
        }

        return UserStudio::tableReady()
            ? UserStudio::payoutLinkForUser((int) $userDetail->user_id) !== null
            : (bool) $userDetail->resolvedStudioId();
    }

    protected function disconnectStudioPayout(UserDetail $userDetail): void
    {
        UserStudio::clearStudioPayout((int) $userDetail->user_id);

        if (Schema::hasColumn($userDetail->getTable(), 'studio_id')) {
        $userDetail->studio_id = null;
        $userDetail->save();
        }

        // Keep artist stripe_account_id on the payout row; studio link lives on user_studios.
        UserPayout::upsertActiveForUser((int) $userDetail->user_id, [
            'status' => UserPayout::STATUS_DRAFT,
        ]);
    }

    /**
     * Persist only columns that still exist on user_details (dual-write during migration).
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function onlyExistingUserDetailColumns(UserDetail $userDetail, array $fields): array
    {
        $table = $userDetail->getTable();
        $payload = [];

        foreach ($fields as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $payload[$column] = $value;
            }
        }

        return $payload;
    }

    protected function ensureDefaultArtistPaymentType(?UserDetail $userDetail): void
    {
        if (! $userDetail?->user_id) {
            return;
        }

        if ($userDetail->activePayout()) {
            return;
        }

        $bankCountry = strtoupper(trim((string) ($userDetail->user?->country_user_belongs_in ?? '')));

        UserPayout::upsertActiveForUser((int) $userDetail->user_id, [
            'status' => UserPayout::STATUS_DRAFT,
            'payout_bank_country' => $bankCountry !== '' ? $bankCountry : null,
        ]);
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
        $currentType = $this->resolvedPaymentType($userDetail);
        if ($currentType === $requestedType) {
            return;
        }

        // Studio selected + Stripe connected, but invite email never sent: allow switching to Artist.
        if (
            $currentType === 'studio_account'
            && $requestedType === 'artist_account'
            && ! $this->hasStudioPayoutCommitted($userDetail)
        ) {
            return;
        }

        // Artist direct → studio revenue split invite (keeps artist Stripe connected).
        if ($currentType === 'artist_account' && $requestedType === 'studio_account') {
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

        $payout = $userDetail->activePayout();
        $clearStripe = false;
        if (($payout?->payout_bank_country ?? null) !== $country && $payout?->stripe_account_id) {
            try {
                if ($stripeConnect->isConfigured()) {
                    $status = $stripeConnect->getOnboardingStatus($payout->stripe_account_id);
                    if (! ($status['complete'] ?? false)) {
                        $clearStripe = true;
                    }
                }
            } catch (\Throwable $e) {
                $clearStripe = true;
            }
        }

        UserPayout::upsertActiveForUser((int) $user->id, array_filter([
            'payout_bank_country' => $country,
            'payout_waiting_list_country' => null,
            'payout_waiting_list_at' => null,
            'stripe_account_id' => $clearStripe ? null : ($payout?->stripe_account_id),
            'connected_at' => $clearStripe ? null : ($payout?->connected_at),
        ], fn ($v) => true));

        $currencySync = StripeConnectCountries::syncCurrencyFromBankCountry($userDetail->fresh() ?? $userDetail);
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
     * Start (or resume) a pending payout change draft. Current active payout stays active until Stripe finishes.
     */
    public function changePayoutAccount(Request $request, StripeConnectService $stripeConnect)
    {
        if ($redirect = $this->ensurePaymentStripeAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to change payout account from this page.',
                'redirect' => $redirect->getTargetUrl(),
            ], 409);
        }

        if (! $stripeConnect->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe is not configured. Please contact support.',
            ], 500);
        }

        $supportedCodes = array_keys(StripeConnectCountries::supported());

        $validated = $request->validate([
            'payout_bank_country' => ['required', 'string', 'size:2', Rule::in($supportedCodes)],
        ], [
            'payout_bank_country.required' => 'Please select where your new bank account is based.',
            'payout_bank_country.in' => 'The selected country is not supported for payouts.',
        ]);

        $user = $request->user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
        $country = strtoupper($validated['payout_bank_country']);
        $previous = $userDetail->activePayout();

        $payout = UserPayout::preparePendingChangeForUser((int) $user->id, $country, [
            'status' => UserPayout::STATUS_DRAFT,
            'payout_waiting_list_country' => null,
            'payout_waiting_list_at' => null,
            'payout_mode' => $previous?->payout_mode ?: 'automatic',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Connect your new Stripe account for '.$country.'.',
            'payout_id' => $payout->id,
            'payout_bank_country' => $country,
            'payout_bank_country_name' => StripeConnectCountries::nameFor($country),
            'reused' => filled($payout->stripe_account_id),
            'current_still_active' => (bool) $previous?->is_active,
        ]);
    }

    /**
     * After Stripe finishes on a pending change draft: activate it and disconnect the current payout.
     */
    public function finalizeChangePayoutAccount(Request $request, StripeConnectService $stripeConnect)
    {
        if ($redirect = $this->ensurePaymentStripeAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to finalize payout account change from this page.',
                'redirect' => $redirect->getTargetUrl(),
            ], 409);
        }

        $validated = $request->validate([
            'payout_id' => ['required', 'integer'],
            'stripe_account_id' => ['required', 'string', 'regex:/^acct_[a-zA-Z0-9]+$/'],
        ]);

        $user = $request->user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        $pending = UserPayout::query()
            ->where('user_id', $user->id)
            ->where('id', (int) $validated['payout_id'])
            ->where('is_active', false)
            ->first();

        if (! $pending || $pending->stripe_account_id !== $validated['stripe_account_id']) {
            return response()->json([
                'success' => false,
                'message' => 'That payout change is no longer available.',
            ], 422);
        }

        if (! $stripeConnect->isConfigured() || ! $stripeConnect->isOnboardingSubmitted($pending->stripe_account_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete Stripe onboarding before activating this account.',
            ], 422);
        }

        try {
            $payout = UserPayout::promotePendingChangeForUser((int) $user->id, (int) $pending->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($payout->payout_bank_country) {
            $user->country_user_belongs_in = strtoupper((string) $payout->payout_bank_country);
            $user->save();
        }

        $currency = $stripeConnect->resolveCurrencyForAccount($payout->stripe_account_id);
        if ($currency !== null) {
            $userDetail->currency = $currency;
        } else {
            StripeConnectCountries::syncCurrencyFromBankCountry($userDetail->fresh() ?? $userDetail);
        }
        $userDetail->save();

        try {
            $this->syncStripeRequirementsAfterConnect($userDetail->fresh() ?? $userDetail);
        } catch (\Throwable) {
            // Requirements sync can retry later.
        }

        $fresh = $userDetail->fresh()?->activePayout() ?? $payout;

        return response()->json([
            'success' => true,
            'message' => 'New payout account is now active.',
            'payout_id' => $fresh->id,
            'artist_stripe_needs_action' => (bool) ($fresh->stripe_requirement ?? false),
            'payout_bank_country' => $fresh->payout_bank_country,
            'payout_bank_country_name' => $fresh->payout_bank_country
                ? StripeConnectCountries::nameFor($fresh->payout_bank_country)
                : null,
        ]);
    }

    /**
     * Reactivate a previously disconnected payout account (makes it active; current one inactive).
     */
    public function activatePayoutAccount(Request $request, StripeConnectService $stripeConnect)
    {
        if ($redirect = $this->ensurePaymentStripeAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to activate a payout account from this page.',
                'redirect' => $redirect->getTargetUrl(),
            ], 409);
        }

        $validated = $request->validate([
            'payout_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        try {
            $payout = UserPayout::reactivateForUser((int) $user->id, (int) $validated['payout_id']);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($payout->payout_bank_country) {
            $user->country_user_belongs_in = strtoupper((string) $payout->payout_bank_country);
            $user->save();
        }

        $currencySync = StripeConnectCountries::syncCurrencyFromBankCountry($userDetail->fresh() ?? $userDetail);
        $userDetail->save();

        if ($stripeConnect->isConfigured() && filled($payout->stripe_account_id)) {
            try {
                $this->syncStripeRequirementsAfterConnect($userDetail->fresh() ?? $userDetail);
            } catch (\Throwable) {
                // Status still updated; requirements sync can retry later.
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Payout account activated.',
            'payout_id' => $payout->id,
            'payout_bank_country' => $payout->payout_bank_country,
            'payout_bank_country_name' => $payout->payout_bank_country
                ? StripeConnectCountries::nameFor($payout->payout_bank_country)
                : null,
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

        UserPayout::upsertActiveForUser((int) $user->id, [
            'status' => UserPayout::STATUS_DRAFT,
            'payout_bank_country' => null,
            'stripe_account_id' => null,
            'connected_at' => null,
            'payout_waiting_list_country' => $countryName,
            'payout_waiting_list_at' => now(),
        ]);

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

            $pendingPayoutId = (int) $request->input('payout_id', 0);
            $sessionPayout = null;

            if ($pendingPayoutId > 0) {
                $sessionPayout = UserPayout::query()
                    ->where('user_id', $user->id)
                    ->where('id', $pendingPayoutId)
                    ->where('is_active', false)
                    ->whereIn('status', [UserPayout::STATUS_DRAFT, UserPayout::STATUS_PENDING])
                    ->whereNull('disconnected_at')
                    ->first();

                if (! $sessionPayout) {
                return response()->json([
                    'success' => false,
                        'message' => 'That payout change draft is no longer available.',
                    ], 422);
                }
            } else {
                $requestedPaymentType = (string) $request->input('payment_type', '');
                $payout = $userDetail->activePayout();
                if (
                    $requestedPaymentType === 'studio_account'
                    && ($payout?->status ?? '') === 'approved'
                    && ! $userDetail->resolvedStudioId()
                ) {
                    $payout = UserPayout::upsertActiveForUser((int) $user->id, [
                        'status' => UserPayout::STATUS_PENDING,
                    ]);
                } elseif (! $payout) {
                    $payout = UserPayout::upsertActiveForUser((int) $user->id, [
                        'status' => UserPayout::STATUS_DRAFT,
                    ]);
                }
                $sessionPayout = $payout;
            }

            $bankCountry = $sessionPayout?->payout_bank_country
                ?: $userDetail->activePayout()?->payout_bank_country
                ?: strtoupper(trim((string) ($user->country_user_belongs_in ?? '')));

            if (! $bankCountry || ! StripeConnectCountries::isSupported($bankCountry)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your signup country is missing or not supported for payouts. Please contact support.',
                    'errors' => [
                        'payout_bank_country' => ['Your signup country is required for Stripe payouts.'],
                    ],
                ], 422);
            }

            if ($pendingPayoutId < 1 && $userDetail->activePayout()?->payout_waiting_list_country) {
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
                $request->boolean('requirements_only'),
                $sessionPayout
            );

            return response()->json([
                'success' => true,
                'client_secret' => $session['client_secret'],
                'account_id' => $session['account_id'],
                'payout_id' => $sessionPayout?->id,
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
        $payout = $userDetail?->activePayout();
        $accountId = $request->query('account_id');
        if (! is_string($accountId) || $accountId === '') {
            $accountId = $payout?->stripe_account_id;
        } elseif ($request->user()) {
            $ownsAccount = UserPayout::query()
                ->where('user_id', $request->user()->id)
                ->where('stripe_account_id', $accountId)
                ->exists();
            if (! $ownsAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe account does not belong to this user.',
            ], 403);
            }
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
                    new UniquePublicUsername(ignoreUserDetailId: $userDetail?->id),
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
                'extra_services' => ['nullable', 'array'],
                'extra_services.*' => ['string', Rule::in(self::EXTRA_SERVICE_OPTIONS)],
                'extra_service_other' => ['nullable', 'string', 'max:40'],
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
            $extraServices = $this->normalizeExtraServices($request);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $stylePayload = array_filter([
                'tattooing_since' => $validated['tattooing_since'] ?? null,
                'primary_style' => $validated['primary_style'] ?? null,
                'other_styles' => $other ?: null,
            ], fn ($v) => $v !== null && $v !== [] && $v !== '');

            $userDetail->update([
                'tattoo_styles' => ! empty($stylePayload) ? $stylePayload : null,
                'extra_services' => $extraServices,
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
                'extra_services' => ['nullable', 'array'],
                'extra_services.*' => ['string', Rule::in(self::EXTRA_SERVICE_OPTIONS)],
                'extra_service_other' => ['nullable', 'string', 'max:40'],
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
            $extraServices = $this->normalizeExtraServices($request);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
            $stylePayload = array_filter([
                'tattooing_since' => $validated['tattooing_since'] ?? null,
                'primary_style' => $validated['primary_style'] ?? null,
                'other_styles' => $other ?: null,
            ], fn ($v) => $v !== null && $v !== [] && $v !== '');

            $userDetail->update([
                'tattoo_styles' => ! empty($stylePayload) ? $stylePayload : null,
                'extra_services' => $extraServices,
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
            $user = $request->user();
            $inviteService = app(\App\Services\StudioArtistInviteService::class);
            if ($inviteService->activeInviteLinkedStudio($user)) {
                $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
                $userDetail->update([
                    'current_step' => 4,
                    'completed_steps' => array_values(array_unique(array_merge($userDetail->completed_steps ?? [], [3]))),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Studio details are locked from your invite.',
                    'nextStep' => 4,
                    'redirect' => route('onboarding.preferences'),
                ]);
            }

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
                'studio_relationship_type' => ['nullable', 'string', Rule::in(UserDetail::STUDIO_RELATIONSHIP_TYPES)],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ], [
                'workspace_type.required' => 'Please select a workspace type.',
                'workspace_type.in' => 'Please select a valid workspace type.',
                'studio_relationship_type.in' => 'Please select a valid relationship type.',
            ]);

            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $clearRelationship = $request->input('studio_path') === 'own_space';
            $relationship = ! empty($validated['studio_relationship_type'])
                ? $validated['studio_relationship_type']
                : null;

            $workplace = [
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
                'relationship' => $relationship,
            ];

            UserStudio::syncWorkplace((int) $user->id, $workplace, $clearRelationship);

            $legacyStudio = $this->onlyExistingUserDetailColumns($userDetail, [
                'studio_name' => $workplace['studio_name'],
                'studio_address' => $workplace['studio_address'],
                'street_name' => $workplace['street_name'],
                'street_number' => $workplace['street_number'],
                'state' => $workplace['state'],
                'postal_code' => $workplace['postal_code'],
                'google_maps_link' => $workplace['google_maps_link'],
                'workspace_type' => $workplace['workspace_type'],
                'studio_relationship_type' => $clearRelationship ? null : $relationship,
            ]);

            $studioData = array_merge($legacyStudio, [
                'city' => $validated['city'],
                'country' => $validated['country'],
                'current_step' => 4,
                'completed_steps' => array_unique(array_merge($userDetail->completed_steps ?? [], [3])),
            ]);

            $userDetail->update(array_merge(
                $studioData,
                $this->resolveLocationPreferences(
                    $validated['country'],
                    isset($validated['latitude']) ? (float) $validated['latitude'] : null,
                    isset($validated['longitude']) ? (float) $validated['longitude'] : null,
                    $validated['studio_address']
                )
            ));

            // Manual setup during onboarding: dismiss pending invite card going forward.
            $inviteService->markSkippedInOnboarding();

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
                'workspace_type' => ['required', 'string', Rule::in(['private', 'shop', 'home', 'collective'])],
                'relationship' => ['nullable', 'string', Rule::in(UserStudio::RELATIONSHIP_TYPES)],
                'studio_relationship_type' => ['nullable', 'string', Rule::in(UserStudio::RELATIONSHIP_TYPES)],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ], [
                'workspace_type.required' => 'Please select a workspace type.',
                'workspace_type.in' => 'Please select a valid workspace type.',
            ]);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $relationship = $validated['relationship']
                ?? $validated['studio_relationship_type']
                ?? null;

            $workplace = [
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
                'relationship' => $relationship,
            ];

            UserStudio::syncWorkplace((int) $user->id, $workplace);

            $studioData = array_merge(
                $this->onlyExistingUserDetailColumns($userDetail, [
                    'studio_name' => $workplace['studio_name'],
                    'studio_address' => $workplace['studio_address'],
                    'street_name' => $workplace['street_name'],
                    'street_number' => $workplace['street_number'],
                    'state' => $workplace['state'],
                    'postal_code' => $workplace['postal_code'],
                    'google_maps_link' => $workplace['google_maps_link'],
                    'workspace_type' => $workplace['workspace_type'],
                    'studio_relationship_type' => $relationship,
                ]),
                [
                    'city' => $validated['city'],
                    'country' => $validated['country'],
                ]
            );

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
     * Update booking rules + consultation settings (Schedule → Booking rules).
     * Does not change scheduling_type / Google Calendar connection.
     */
    public function updateBookingRules(Request $request)
    {
        try {
            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $validated = $this->validateBookingRulesPayload($request);
            $userDetail->update($this->scheduleConsultationUpdateData($validated, $request));

            $message = 'Booking rules updated successfully.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('schedule.booking-rules')
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
     * Update scheduling type only (Schedule → Calendar sync).
     * Does not change booking rules / consultation fields.
     */
    public function updateCalendarSync(Request $request)
    {
        try {
            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $validated = $request->validate([
                'scheduling_type' => ['required', 'in:auto,managed'],
            ]);

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

            $userDetail->update(['scheduling_type' => $schedulingType]);

            $message = $schedulingType === 'auto'
                ? 'Calendar sync updated. Auto scheduling with Google Calendar is enabled.'
                : 'Calendar sync updated. Managed scheduling is enabled.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('schedule.calendar-sync')
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

                UserPayout::upsertActiveForUser((int) $user->id, [
                    'stripe_account_id' => null,
                    'status' => UserPayout::STATUS_DISCONNECTED,
                    'stripe_requirement' => false,
                    'stripe_requirement_email_sent_at' => null,
                    'connected_at' => null,
                    'disconnected_at' => now(),
                ]);

                $msg = 'Stripe payout disconnected. You can now switch payout options or connect again.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => true, 'message' => $msg]);
                }

                return redirect()->route('settings.payment')->with('success', $msg);
            }

            if ((int) $request->input('resend_studio_email', 0) === 1) {
                return $this->resendStudioPayoutEmail($request, $user, $userDetail);
            }

            if ((int) $request->input('save_studio_split', 0) === 1) {
                return $this->saveStudioSplitRelationship($request, $userDetail);
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

                $msg = 'Studio payout disconnected. You can invite a studio again or switch payout options.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => true, 'message' => $msg]);
                }

                return redirect()->route('settings.payment')->with('success', $msg);
            }

            if ((int) $request->input('switch_to_artist', 0) === 1) {
                if (! $this->hasActiveArtistStripe($userDetail)) {
                    $msg = 'Connect your Stripe account first before switching to direct payment.';
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }

                    return redirect()->back()->with('error', $msg);
                }

                $userStudioId = (int) $request->input('user_studio_id', 0);
                $studioLink = null;
                if ($userStudioId > 0 && UserStudio::tableReady()) {
                    $studioLink = UserStudio::query()
                        ->where('user_id', $user->id)
                        ->where('id', $userStudioId)
                        ->where('status', UserStudio::STATUS_ACTIVE)
                        ->first();
                }

                // Per-studio switch (multi-studio payouts): only clear that workplace's split flag.
                if ($studioLink) {
                    if (! $studioLink->payout || $studioLink->revenue_split === null) {
                        $msg = 'That studio is already on direct payment.';
                        if ($request->expectsJson() || $request->ajax()) {
                            return response()->json(['success' => false, 'message' => $msg], 422);
                        }

                        return redirect()->back()->with('error', $msg);
                    }

                    $previousArtistPercent = (int) $studioLink->revenue_split;
                    $studioLink->loadMissing(['studio', 'user.userDetail']);

                    $studioLink->payout = false;
                    $studioLink->connection_type = UserStudio::CONNECTION_NOT_CONNECTED;
                    $studioLink->save();

                    $this->notifyStudioArtistSwitchedToDirect($studioLink, $previousArtistPercent);

                    $activePayout = $userDetail->activePayout();
                    $artistStripeAccountId = $activePayout?->stripe_account_id;
                    $remainingSplit = UserStudio::payoutLinkForUser((int) $user->id);
                    if ($remainingSplit) {
                        UserPayout::upsertActiveForUser((int) $user->id, [
                            'type' => UserPayout::TYPE_STUDIO,
                            'stripe_account_id' => $artistStripeAccountId,
                            'studio_id' => $remainingSplit->studio_id,
                            'user_studio_id' => $remainingSplit->id,
                            'studio_revenue_artist_percent' => $remainingSplit->revenue_split,
                            'studio_relationship_type' => $remainingSplit->relationship,
                        ]);
                        $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, [
                            'studio_id' => $remainingSplit->studio_id,
                            'studio_revenue_artist_percent' => $remainingSplit->revenue_split,
                            'studio_relationship_type' => $remainingSplit->relationship,
                        ]));
                        $userDetail->save();
                    } else {
                        UserPayout::upsertActiveForUser((int) $user->id, [
                            'type' => UserPayout::TYPE_ARTIST,
                            'status' => filled($artistStripeAccountId) ? UserPayout::STATUS_APPROVED : UserPayout::STATUS_PENDING,
                            'stripe_account_id' => $artistStripeAccountId,
                            'studio_id' => null,
                            'user_studio_id' => null,
                        ]);
                        $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, [
                            'payment_type' => 'artist_account',
                        ]));
                        $userDetail->save();
                    }

                    $studioName = trim((string) ($studioLink->studio?->name ?: $studioLink->studio_name ?: 'that studio'));
                    $msg = 'You now get paid directly at '.$studioName.'.';
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json(['success' => true, 'message' => $msg]);
                    }

                    return redirect()->route('settings.payment')->with('success', $msg);
                }

                // Global switch (single-studio / legacy).
                if ($this->resolvedPaymentType($userDetail) !== 'studio_account') {
                    $msg = 'You are already on artist direct payment.';
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }

                    return redirect()->back()->with('error', $msg);
                }

                $splitLinks = UserStudio::tableReady()
                    ? UserStudio::query()
                        ->where('user_id', $user->id)
                        ->where('status', UserStudio::STATUS_ACTIVE)
                        ->where('payout', true)
                        ->whereNotNull('revenue_split')
                        ->with(['studio', 'user.userDetail'])
                        ->get()
                    : collect();

                $activePayout = $userDetail->activePayout();
                $artistStripeAccountId = $activePayout?->stripe_account_id;
                UserStudio::clearStudioPayout((int) $user->id);

                foreach ($splitLinks as $splitLink) {
                    $this->notifyStudioArtistSwitchedToDirect(
                        $splitLink,
                        (int) $splitLink->revenue_split,
                    );
                }

                $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, [
                    'studio_id' => null,
                    'payment_type' => 'artist_account',
                    'studio_revenue_artist_percent' => null,
                    'studio_relationship_type' => null,
                ]));
                $userDetail->save();

                UserPayout::upsertActiveForUser((int) $user->id, [
                    'status' => filled($artistStripeAccountId) ? UserPayout::STATUS_APPROVED : UserPayout::STATUS_PENDING,
                    'stripe_account_id' => $artistStripeAccountId,
                ]);

                $msg = 'You now get paid directly.';
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

                UserPayout::upsertActiveForUser((int) $user->id, [
                    'payout_mode' => $validated['payout_mode'],
                ]);

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
                $rules['studio_name'] = ['nullable', 'string', 'max:255'];
                $messages['studio_email.required'] = 'Studio email is required.';
                $messages['studio_email.email'] = 'Please enter a valid email address.';
                // Save & Send (email not sent): require split + relationship with the invite.
                $rules = array_merge($rules, $this->studioSplitRelationshipRules(required: true));
                $messages = array_merge($messages, $this->studioSplitRelationshipMessages());
            }

            $this->mergeStudioSplitRelationshipInput($request);
            $validated = $request->validate($rules, $messages);
            $studio = null;

            $this->assertPaymentTypeCanChange($userDetail, $validated['payment_type']);
            $this->assertStudioInviteEmailAvailable($validated['studio_email'] ?? null, $paymentType);

            $activePayout = $userDetail->activePayout();

            if ($paymentType === 'artist_account') {
                if ($activePayout?->payout_waiting_list_country) {
                    UserStudio::clearStudioPayout((int) $user->id);
                    $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, [
                        'studio_id' => null,
                    ]));
                    UserPayout::upsertActiveForUser((int) $user->id, [
                        'type' => UserPayout::TYPE_ARTIST,
                        'status' => UserPayout::STATUS_PENDING,
                        'stripe_account_id' => null,
                        'studio_id' => null,
                        'user_studio_id' => null,
                        'connected_at' => null,
                    ]);
                } else {
                    $stripeConnect = app(StripeConnectService::class);
                    if (! $stripeConnect->isConfigured()) {
                        throw ValidationException::withMessages([
                            'stripe_connect' => ['Stripe payout setup is not available right now. Please try again later.'],
                        ]);
                    }

                    $accountId = $request->input('stripe_account_id') ?: $activePayout?->stripe_account_id;
                    if (! is_string($accountId) || $accountId === '' || ! $stripeConnect->isOnboardingSubmitted($accountId)) {
                        throw ValidationException::withMessages([
                            'stripe_connect' => ['Please complete Stripe payout setup before saving.'],
                        ]);
                    }

                    UserStudio::clearStudioPayout((int) $user->id);
                    $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, [
                        'studio_id' => null,
                    ]));

                    $currency = $stripeConnect->resolveCurrencyForAccount($accountId);
                    if ($currency !== null) {
                        $userDetail->currency = $currency;
                    }

                    UserPayout::upsertActiveForUser((int) $user->id, [
                        'type' => UserPayout::TYPE_ARTIST,
                        'stripe_account_id' => $accountId,
                        'studio_id' => null,
                        'user_studio_id' => null,
                    ]);
                    $this->syncStripeRequirementsAfterConnect($userDetail->fresh() ?? $userDetail);
                }
            } elseif ($paymentType === 'studio_account') {
                $studioName = trim((string) ($validated['studio_name'] ?? ''));
                if ($studioName === '') {
                    $studioName = $userDetail->resolvedStudioName() !== '' ? $userDetail->resolvedStudioName() : 'Studio';
                }
                $studioEmail = strtolower(trim($validated['studio_email']));
                $studio = Studio::firstWhere('email', $studioEmail);
                if (! $studio) {
                    $studio = Studio::create([
                        'name' => $studioName,
                        'email' => $studioEmail,
                    ]);
                } elseif ($studioName !== '' && $studioName !== 'Studio' && blank($studio->name)) {
                    $studio->name = $studioName;
                    $studio->save();
                }

                // Keep the artist's Stripe account — studio payout uses both accounts.
                $artistStripeAccountId = $activePayout?->stripe_account_id;

                UserStudio::syncStudioPayout((int) $user->id, $studio, $validated);
                $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, [
                    'studio_id' => $studio->id,
                    'studio_name' => $studioName,
                    'studio_revenue_artist_percent' => $validated['studio_revenue_artist_percent'] ?? null,
                    'studio_relationship_type' => $validated['studio_relationship_type'] ?? null,
                ]));
                $this->applyStudioSplitAndRelationship($userDetail, $validated);

                $link = UserStudio::payoutLinkForUser((int) $user->id);
                UserPayout::upsertActiveForUser((int) $user->id, [
                    'type' => UserPayout::TYPE_STUDIO,
                    'status' => UserPayout::STATUS_PENDING,
                    'stripe_account_id' => $artistStripeAccountId,
                    'studio_id' => $studio->id,
                    'user_studio_id' => $link?->id,
                    'studio_revenue_artist_percent' => $validated['studio_revenue_artist_percent'] ?? null,
                    'studio_relationship_type' => $validated['studio_relationship_type'] ?? null,
                ]);
            }

            $userDetail->save();

            if ($paymentType === 'studio_account') {
                $this->sendStudioPayoutInfoRequestEmail($user, $userDetail, $studio);
            }

            $userDetail->refresh();
            $freshPayout = $userDetail->activePayout();

            if ($request->expectsJson() || $request->ajax()) {
                $stripeConnect = app(StripeConnectService::class);
                $liveStripeStatus = null;
                if (
                    $paymentType === 'artist_account'
                    && $freshPayout?->stripe_account_id
                    && $stripeConnect->isConfigured()
                ) {
                    try {
                        $liveStripeStatus = $stripeConnect->getOnboardingStatus($freshPayout->stripe_account_id);
                    } catch (\Throwable) {
                        $liveStripeStatus = null;
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment settings updated successfully!',
                    'artist_stripe_needs_action' => $paymentType === 'artist_account'
                        && $this->artistStripeNeedsAction($userDetail, $liveStripeStatus, $stripeConnect),
                    'payment_status' => $freshPayout?->status,
                    'stripe_requirement' => (bool) ($freshPayout?->stripe_requirement ?? false),
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
        $rules = array_merge(
            ['scheduling_type' => ['required', 'in:auto,managed']],
            $this->bookingRulesValidationRules($request)
        );

        return $request->validate($rules);
    }

    /**
     * Validate booking rules + consultation fields (no scheduling_type).
     *
     * @return array<string, mixed>
     */
    private function validateBookingRulesPayload(Request $request): array
    {
        return $request->validate($this->bookingRulesValidationRules($request));
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function bookingRulesValidationRules(Request $request): array
    {
        $requireConsultation = $request->has('require_consultation') && $request->require_consultation == '1';

        $validationRules = [
            'cancellation_window' => ['required', 'in:12h,24h,48h,72h,1w,2w'],
            'reschedule_times' => ['required', 'in:once,twice,unlimited,never'],
            'session_buffer_period' => ['required', 'integer', 'min:0'],
            'require_consultation' => ['nullable'],
        ];

        if ($requireConsultation) {
            $validationRules['session_type'] = ['required', 'in:online,physical,both'];
            $validationRules['session_duration_minutes'] = ['required', 'integer', 'min:15', 'max:480'];
            $validationRules['consultation_timing'] = ['required', 'in:combined,separate'];

            $consultationTiming = $request->input('consultation_timing');
            if ($consultationTiming === 'separate') {
                $validationRules['require_gap_between_consultation_tattoo'] = ['nullable'];
                $requireGap = $request->has('require_gap_between_consultation_tattoo') && $request->require_gap_between_consultation_tattoo == '1';
                if ($requireGap) {
                    $validationRules['consultation_tattoo_gap_value'] = ['required', 'integer', 'min:1'];
                } else {
                    $validationRules['consultation_tattoo_gap_value'] = ['nullable', 'integer', 'min:1'];
                }
            } else {
                $validationRules['require_gap_between_consultation_tattoo'] = ['nullable'];
                $validationRules['consultation_tattoo_gap_value'] = ['nullable', 'integer', 'min:1'];
            }
        } else {
            $validationRules['session_type'] = ['nullable', 'in:online,physical,both'];
            $validationRules['session_duration_minutes'] = ['nullable', 'integer', 'min:15', 'max:480'];
            $validationRules['consultation_timing'] = ['nullable', 'in:combined,separate'];
            $validationRules['require_gap_between_consultation_tattoo'] = ['nullable'];
            $validationRules['consultation_tattoo_gap_value'] = ['nullable', 'integer', 'min:1'];
        }

        return $validationRules;
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

            $lockedCurrency = $this->resolveLockedPaymentCurrency($user, $userDetail);
            $currency = $lockedCurrency['code'] ?: $validated['currency'];

            $minimumDepositAmount = (float) $validated['minimum_deposit_amount'];

            $updateData = [
                'currency' => $currency,
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

            if ((int) $request->input('save_studio_split', 0) === 1) {
                return $this->saveStudioSplitRelationship($request, $userDetail);
            }

            if ((int) $request->input('disconnect_stripe', 0) === 1) {
                if (! $this->hasActiveArtistStripe($userDetail)) {
                    return response()->json(['success' => false, 'message' => 'No connected Stripe payout to disconnect.'], 422);
                }

                UserPayout::upsertActiveForUser((int) $user->id, [
                    'stripe_account_id' => null,
                    'status' => UserPayout::STATUS_DISCONNECTED,
                    'stripe_requirement' => false,
                    'stripe_requirement_email_sent_at' => null,
                    'connected_at' => null,
                    'disconnected_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Stripe payout disconnected. You can now switch payout options or connect again.',
                ]);
            }

            if ((int) $request->input('disconnect_studio', 0) === 1) {
                if (! $this->hasStudioPayoutCommitted($userDetail)) {
                    return response()->json(['success' => false, 'message' => 'No linked studio payout to disconnect.'], 422);
                }

                $this->disconnectStudioPayout($userDetail);

                return response()->json([
                    'success' => true,
                    'message' => 'Studio payout disconnected. You can invite a studio again or switch payout options.',
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
            $studioAlreadyInvited = $paymentType === 'studio_account'
                && $this->hasStudioPayoutCommitted($userDetail);

            if ($paymentType === 'studio_account') {
                // After invite is sent, email lives in a disabled UI field — allow omit and reuse the linked studio.
                $rules['studio_email'] = [$studioAlreadyInvited ? 'nullable' : 'required', 'email', 'max:255'];
                $messages['studio_email.required'] = 'Studio email is required.';
                $messages['studio_email.email'] = 'Please enter a valid email address.';
                $rules = array_merge($rules, $this->studioSplitRelationshipRules(required: ! $studioAlreadyInvited));
                $messages = array_merge($messages, $this->studioSplitRelationshipMessages());
            }

            $this->mergeStudioSplitRelationshipInput($request);
            $validated = $request->validate($rules, $messages);
            $studio = null;

            $stripeConnect = app(StripeConnectService::class);
            $activePayout = $userDetail->activePayout();
            $stripeStatus = null;
            if ($activePayout?->stripe_account_id && $stripeConnect->isConfigured()) {
                try {
                    $stripeStatus = $stripeConnect->getOnboardingStatus($activePayout->stripe_account_id);
                } catch (\Throwable) {
                    $stripeStatus = null;
                }
            }

            $this->assertPaymentTypeCanChange($userDetail, $validated['payment_type'], $stripeStatus);
            $this->assertStudioInviteEmailAvailable($validated['studio_email'] ?? null, $paymentType);

            $userDetail->completed_steps = array_unique(array_merge($userDetail->completed_steps ?? [], [6]));

            if ($paymentType === 'artist_account') {
                if ($activePayout?->payout_waiting_list_country) {
                    UserStudio::clearStudioPayout((int) $user->id);
                    $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, [
                        'studio_id' => null,
                    ]));
                    UserPayout::upsertActiveForUser((int) $user->id, [
                        'status' => UserPayout::STATUS_PENDING,
                        'stripe_account_id' => null,
                        'connected_at' => null,
                    ]);
                } else {
                    if (! $stripeConnect->isConfigured()) {
                        throw ValidationException::withMessages([
                            'stripe_connect' => ['Stripe payout setup is not available right now. Please try again later or skip for now.'],
                        ]);
                    }

                    $accountId = $userDetail->activePayout()?->stripe_account_id;
                    if (! $accountId) {
                        try {
                            $accountId = $stripeConnect->ensureConnectedAccount($user, $userDetail);
                        } catch (\Throwable) {
                            $accountId = null;
                        }
                    }

                    if (! $accountId || ! $stripeConnect->isOnboardingSubmitted($accountId)) {
                        throw ValidationException::withMessages([
                            'stripe_connect' => ['Please complete Stripe payout setup before finishing onboarding.'],
                        ]);
                    }

                    UserStudio::clearStudioPayout((int) $user->id);
                    $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, [
                        'studio_id' => null,
                    ]));
                    UserPayout::upsertActiveForUser((int) $user->id, [
                        'stripe_account_id' => $accountId,
                    ]);
                    $this->syncStripeRequirementsAfterConnect($userDetail->fresh() ?? $userDetail);
                }
            } elseif ($paymentType === 'studio_account') {
                $studioName = $userDetail->resolvedStudioName() !== '' ? $userDetail->resolvedStudioName() : 'Studio';
                $studioEmail = strtolower(trim((string) ($validated['studio_email'] ?? '')));
                $alreadyInvited = $studioAlreadyInvited;
                $studio = null;

                if ($studioEmail === '' && $alreadyInvited) {
                    $link = UserStudio::payoutLinkForUser((int) $user->id);
                    $studio = $link?->studio_id ? Studio::find($link->studio_id) : null;
                    if (! $studio && $userDetail->resolvedStudioId()) {
                        $studio = Studio::find($userDetail->resolvedStudioId());
                    }
                    $studioEmail = strtolower(trim((string) ($studio?->email ?? '')));
                }

                if ($studioEmail === '') {
                    throw ValidationException::withMessages([
                        'studio_email' => ['Studio email is required.'],
                    ]);
                }

                if (! $studio) {
                $studio = Studio::firstWhere('email', $studioEmail);
                }
                if (! $studio) {
                    $studio = Studio::create([
                        'name' => $studioName,
                        'email' => $studioEmail,
                    ]);
                }

                if (! array_key_exists('studio_revenue_artist_percent', $validated)
                    || $validated['studio_revenue_artist_percent'] === null
                    || $validated['studio_revenue_artist_percent'] === '') {
                    $validated['studio_revenue_artist_percent'] = $userDetail->resolvedStudioRevenueArtistPercent(50);
                }

                UserStudio::syncStudioPayout((int) $user->id, $studio, $validated);
                $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, [
                    'studio_id' => $studio->id,
                    'studio_revenue_artist_percent' => $validated['studio_revenue_artist_percent'] ?? null,
                    'studio_relationship_type' => $validated['studio_relationship_type'] ?? null,
                ]));
                $this->applyStudioSplitAndRelationship($userDetail, $validated);

                UserPayout::upsertActiveForUser((int) $user->id, [
                    'status' => UserPayout::STATUS_PENDING,
                ]);

                if (! $alreadyInvited) {
                $this->sendStudioPayoutInfoRequestEmail($user, $userDetail, $studio);
            }

                $freshPayout = $userDetail->fresh()?->activePayout();
                $artistStripeReady = $this->hasActiveArtistStripe($userDetail->fresh(), $stripeStatus)
                    || (
                        $stripeConnect->isConfigured()
                        && is_string($freshPayout?->stripe_account_id)
                        && $freshPayout->stripe_account_id !== ''
                        && $stripeConnect->isOnboardingSubmitted($freshPayout->stripe_account_id)
                    );

                $userDetail->save();

                if (! $artistStripeReady) {
                    return response()->json([
                        'success' => true,
                        'message' => $alreadyInvited
                            ? 'Studio invite is saved. Connect your bank account or choose Set up later to continue.'
                            : 'Invite sent to your studio. Connect your bank account or choose Set up later to continue.',
                        'studio_invite_sent' => ! $alreadyInvited,
                        'studio_payout_committed' => true,
                        'payout_option_locked' => true,
                        // Stay on the payment step — frontend updates UI without a full reload.
                        'stay_on_page' => true,
                        'studio_email' => $studioEmail,
                        'studio_revenue_artist_percent' => (int) ($validated['studio_revenue_artist_percent'] ?? 50),
                    ]);
                }
            }

            $userDetail->save();

            // Mark onboarding as complete (artist path, or studio invite + artist Stripe ready)
            $completingOnboarding = $user->on_boarding !== 'yes';
            $user->update(['on_boarding' => 'yes']);

            if ($completingOnboarding) {
                $this->sendArtistWelcomeEmail($user);
                app(\App\Services\MailcoachSubscriberService::class)
                    ->queueSubscribeUser($user, \App\Services\MailcoachSubscriberService::TAG_ARTIST);

                $this->seedDefaultQuestionSortingForArtist($user);
                $this->seedDefaultConsentQuestionsForArtist($user);
                app(\App\Services\ArtistReferralRewardService::class)
                    ->markPendingWhenReferredArtistBecomesActive($user);
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
            $this->seedDefaultConsentQuestionsForArtist($user);
            app(\App\Services\ArtistReferralRewardService::class)
                ->markPendingWhenReferredArtistBecomesActive($user);

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

        if (! $this->studioPayoutInviteIsActive($userDetail)) {
            return view('studio.payout-form-result', [
                'success' => false,
                'message' => 'This payout request is no longer active.',
            ]);
        }

        $studio = $this->resolveStudioForPayoutInvite($userDetail);
        if (! $studio) {
            return view('studio.payout-form-result', [
                'success' => false,
                'message' => 'Studio record was not found.',
            ]);
        }

        $artist = $userDetail->user;
        $artistName = $userDetail->publicDisplayName();
        if ($artistName === '' || $artistName === 'Artist') {
            $fallback = trim(($artist->first_name ?? '').' '.($artist->last_name ?? ''));
            $artistName = $fallback !== '' ? $fallback : ($userDetail->user_name ?? $artist->email ?? 'Artist');
        }

        $stripeConnect = app(StripeConnectService::class);
        $studioAlreadyConnected = $studio->hasStripeConnect();
        // Invite UI must follow this artist↔studio link — not the artist's global UserPayout status.
        $paymentStatus = $this->studioPayoutInviteDecisionStatus($userDetail, $studio);
        $pendingJoin = $this->pendingJoinForPayoutInvite($userDetail);

        // After Stripe (or when already done), send logged-in studio users to the dashboard.
        if (
            $request->boolean('completed')
            && $paymentStatus === 'approved'
            && Auth::check()
            && (int) Auth::id() === (int) ($studio->user_id ?? 0)
        ) {
            return redirect()->route('studio.dashboard');
        }

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
        $acceptUrl = URL::temporarySignedRoute(
            'studio.payout-info.accept',
            now()->addDays(30),
            ['userDetail' => $userDetail->id]
        );
        $declineUrl = URL::temporarySignedRoute(
            'studio.payout-artist-link.decline',
            now()->addDays(30),
            ['userDetail' => $userDetail->id]
        );

        $styles = is_array($userDetail->tattoo_styles) ? $userDetail->tattoo_styles : [];
        $tattooingSince = $styles['tattooing_since'] ?? null;
        $primaryStyle = $styles['primary_style'] ?? null;
        $otherStyles = [];
        if (isset($styles['other_styles']) && is_array($styles['other_styles'])) {
            $otherStyles = array_values(array_filter($styles['other_styles']));
        }
        $styleLabels = array_values(array_filter(array_merge(
            $primaryStyle ? [(string) $primaryStyle] : [],
            array_map(fn ($s) => (string) $s, $otherStyles)
        )));
        $relationshipLabels = [
            'co_owner' => 'Co-owner',
            'resident' => 'Resident',
            'collective_member' => 'Collective Member',
            'apprentice' => 'Apprentice',
            'other' => 'Other (Contract Artist, Freelancer)',
        ];
        $relationshipType = $pendingJoin?->relationship ?? $userDetail->studio_relationship_type ?? null;
        $artistPercent = $pendingJoin?->revenue_split !== null
            ? (int) $pendingJoin->revenue_split
            : (int) ($userDetail->studio_revenue_artist_percent ?? 50);
        $artistPercent = max(0, min(100, $artistPercent));
        $locationParts = array_values(array_filter([
            trim((string) ($userDetail->city ?? '')),
            trim((string) ($userDetail->country ?? '')),
        ], fn (string $part) => $part !== ''));

        $avatarPath = trim((string) ($userDetail->avatar ?? ''));
        $artistHandle = trim((string) ($userDetail->user_name ?? ''));

        return view('studio.payout-form', [
            'userDetail' => $userDetail,
            'studio' => $studio,
            'artistName' => $artistName,
            'artistHandle' => $artistHandle !== '' ? $artistHandle : null,
            'artistAvatarUrl' => $avatarPath !== '' ? asset($avatarPath) : null,
            'artistInitials' => $userDetail->publicDisplayInitials(),
            'artistLocation' => $locationParts !== [] ? implode(', ', $locationParts) : null,
            'artistTattooingSince' => $tattooingSince ? (string) $tattooingSince : null,
            'artistPrimaryStyle' => $primaryStyle ? (string) $primaryStyle : null,
            'artistStyleLabels' => $styleLabels,
            'studioRevenueArtistPercent' => $artistPercent,
            'studioRevenueStudioPercent' => 100 - $artistPercent,
            'studioRelationshipLabel' => $relationshipLabels[$relationshipType] ?? null,
            'studioNameValue' => old('studio_name', $studio->name ?? $userDetail->studio_name ?? ''),
            'studioEmail' => old('email', $studio->email ?? ''),
            'studioAlreadyConnected' => $studioAlreadyConnected,
            'studioProfile' => $studioProfile,
            'paymentStatus' => $paymentStatus,
            'approveUrl' => $approveUrl,
            'acceptUrl' => $acceptUrl,
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

        if (! $this->studioPayoutInviteIsActive($userDetail)) {
            return response()->json(['success' => false, 'message' => 'This payout request is no longer active.'], 422);
        }

        $studio = $this->resolveStudioForPayoutInvite($userDetail);
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

    public function studioStripeStatus(
        Request $request,
        UserDetail $userDetail,
        StripeConnectService $stripeConnect,
        StripeRequirementSyncService $stripeRequirementSync,
    ) {
        if (! $request->hasValidSignature()) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired link.'], 403);
        }

        $studio = $this->resolveStudioForPayoutInvite($userDetail);
        $accountId = $request->query('account_id');
        if (! is_string($accountId) || $accountId === '') {
            $accountId = $studio?->resolveStripeAccountId()
                ?? $userDetail->activePayout()?->stripe_account_id;
        }

        if (! $accountId) {
            return response()->json(['success' => true, 'complete' => false]);
        }

        try {
            $status = $stripeConnect->getOnboardingStatus($accountId);

            if ($studio && ! $stripeConnect->accountNeedsUserSubmission($status)) {
                try {
                    $stripeRequirementSync->syncStudio($studio);
                } catch (\Throwable) {
                    // Status response still succeeds even if flag sync fails.
                }
            }

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

        if (! $this->studioPayoutInviteIsActive($userDetail)) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Request inactive',
                'message' => 'This payout request is no longer active.',
            ]);
        }

        $studio = $this->resolveStudioForPayoutInvite($userDetail);
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

        $studioUser = auth()->user();
        $canGoToStudioDashboard = $studioUser
            && ($studioUser->role ?? '') === 'studio'
            && ! $studioUser->mustCompleteStudioOnboarding();

        if (! $needsAction) {
            if ($canGoToStudioDashboard) {
                return redirect()
                    ->route('studio.dashboard')
                    ->with('success', 'Stripe does not need any more information from your studio right now.');
            }

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

        $doneUrl = $canGoToStudioDashboard
            ? route('studio.dashboard')
            : URL::temporarySignedRoute(
                'studio.payout-info.show',
                now()->addDays(14),
                ['userDetail' => $userDetail->id, 'completed' => 1]
            );

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
            'doneUrl' => $doneUrl,
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

        if (! $this->studioPayoutInviteIsActive($userDetail)) {
            return response()->json(['success' => false, 'message' => 'This payout request is no longer active.'], 422);
        }

        $studio = $this->resolveStudioForPayoutInvite($userDetail);
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

        if (! $this->studioPayoutInviteIsActive($userDetail)) {
            return response()->json(['success' => false, 'message' => 'This payout request is no longer active.'], 422);
        }

        $studio = $this->resolveStudioForPayoutInvite($userDetail);
        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio record was not found.'], 404);
        }

        $validated = $request->validate([
            'account_id' => ['required', 'string', 'regex:/^acct_[a-zA-Z0-9]+$/'],
        ]);

        try {
            $stripeConnect->finalizeStudioOnboarding($studio, $userDetail, $validated['account_id']);
            $studio->refresh();
            // Split invite goes live only after Stripe is connected.
            $this->activatePendingJoinForStudio($userDetail, $studio);

            return response()->json([
                'success' => true,
                'message' => 'Stripe payout setup completed successfully.',
                'redirect' => route('studio.dashboard'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Signed invite accept: create/link studio user account, then approve or continue to Stripe.
     */
    public function acceptStudioInvitation(Request $request, UserDetail $userDetail, StripeConnectService $stripeConnect)
    {
        if (! $request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'This link is invalid or has expired. Ask the artist to resend the invite.',
            ], 403);
        }

        if (! $this->studioPayoutInviteIsActive($userDetail)) {
            return response()->json([
                'success' => false,
                'message' => 'This payout request is no longer active.',
            ], 422);
        }

        $studio = $this->resolveStudioForPayoutInvite($userDetail);
        if (! $studio) {
            return response()->json([
                'success' => false,
                'message' => 'Studio record was not found.',
            ], 404);
        }

        $inviteStatus = $this->studioPayoutInviteDecisionStatus($userDetail, $studio);

        if ($inviteStatus === 'approved') {
            return response()->json([
                'success' => true,
                'csrf_token' => csrf_token(),
                'redirect' => route('studio.dashboard'),
            ]);
        }

        if ($inviteStatus === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'This payout request was already declined.',
            ], 422);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'terms' => ['accepted'],
        ], [
            'password.required' => 'Create a password.',
            'password.min' => 'Use at least 8 characters',
            'terms.accepted' => 'Tick the box to continue',
        ]);

        try {
            $studioUser = $this->registerOrUpdateStudioUserFromInvite($studio, (string) $validated['password']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Failed to register studio user from invite', [
                'studio_id' => $studio->id,
                'user_detail_id' => $userDetail->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not create your studio account. Please try again.',
            ], 500);
        }

        Auth::login($studioUser);
        $this->activatePendingJoinForStudio($userDetail, $studio);

        if ($studio->hasStripeConnect()) {
            $this->setPayoutStatus($userDetail, UserPayout::STATUS_APPROVED, [
                'stripe_requirement' => (bool) ($studio->stripe_requirement ?? false),
            ]);

            return response()->json([
                'success' => true,
                'csrf_token' => csrf_token(),
                'redirect' => route('studio.dashboard'),
            ]);
        }

        if (! $stripeConnect->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe is not configured. Please contact support.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'next' => 'stripe',
            'csrf_token' => csrf_token(),
            'message' => 'Account created. Split confirmed',
        ]);
    }

    /**
     * Create or update a users.role=studio account for the invited studio email.
     */
    protected function registerOrUpdateStudioUserFromInvite(Studio $studio, string $password): User
    {
        $email = strtolower(trim((string) ($studio->email ?? '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'This invite does not have a valid studio email.',
            ]);
        }

        $existing = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing) {
            if ($existing->role !== 'studio') {
                throw ValidationException::withMessages([
                    'email' => 'This email is already registered with a different Bookpay account. Sign in or ask the artist to use another studio email.',
                ]);
            }

            $existing->password = $password;
            $existing->must_set_password = false;
            if (empty($existing->email_verified_at)) {
                $existing->email_verified_at = now();
            }
            $existing->on_app = 1;
            if (empty($existing->studio_registration_source)) {
                $existing->studio_registration_source = 'invite';
            }
            $existing->save();

            if ((int) ($studio->user_id ?? 0) !== (int) $existing->id) {
                $studio->user_id = $existing->id;
                $studio->save();
            }

            app(\App\Services\MailcoachSubscriberService::class)
                ->queueSubscribeUser($existing, \App\Services\MailcoachSubscriberService::TAG_STUDIO);
            app(\App\Services\MailcoachSubscriberService::class)->queueSubscribeStudio($studio);

            return $existing->fresh();
        }

        $nameParts = preg_split('/\s+/', trim((string) ($studio->name ?? '')), 2) ?: [];
        $firstName = trim((string) ($nameParts[0] ?? ''));
        $lastName = trim((string) ($nameParts[1] ?? ''));

        $user = User::create([
            'first_name' => $firstName !== '' ? $firstName : 'Studio',
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
            'must_set_password' => false,
            'role' => 'studio',
            'studio_registration_source' => 'invite',
            'on_boarding' => 'no',
            'on_app' => 1,
            'app_id' => null,
            'email_verified_at' => now(),
        ]);

        // Studio profile lives on `studios` — no user_details row for invite studios.
        $studio->user_id = $user->id;
        $studio->save();

        app(\App\Services\MailcoachSubscriberService::class)
            ->queueSubscribeUser($user, \App\Services\MailcoachSubscriberService::TAG_STUDIO);
        app(\App\Services\MailcoachSubscriberService::class)->queueSubscribeStudio($studio);

        return $user;
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

        if (! $this->studioPayoutInviteIsActive($userDetail)) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Request inactive',
                'message' => 'This payout request is no longer active.',
            ]);
        }

        $studio = $this->resolveStudioForPayoutInvite($userDetail);
        if (! $studio) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Studio not found',
                'message' => 'Studio record was not found.',
            ]);
        }

        // Not connected yet: send them to the review page where Approve starts Stripe setup.
        if (! $studio->hasStripeConnect()) {
            return redirect()->to(URL::temporarySignedRoute(
                'studio.payout-info.show',
                now()->addDays(30),
                ['userDetail' => $userDetail->id]
            ));
        }

        $inviteStatus = $this->studioPayoutInviteDecisionStatus($userDetail, $studio);

        if ($inviteStatus === 'approved') {
            return view('studio.payout-form-result', [
                'success' => true,
                'title' => 'Already approved',
                'message' => 'This artist was already approved to receive payouts through your studio.',
            ]);
        }

        if ($inviteStatus === 'rejected') {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Request declined',
                'message' => 'This payout request was already declined.',
            ]);
        }

        // Keep the artist's own stripe_account_id — studio payout uses both accounts.
        $this->setPayoutStatus($userDetail, UserPayout::STATUS_APPROVED, [
            'stripe_requirement' => (bool) ($studio->stripe_requirement ?? false),
        ]);
        $this->activatePendingJoinForStudio($userDetail, $studio);

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
        $wantsJson = $request->expectsJson() || $request->ajax() || $request->isMethod('post');

        if (! $request->hasValidSignature()) {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'This link is invalid or has expired. Ask the artist to resend the payout email.',
                ], 403);
            }

            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Invalid link',
                'message' => 'This link is invalid or has expired. Ask the artist to resend the payout email.',
            ]);
        }

        if (! $this->studioPayoutInviteIsActive($userDetail)) {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'This payout request is no longer active.',
                ], 422);
            }

            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Request inactive',
                'message' => 'This payout request is no longer active.',
            ]);
        }

        $studio = $this->resolveStudioForPayoutInvite($userDetail);
        $inviteStatus = $this->studioPayoutInviteDecisionStatus($userDetail, $studio);

        if ($inviteStatus === 'approved') {
            if ($wantsJson) {
                return response()->json([
                    'success' => true,
                    'redirect' => URL::temporarySignedRoute(
                        'studio.payout-info.show',
                        now()->addDays(14),
                        ['userDetail' => $userDetail->id]
                    ),
                    'message' => 'This artist was already approved to use your studio’s payout details. Nothing was changed.',
                ]);
            }

            return view('studio.payout-form-result', [
                'success' => true,
                'title' => 'Already linked',
                'message' => 'This artist was already approved to use your studio’s payout details. Nothing was changed.',
            ]);
        }

        if ($inviteStatus === 'rejected') {
            if ($wantsJson) {
                return response()->json([
                    'success' => true,
                    'message' => 'You have already declined this request.',
                ]);
            }

            return view('studio.payout-form-result', [
                'success' => true,
                'title' => 'Already declined',
                'message' => 'You have already declined this request.',
            ]);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);
        $declineMessage = trim((string) ($validated['message'] ?? ''));

        $artistUser = $userDetail->user;
        $studioName = $studio?->name ?? 'Studio';

        // Decline this studio invite only — keep studio_id so reopen shows “already declined”.
        if ($studio) {
            UserStudio::query()
                ->where('user_id', (int) $userDetail->user_id)
                ->where('studio_id', (int) $studio->id)
                ->whereIn('status', [UserStudio::STATUS_JOIN_PENDING, UserStudio::STATUS_ACTIVE])
                ->update([
                    'status' => UserStudio::STATUS_INACTIVE,
                    'payout' => false,
                    'connection_type' => UserStudio::CONNECTION_NOT_ON_BOOKPAY,
                    'updated_at' => now(),
                ]);
        } else {
            UserStudio::clearStudioPayout((int) $userDetail->user_id);
        }

        $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, [
            'studio_id' => null,
        ]));
        $userDetail->save();
        // Do not flip global UserPayout to rejected — artist may already have their own approved payouts.
        $activePayout = $userDetail->activePayout();
        if ($activePayout && ($activePayout->status ?? '') !== UserPayout::STATUS_APPROVED) {
            $this->setPayoutStatus($userDetail, UserPayout::STATUS_REJECTED, [
                'studio_id' => null,
                'user_studio_id' => null,
            ]);
        }

        if ($artistUser) {
            $this->sendStudioPayoutDeclinedArtistEmail(
                $artistUser,
                $studioName,
                $declineMessage !== '' ? $declineMessage : null,
            );
        }

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'title' => 'Invitation declined',
                'message' => 'We let the artist know. They keep being paid directly. If you change your mind, ask them to send a new invite.',
            ]);
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

        $payout = $userDetail?->activePayout();
        if (! $userDetail || $this->resolvedPaymentType($userDetail) !== 'studio_account') {
            return redirect()->intended(authenticated_home_url());
        }

        $status = (string) ($payout?->status ?? 'pending');
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

    /**
     * Copy admin consent-form question templates into the artist's account (once).
     */
    private function seedDefaultConsentQuestionsForArtist(User $user): void
    {
        try {
            app(\App\Services\ConsentFormQuestionService::class)->seedForArtist((int) $user->id);
        } catch (\Throwable $e) {
            Log::error('Failed to seed consent questions after onboarding', [
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
        $payout = $userDetail->activePayout();
        $userStudioId = (int) $request->input('user_studio_id', 0);
        $targetedLink = null;
        if ($userStudioId > 0 && UserStudio::tableReady()) {
            $targetedLink = UserStudio::query()
                ->with('studio')
                ->where('user_id', $user->id)
                ->where('id', $userStudioId)
                ->where('status', UserStudio::STATUS_ACTIVE)
                ->where('payout', true)
                ->whereNotNull('revenue_split')
                ->first();
        }

        if (! $targetedLink && ($this->resolvedPaymentType($userDetail) !== 'studio_account' || ! $userDetail->resolvedStudioId())) {
            $msg = 'No studio payout request to resend yet.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $this->mergeStudioSplitRelationshipInput($request);
        // Onboarding reminder sends split fields; settings reminder is email-only.
        if ($request->filled('studio_revenue_artist_percent') || $request->filled('studio_relationship_type')) {
            $splitValidated = $request->validate(
                $this->studioSplitRelationshipRules(required: true),
                $this->studioSplitRelationshipMessages()
            );
            $this->applyStudioSplitAndRelationship($userDetail, $splitValidated);
            $userDetail->save();
            // Split/relationship live on user_studios / user_details — not user_payouts.
        }

        $studioId = $targetedLink?->studio_id ?: $userDetail->resolvedStudioId();

        // Same rule as the UI: accepted = user_studios on_bookpay + studio Stripe — not artist payout status.
        $linkForCheck = $targetedLink ?: (UserStudio::tableReady() ? UserStudio::payoutLinkForUser((int) $user->id) : null);
        $studioForCheck = null;
        if ($linkForCheck) {
            $studioForCheck = $linkForCheck->relationLoaded('studio')
                ? $linkForCheck->studio
                : Studio::query()->find($linkForCheck->studio_id);
        }
        $studioConnected = $linkForCheck
            && ($linkForCheck->connection_type ?? '') === UserStudio::CONNECTION_ON_BOOKPAY
            && filled($studioForCheck?->resolveStripeAccountId());

        if ($studioConnected) {
            $studio = Studio::find($studioId);
            $needsRequirements = (bool) ($studio?->stripe_requirement || $payout?->stripe_requirement);

            if (! $needsRequirements && ! (int) $request->input('requirements_reminder', 0)) {
            $msg = 'Your studio payout is already connected.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
            }
        }

        $studio = $targetedLink?->studio ?: Studio::find($studioId);
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

            if (User::emailBlockedForStudioInvite($requestedEmail)) {
                $msg = 'This email is already registered with a Bookpay account. Use a different studio email.';

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $msg,
                        'errors' => ['studio_email' => [$msg]],
                    ], 422);
                }

                return redirect()->back()->withErrors(['studio_email' => $msg])->withInput();
            }

            if ($requestedEmail !== strtolower(trim((string) $studio->email))) {
                $studio = $this->resolveOrCreateStudio(
                    $requestedEmail,
                    $userDetail->resolvedStudioName() !== '' ? $userDetail->resolvedStudioName() : 'Studio'
                );
                UserStudio::syncStudioPayout((int) $user->id, $studio, [
                    'studio_revenue_artist_percent' => $userDetail->payoutUserStudio()?->revenue_split
                        ?? $userDetail->studio_revenue_artist_percent,
                    'studio_relationship_type' => $userDetail->payoutUserStudio()?->relationship
                        ?? $userDetail->studio_relationship_type,
                ]);
                $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, [
                    'studio_id' => $studio->id,
                ]));
                $userDetail->save();
                $this->setPayoutStatus($userDetail, UserPayout::STATUS_PENDING);
                $emailChanged = true;
            }
        }

        $freshPayout = $userDetail->fresh()?->activePayout() ?? $payout;
        $requirementsReminder = (int) $request->input('requirements_reminder', 0) === 1
            || (
                ($freshPayout?->status ?? '') === UserPayout::STATUS_APPROVED
                && (bool) ($studio->stripe_requirement || $freshPayout?->stripe_requirement)
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

    /**
     * Save only studio revenue split + relationship type.
     */
    protected function saveStudioSplitRelationship(Request $request, UserDetail $userDetail)
    {
        $this->mergeStudioSplitRelationshipInput($request);

        try {
            $validated = $request->validate(
                $this->studioSplitRelationshipRules(required: true),
                $this->studioSplitRelationshipMessages()
            );
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please fix the validation errors',
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        }

        $this->applyStudioSplitAndRelationship($userDetail, $validated);
        $userDetail->save();

        $link = UserStudio::payoutLinkForUser((int) $userDetail->user_id) ?? UserStudio::activeForUser((int) $userDetail->user_id);

        $msg = 'Revenue split and relationship type saved.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'studio_revenue_artist_percent' => (int) ($link?->revenue_split
                    ?? $userDetail->studio_revenue_artist_percent
                    ?? 0),
                'studio_relationship_type' => $link?->relationship
                    ?? $userDetail->studio_relationship_type,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Map not-connected section aliases onto the canonical studio split fields.
     */
    private function mergeStudioSplitRelationshipInput(Request $request): void
    {
        $merge = [];

        if (! $request->filled('studio_revenue_artist_percent') && $request->filled('studio_revenue_artist_percent_nc')) {
            $merge['studio_revenue_artist_percent'] = $request->input('studio_revenue_artist_percent_nc');
        }

        if (! $request->filled('studio_relationship_type') && $request->filled('studio_relationship_type_nc')) {
            $merge['studio_relationship_type'] = $request->input('studio_relationship_type_nc');
        }

        if ($merge !== []) {
            $request->merge($merge);
        }
    }

    /**
     * @return array<string, list<string|\Illuminate\Validation\Rules\In>>
     */
    private function studioSplitRelationshipRules(bool $required): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            'studio_revenue_artist_percent' => [$presence, 'integer', 'min:0', 'max:100'],
            // Collected on studio step; keep nullable here so payouts can omit it.
            'studio_relationship_type' => ['nullable', Rule::in(UserDetail::STUDIO_RELATIONSHIP_TYPES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function studioSplitRelationshipMessages(): array
    {
        return [
            'studio_revenue_artist_percent.required' => 'Please enter your revenue split percentage.',
            'studio_revenue_artist_percent.integer' => 'Revenue split must be a whole number.',
            'studio_revenue_artist_percent.min' => 'Revenue split must be between 0 and 100.',
            'studio_revenue_artist_percent.max' => 'Revenue split must be between 0 and 100.',
            'studio_relationship_type.required' => 'Please select a relationship type.',
            'studio_relationship_type.in' => 'Invalid relationship type selected.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyStudioSplitAndRelationship(UserDetail $userDetail, array $validated): void
    {
        UserStudio::applySplitAndRelationship((int) $userDetail->user_id, $validated);

        $legacy = [];
        if (array_key_exists('studio_revenue_artist_percent', $validated) && $validated['studio_revenue_artist_percent'] !== null && $validated['studio_revenue_artist_percent'] !== '') {
            $legacy['studio_revenue_artist_percent'] = (int) $validated['studio_revenue_artist_percent'];
        }

        if (! empty($validated['studio_relationship_type'])) {
            $legacy['studio_relationship_type'] = $validated['studio_relationship_type'];
        }

        $userDetail->fill($this->onlyExistingUserDetailColumns($userDetail, $legacy));
    }

    /**
     * S06 — studio notification when an artist leaves the revenue split for direct payment.
     */
    private function notifyStudioArtistSwitchedToDirect(UserStudio $link, int $artistPercent): void
    {
        $link->loadMissing(['studio', 'user.userDetail']);
        $studio = $link->studio;
        if (! $studio instanceof Studio) {
            return;
        }

        $email = app(ArtistStudioJoinRequestNotifier::class)->resolveStudioEmail($studio);
        if ($email === null) {
            Log::warning('Artist switched-to-direct email skipped — studio has no email', [
                'studio_id' => $studio->id,
                'user_studio_id' => $link->id,
                'artist_id' => $link->user_id,
            ]);

            return;
        }

        try {
            Mail::to($email)->send(new ArtistSwitchedToDirectStudioMail(
                userStudio: $link,
                artistPercent: $artistPercent,
                effectiveFrom: now(),
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send artist switched-to-direct studio email', [
                'studio_id' => $studio->id,
                'user_studio_id' => $link->id,
                'artist_id' => $link->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendStudioPayoutDeclinedArtistEmail(
        User $artistUser,
        string $studioName,
        ?string $message = null,
    ): void {
        $artistName = trim(($artistUser->first_name ?? '').' '.($artistUser->last_name ?? ''));
        if ($artistName === '') {
            $artistName = $artistUser->user_name ?? $artistUser->email ?? 'Artist';
        }

        try {
            Mail::to($artistUser->email)->send(new StudioPayoutDeclinedArtistMail(
                $artistName,
                $studioName,
                route('settings.payment'),
                $message,
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

        $userDetail->loadMissing('user');
        $artistName = trim((string) $userDetail->publicDisplayName());
        if ($artistName === '' || $artistName === 'Artist') {
        $artistName = trim(($artistUser->first_name ?? '').' '.($artistUser->last_name ?? ''));
        }
        if ($artistName === '') {
            $artistName = $artistUser->user_name ?? $artistUser->email ?? 'Artist';
        }

        $showApproveDecline = ! $requirementsReminder;

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
                'studio.payout-info.show',
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
                $this->studioRelationshipEmailPhrase(
                    $userDetail->activePayout()?->studio_relationship_type
                        ?? $userDetail->studio_relationship_type
                ),
                $userDetail,
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
     * Studio for signed payout / join-invite links.
     * Prefers pending user_studios join (Account Studio) because user_details.studio_id may be dropped.
     */
    private function resolveStudioForPayoutInvite(UserDetail $userDetail): ?Studio
    {
        $userId = (int) $userDetail->user_id;

        $pending = UserStudio::pendingJoinForUser($userId);
        if ($pending?->studio_id) {
            $studio = Studio::query()->find($pending->studio_id);
            if ($studio) {
                return $studio;
            }
        }

        // Include inactive (declined) / not-yet-on-bookpay payout links for this invite.
        if (UserStudio::tableReady()) {
            $linkStudioId = UserStudio::query()
                ->where('user_id', $userId)
                ->whereNotNull('studio_id')
                ->orderByDesc('id')
                ->value('studio_id');
            if ($linkStudioId) {
                $studio = Studio::query()->find((int) $linkStudioId);
                if ($studio) {
                    return $studio;
                }
            }
        }

        $studioId = $userDetail->resolvedStudioId();
        if ($studioId) {
            return Studio::query()->find($studioId);
        }

        return null;
    }

    private function studioPayoutInviteIsActive(UserDetail $userDetail): bool
    {
        $studio = $this->resolveStudioForPayoutInvite($userDetail);
        if ($studio === null) {
            return false;
        }

        $userId = (int) $userDetail->user_id;

        // Artist dashboard “add studio” invite — active while join is pending for this studio.
        $pending = UserStudio::pendingJoinForUser($userId);
        if ($pending && (int) $pending->studio_id === (int) $studio->id) {
            return true;
        }

        $decision = $this->studioPayoutInviteDecisionStatus($userDetail, $studio);
        if (in_array($decision, ['approved', 'rejected', 'pending'], true)
            && UserStudio::query()
                ->where('user_id', $userId)
                ->where('studio_id', (int) $studio->id)
                ->exists()) {
            return true;
        }

        $payoutLink = UserStudio::payoutLinkForUser($userId);
        if ($payoutLink && (int) $payoutLink->studio_id === (int) $studio->id) {
            return true;
        }

        $payout = $userDetail->activePayout();
        if ($this->resolvedPaymentType($userDetail) === 'studio_account') {
            return ! in_array(($payout?->status ?? null), [
                UserPayout::STATUS_REJECTED,
                UserPayout::STATUS_DISCONNECTED,
            ], true);
        }

        return false;
    }

    /**
     * Studio-invite decision for this artist↔studio link (not global user_payouts).
     *
     * @return 'approved'|'rejected'|'pending'
     */
    private function studioPayoutInviteDecisionStatus(UserDetail $userDetail, ?Studio $studio = null): string
    {
        $studio = $studio ?? $this->resolveStudioForPayoutInvite($userDetail);
        if (! $studio) {
            return 'pending';
        }

        $userId = (int) $userDetail->user_id;

        $link = UserStudio::query()
            ->where('user_id', $userId)
            ->where('studio_id', (int) $studio->id)
            ->orderByDesc('id')
            ->first();

        if ($link) {
            if ((int) $link->status === UserStudio::STATUS_ACTIVE
                && ($link->connection_type ?? '') === UserStudio::CONNECTION_ON_BOOKPAY) {
                return 'approved';
            }

            if ((int) $link->status === UserStudio::STATUS_INACTIVE) {
                return 'rejected';
            }

            // JOIN_PENDING or payout link waiting for studio accept / Stripe.
            return 'pending';
        }

        $pending = UserStudio::pendingJoinForUser($userId);
        if ($pending && (int) $pending->studio_id === (int) $studio->id) {
            return 'pending';
        }

        return 'pending';
    }

    private function payoutStatusFor(UserDetail $userDetail): string
    {
        return (string) ($userDetail->activePayout()?->status ?? 'pending');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function setPayoutStatus(UserDetail $userDetail, string $status, array $extra = []): void
    {
        $allowed = [
            'status',
            'stripe_account_id',
            'stripe_requirement',
            'stripe_requirement_email_sent_at',
            'payout_bank_country',
            'payout_waiting_list_country',
            'payout_waiting_list_at',
            'payout_mode',
            'connected_at',
            'disconnected_at',
        ];

        $payload = ['status' => $status];
        foreach ($extra as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $payload[$key] = $value;
            }
        }

        UserPayout::upsertActiveForUser((int) $userDetail->user_id, $payload);
    }

    private function pendingJoinForPayoutInvite(UserDetail $userDetail): ?UserStudio
    {
        return UserStudio::pendingJoinForUser((int) $userDetail->user_id);
    }

    private function assertStudioInviteEmailAvailable(mixed $email, ?string $paymentType): void
    {
        if ($paymentType !== 'studio_account' || empty($email)) {
            return;
        }

        if (User::emailBlockedForStudioInvite((string) $email)) {
            throw ValidationException::withMessages([
                'studio_email' => ['This email is already registered with a Bookpay account. Use a different studio email.'],
            ]);
        }
    }

    private function activatePendingJoinForStudio(UserDetail $userDetail, Studio $studio): void
    {
        // Revenue split stays pending until the studio can actually receive payouts.
        if (! $studio->hasStripeConnect()) {
            return;
        }

        $connectionType = UserStudio::CONNECTION_ON_BOOKPAY;

        // Artist-initiated invite: active payout link (payout=1) — mark accepted here.
        $payoutLink = UserStudio::payoutLinkForUser((int) $userDetail->user_id);
        if ($payoutLink && (int) $payoutLink->studio_id === (int) $studio->id) {
            $payoutLink->connection_type = $connectionType;
            $payoutLink->save();
        }

        // Dashboard join invite: pending → active.
        $pending = $this->pendingJoinForPayoutInvite($userDetail);
        if ($pending && (int) $pending->studio_id === (int) $studio->id) {
            $pending->status = UserStudio::STATUS_ACTIVE;
            $pending->connection_type = $connectionType;
            $pending->save();
        }
    }

    private function studioRelationshipEmailPhrase(?string $relationshipType): string
    {
        return match ($relationshipType) {
            'co_owner' => 'co-owned studio',
            'resident' => 'resident studio',
            'collective_member' => 'collective studio',
            'apprentice' => 'apprentice studio',
            'other' => 'partner studio',
            default => 'studio',
        };
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
