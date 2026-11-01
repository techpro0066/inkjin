<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Models\StudioPayout;
use App\Services\StripeConnectService;
use App\Support\StripeConnectCountries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class MoneyController extends Controller
{
    public function earnings(Request $request): View
    {
        return view('studio.money.earnings', $this->viewData($request, 'earnings'));
    }

    public function payouts(Request $request, StripeConnectService $stripeConnect): View
    {
        $user = $request->user();
        $data = $this->viewData($request, 'payouts');
        $studio = $data['studio'];

        $offersSplit = $studio?->offers_revenue_split;
        $offersRent = $studio?->offers_workstation_rent;
        $artistPercent = $studio?->default_revenue_artist_percent;

        if ($offersSplit === null) {
            $offersSplit = true;
        }
        if ($offersRent === null) {
            $offersRent = true;
        }
        $artistPercent = max(1, min(99, (int) ($artistPercent ?? 60)));

        $stripeCountries = StripeConnectCountries::supportedForSelect();
        $currentCountry = $this->resolveCountryCode($user, $studio, $stripeCountries);
        if (! StripeConnectCountries::isSupported($currentCountry) && $stripeCountries !== []) {
            $currentCountry = strtoupper((string) ($stripeCountries[0]['code'] ?? 'GR'));
        }

        $businessType = trim((string) ($studio?->business_type ?? ''));
        if (! in_array($businessType, ['company', 'individual'], true)) {
            $businessType = 'company';
        }

        $savedCurrency = strtoupper(trim((string) ($studio?->currency ?? '')));
        $currentCurrency = $savedCurrency !== ''
            ? $savedCurrency
            : (StripeConnectCountries::currencyForCountry($currentCountry) ?? 'EUR');

        $currencyByCountry = [];
        foreach ($stripeCountries as $country) {
            $code = strtoupper((string) $country['code']);
            $currencyByCountry[$code] = StripeConnectCountries::currencyForCountry($code) ?: 'EUR';
        }

        $stripeConnected = false;
        $stripeDisplayName = $data['studioName'];
        $stripeCountryName = null;
        $stripeCurrency = $currentCurrency;
        $stripeLast4 = null;

        $accountId = $studio?->resolveStripeAccountId();
        if ($accountId && $stripeConnect->isConfigured()) {
            try {
                $stripeConnected = $stripeConnect->isOnboardingSubmitted($accountId);
            } catch (\Throwable) {
                $stripeConnected = false;
            }

            if ($stripeConnected) {
                $profile = $stripeConnect->resolveStudioDisplayProfile($accountId, $studio);
                $stripeDisplayName = trim((string) ($profile['name'] ?? '')) ?: $stripeDisplayName;
                $stripeCountryName = $profile['country_name']
                    ?? ($profile['country'] ? StripeConnectCountries::nameFor((string) $profile['country']) : null);

                if ($savedCurrency === '') {
                    $stripeCurrency = strtoupper(trim((string) ($stripeConnect->resolveCurrencyForAccount($accountId) ?? ''))) ?: $stripeCurrency;
                }
                if ($stripeCurrency === '' && ! empty($profile['country'])) {
                    $stripeCurrency = StripeConnectCountries::currencyForCountry((string) $profile['country']) ?? 'EUR';
                }

                $stripeLast4 = $stripeConnect->resolveBankLast4($accountId);
            }
        }

        if ($stripeCountryName === null || $stripeCountryName === '') {
            $stripeCountryName = StripeConnectCountries::nameFor($currentCountry) ?? $currentCountry;
        }

        $disconnectedPayouts = $studio?->id
            ? StudioPayout::disconnectedForStudio((int) $studio->id)
            : collect();

        $activePayout = $studio?->activePayout();
        $currentPayoutCountry = strtoupper(trim((string) (
            $activePayout?->payout_bank_country
            ?? $currentCountry
        )));
        $changePayoutCountryMeta = [];
        foreach ($stripeCountries as $country) {
            $code = strtoupper((string) $country['code']);
            $changePayoutCountryMeta[$code] = [
                'name' => $country['name'],
                'currency' => $currencyByCountry[$code] ?? 'EUR',
            ];
        }
        if ($currentPayoutCountry === '' || ! isset($changePayoutCountryMeta[$currentPayoutCountry])) {
            $currentPayoutCountry = (string) ($stripeCountries[0]['code'] ?? 'GR');
        }

        return view('studio.money.payouts', array_merge($data, [
            'offersRevenueSplit' => (bool) $offersSplit,
            'offersWorkstationRent' => (bool) $offersRent,
            'defaultRevenueArtistPercent' => $artistPercent,
            'stripeConnected' => $stripeConnected,
            'stripeDisplayName' => $stripeDisplayName,
            'stripeCountryName' => $stripeCountryName,
            'stripeCurrency' => $stripeCurrency !== '' ? $stripeCurrency : 'EUR',
            'stripeLast4' => $stripeLast4,
            'stripeCountries' => $stripeCountries,
            'currentCountry' => $currentCountry,
            'currentCurrency' => $currentCurrency,
            'businessType' => $businessType,
            'currencyByCountry' => $currencyByCountry,
            'stripeConnectConfigured' => $stripeConnect->isConfigured(),
            'stripePublishableKey' => config('services.stripe.key'),
            'stripeConnectLocale' => config('services.stripe.connect.locale', 'en-US'),
            'disconnectedPayouts' => $disconnectedPayouts,
            'activeStudioPayout' => $activePayout,
            'changePayoutCountries' => $stripeCountries,
            'changePayoutCountryMeta' => $changePayoutCountryMeta,
            'currentPayoutCountry' => $currentPayoutCountry,
            'currentPayoutCurrency' => $changePayoutCountryMeta[$currentPayoutCountry]['currency'] ?? $currentCurrency,
        ]));
    }

    public function updatePayouts(Request $request): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        if (! $studio) {
            return response()->json([
                'success' => false,
                'message' => 'Studio not found.',
            ], 404);
        }

        $validated = $request->validate([
            'offers_revenue_split' => ['required', 'boolean'],
            'offers_workstation_rent' => ['required', 'boolean'],
            'default_revenue_artist_percent' => ['nullable', 'integer', 'min:1', 'max:99'],
        ], [
            'offers_revenue_split.required' => 'Please choose whether you use revenue split.',
            'offers_workstation_rent.required' => 'Please choose whether you use workstation rent.',
            'default_revenue_artist_percent.min' => 'Artist share must be between 1 and 99.',
            'default_revenue_artist_percent.max' => 'Artist share must be between 1 and 99.',
        ]);

        $offersSplit = $request->boolean('offers_revenue_split');
        $offersRent = $request->boolean('offers_workstation_rent');

        if (! $offersSplit && ! $offersRent) {
            $message = 'Keep at least one on.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [
                    'offers_revenue_split' => [$message],
                    'offers_workstation_rent' => [$message],
                ],
            ], 422);
        }

        $artistPercent = (int) ($validated['default_revenue_artist_percent'] ?? 60);
        if (! $offersSplit) {
            $artistPercent = (int) ($studio->default_revenue_artist_percent ?? 60);
        }
        $artistPercent = max(1, min(99, $artistPercent));

        $studio->fill([
            'offers_revenue_split' => $offersSplit,
            'offers_workstation_rent' => $offersRent,
            'default_revenue_artist_percent' => $artistPercent,
        ]);

        if (empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
        }

        $studio->save();

        return response()->json([
            'success' => true,
            'message' => 'Changes saved',
            'studio' => [
                'offers_revenue_split' => (bool) $studio->offers_revenue_split,
                'offers_workstation_rent' => (bool) $studio->offers_workstation_rent,
                'default_revenue_artist_percent' => (int) $studio->default_revenue_artist_percent,
            ],
        ]);
    }

    public function createStripeSession(Request $request, StripeConnectService $stripeConnect): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio not found.'], 404);
        }

        if (! $stripeConnect->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Stripe is not configured.'], 500);
        }

        $supportedCodes = collect(StripeConnectCountries::supportedForSelect())
            ->pluck('code')
            ->map(fn ($code) => strtoupper((string) $code))
            ->all();

        $validated = $request->validate([
            'currency' => ['nullable', 'string', 'size:3'],
            'business_type' => ['nullable', 'string', Rule::in(['company', 'individual'])],
            'country' => ['nullable', 'string', 'size:2', Rule::in($supportedCodes)],
            'industry' => ['nullable', 'string', Rule::in(['tattoo_studio', 'tattoo_beauty', 'other'])],
            'payout_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $pendingPayoutId = (int) ($validated['payout_id'] ?? 0);
        $targetPayout = null;
        if ($pendingPayoutId > 0) {
            $targetPayout = StudioPayout::query()
                ->where('studio_id', $studio->id)
                ->where('id', $pendingPayoutId)
                ->where('is_active', false)
                ->whereIn('status', [StudioPayout::STATUS_DRAFT, StudioPayout::STATUS_PENDING])
                ->whereNull('disconnected_at')
                ->first();

            if (! $targetPayout) {
                return response()->json([
                    'success' => false,
                    'message' => 'That payout change draft is no longer available.',
                ], 422);
            }
        }

        $country = strtoupper((string) (
            $validated['country']
            ?? $targetPayout?->payout_bank_country
            ?? $studio->country
            ?? ''
        ));
        $businessType = (string) (
            $validated['business_type']
            ?? $studio->business_type
            ?? 'company'
        );
        if (! in_array($businessType, ['company', 'individual'], true)) {
            $businessType = 'company';
        }
        $currency = strtoupper((string) (
            $validated['currency']
            ?? $studio->currency
            ?? StripeConnectCountries::currencyForCountry($country)
            ?? 'EUR'
        ));
        $industry = $validated['industry'] ?? 'tattoo_studio';

        if ($country === '' || ! in_array($country, $supportedCodes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Please select your business country.',
                'errors' => ['country' => ['Please select your business country.']],
            ], 422);
        }

        // First-time connect updates studio settings; pending change keeps current settings until finalize.
        if (! $targetPayout) {
            $studio->fill([
                'currency' => $currency,
                'business_type' => $businessType,
                'country' => $country,
            ]);
            if (empty($studio->user_id) && $user?->id) {
                $studio->user_id = $user->id;
            }
            $studio->save();
        }

        try {
            $session = $stripeConnect->createStudioOwnerOnboardingSession($studio, [
                'business_type' => $businessType,
                'country' => $country,
                'industry' => $industry,
            ], $targetPayout);

            return response()->json([
                'success' => true,
                'client_secret' => $session['client_secret'],
                'account_id' => $session['account_id'],
                'payout_id' => $session['payout_id'] ?? $targetPayout?->id,
                'publishable_key' => config('services.stripe.key'),
                'collection_options' => $session['collection_options'],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Studio Money Stripe session failed', [
                'studio_id' => $studio->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not start Stripe onboarding: '.$e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Studio Money Stripe session failed', [
                'studio_id' => $studio->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Could not start Stripe onboarding.'], 500);
        }
    }

    public function changePayoutAccount(Request $request, StripeConnectService $stripeConnect): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio not found.'], 404);
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
            'business_type' => ['required', 'string', Rule::in(['company', 'individual'])],
        ], [
            'payout_bank_country.required' => 'Please select your business country.',
            'payout_bank_country.in' => 'The selected country is not supported for payouts.',
            'business_type.required' => 'Please select a business type.',
            'business_type.in' => 'Please select a valid business type.',
        ]);

        $country = strtoupper($validated['payout_bank_country']);
        $businessType = $validated['business_type'];
        $previous = $studio->activePayout();

        $payout = StudioPayout::preparePendingChangeForStudio((int) $studio->id, $country);

        return response()->json([
            'success' => true,
            'message' => 'Connect your new Stripe account for '.$country.'.',
            'payout_id' => $payout->id,
            'payout_bank_country' => $country,
            'payout_bank_country_name' => StripeConnectCountries::nameFor($country),
            'business_type' => $businessType,
            'reused' => filled($payout->stripe_account_id),
            'current_still_active' => (bool) $previous?->is_active,
        ]);
    }

    public function finalizeChangePayoutAccount(Request $request, StripeConnectService $stripeConnect): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio not found.'], 404);
        }

        $validated = $request->validate([
            'payout_id' => ['required', 'integer'],
            'stripe_account_id' => ['required', 'string', 'regex:/^acct_[a-zA-Z0-9]+$/'],
            'business_type' => ['nullable', 'string', Rule::in(['company', 'individual'])],
        ]);

        $pending = StudioPayout::query()
            ->where('studio_id', $studio->id)
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
            $payout = StudioPayout::promotePendingChangeForStudio((int) $studio->id, (int) $pending->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $studio->unsetRelation('activePayoutRelation');

        if ($payout->payout_bank_country) {
            $studio->country = strtoupper((string) $payout->payout_bank_country);
        }

        $businessType = $validated['business_type'] ?? null;
        if (! in_array($businessType, ['company', 'individual'], true)) {
            try {
                $profile = $stripeConnect->resolveStudioDisplayProfile($payout->stripe_account_id, $studio);
                $businessType = $profile['business_type'] ?? null;
            } catch (\Throwable) {
                $businessType = null;
            }
        }
        if (in_array($businessType, ['company', 'individual'], true)) {
            $studio->business_type = $businessType;
        }

        $currency = $stripeConnect->resolveCurrencyForAccount($payout->stripe_account_id);
        if ($currency !== null) {
            $studio->currency = $currency;
        } elseif ($payout->payout_bank_country) {
            $studio->currency = StripeConnectCountries::currencyForCountry((string) $payout->payout_bank_country) ?? $studio->currency;
        }
        $studio->save();

        try {
            app(\App\Services\StripeRequirementSyncService::class)->syncStudio($studio);
        } catch (\Throwable) {
            // Keep payout connected even if requirement sync fails.
        }

        return response()->json([
            'success' => true,
            'message' => 'Payout account updated',
            'payout_id' => $payout->id,
            'stripe_connected' => true,
        ]);
    }

    public function stripeStatus(Request $request, StripeConnectService $stripeConnect): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $accountId = $request->query('account_id');
        if (! is_string($accountId) || $accountId === '') {
            $accountId = $studio?->resolveStripeAccountId();
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
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => 'Could not read Stripe status.'], 500);
        }
    }

    public function completeStripe(Request $request, StripeConnectService $stripeConnect): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        if (! $studio) {
            return response()->json(['success' => false, 'message' => 'Studio not found.'], 404);
        }

        $validated = $request->validate([
            'account_id' => ['required', 'string', 'regex:/^acct_[a-zA-Z0-9]+$/'],
        ]);

        try {
            $stripeConnect->finalizeStudioOwnerOnboarding($studio, $validated['account_id']);
            $studio->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Stripe connected',
                'studio' => [
                    'stripe_account_id' => $studio->resolveStripeAccountId(),
                    'stripe_connected' => true,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Could not complete Stripe setup.',
            ], 422);
        }
    }

    public function disconnectStripe(Request $request): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        if (! $studio) {
            return response()->json([
                'success' => false,
                'message' => 'Studio not found.',
            ], 404);
        }

        $accountId = $studio->resolveStripeAccountId();
        if (! $accountId) {
            return response()->json([
                'success' => true,
                'message' => 'Bank account disconnected',
                'stripe_connected' => false,
            ]);
        }

        StudioPayout::deactivateActiveForStudio((int) $studio->id);
        $studio->unsetRelation('activePayoutRelation');

        Log::info('Studio Stripe account disconnected from Money > Payouts', [
            'studio_id' => $studio->id,
            'user_id' => $user?->id,
            'previous_stripe_account_id' => $accountId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bank account disconnected',
            'stripe_connected' => false,
        ]);
    }

    public function reactivateStripe(Request $request): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        if (! $studio) {
            return response()->json([
                'success' => false,
                'message' => 'Studio not found.',
            ], 404);
        }

        $validated = $request->validate([
            'payout_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            StudioPayout::reactivateForStudio((int) $studio->id, (int) $validated['payout_id']);
            $studio->unsetRelation('activePayoutRelation');

            return response()->json([
                'success' => true,
                'message' => 'Bank account reconnected',
                'stripe_connected' => (bool) $studio->hasStripeConnect(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not reconnect that bank account.',
            ], 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(Request $request, string $moneyTab): array
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $studioName = trim((string) ($studio?->name ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        return [
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio?->email ?? $user->email,
            'studioInitials' => $initials,
            'ownerAvatarUrl' => ($imagePath = trim((string) ($studio?->image_url ?? ''))) !== ''
                ? asset(ltrim($imagePath, '/'))
                : null,
            'profileComplete' => (string) ($user->on_boarding ?? '') === 'yes',
            'stripeConnected' => (bool) ($studio?->hasStripeConnect()),
            'activeNav' => 'money',
            'moneyTab' => $moneyTab,
        ];
    }

    private function resolveStudio($user): ?Studio
    {
        if ($user?->id) {
            $byOwner = Studio::query()->where('user_id', $user->id)->first();
            if ($byOwner) {
                return $byOwner;
            }
        }

        $email = strtolower(trim((string) ($user->email ?? '')));
        if ($email === '') {
            return null;
        }

        $studio = Studio::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($studio && empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
            $studio->save();
        }

        return $studio;
    }

    /**
     * @param  array<int, array{code:string,name:string}>  $countries
     */
    private function resolveCountryCode($user, ?Studio $studio, array $countries): string
    {
        $codes = collect($countries)->pluck('code')->map(fn ($c) => strtoupper((string) $c))->all();

        $saved = strtoupper(trim((string) ($studio?->country ?? '')));
        if (strlen($saved) === 2 && in_array($saved, $codes, true)) {
            return $saved;
        }

        $userCode = strtoupper(trim((string) ($user->country_user_belongs_in ?? '')));
        if (strlen($userCode) === 2 && in_array($userCode, $codes, true)) {
            return $userCode;
        }

        $studioCountry = trim((string) ($studio?->country ?? ''));
        if ($studioCountry !== '') {
            foreach ($countries as $country) {
                if (strcasecmp((string) $country['name'], $studioCountry) === 0) {
                    return strtoupper((string) $country['code']);
                }
            }
        }

        return strtoupper((string) ($countries[0]['code'] ?? 'GR'));
    }
}
