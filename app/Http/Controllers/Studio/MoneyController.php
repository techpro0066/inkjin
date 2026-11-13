<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Studio;
use App\Models\StudioPayout;
use App\Services\ArtistPayoutService;
use App\Services\ArtistReferralFeeWaiverService;
use App\Services\StripeConnectService;
use App\Support\StripeConnectCountries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MoneyController extends Controller
{
    public function earnings(Request $request, ArtistPayoutService $payout, StripeConnectService $stripeConnect): View
    {
        $data = $this->viewData($request, 'earnings');
        $studio = $data['studio'];

        $rows = $studio
            ? $this->studioEarningsRows($studio, $payout)
            : collect();

        return view('studio.money.earnings', array_merge($data, [
            'earningsRows' => $rows,
            'earningsSummary' => $this->studioEarningsSummary($rows, $studio, $stripeConnect),
        ]));
    }

    public function exportEarnings(Request $request, ArtistPayoutService $payout): StreamedResponse
    {
        $studio = $this->resolveStudio($request->user());
        abort_unless($studio, 404);

        $rows = $this->studioEarningsRows($studio, $payout);
        $filename = 'studio-earnings-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, [
                'Date',
                'Booking',
                'Artist',
                'Paid by client',
                'Studio share',
                'Studio share %',
                'Fee',
                'Studio gets',
                'Status',
            ]);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['date'],
                    $row['booking_label'],
                    $row['artist'],
                    number_format($row['client_paid'], 2, '.', ''),
                    number_format($row['studio_share'], 2, '.', ''),
                    $row['studio_percent'].'%',
                    number_format($row['studio_fee'], 2, '.', ''),
                    number_format($row['studio_gets'], 2, '.', ''),
                    $row['status_label'],
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
     * Studio share rows for revenue-split bookings at this studio.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function studioEarningsRows(Studio $studio, ArtistPayoutService $payout): Collection
    {
        return Booking::query()
            ->where('studio_id', $studio->id)
            ->where('payment_status', 'paid')
            ->whereNotNull('deposit_amount')
            ->where('deposit_amount', '>', 0)
            ->with(['artist.userDetail', 'artistPayout', 'user'])
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->limit(300)
            ->get()
            ->map(fn (Booking $booking) => $this->mapStudioEarningsRow($booking, $payout))
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapStudioEarningsRow(Booking $booking, ArtistPayoutService $payout): ?array
    {
        $userDetail = $booking->artist?->userDetail;
        $paymentType = $payout->resolvePayoutPaymentType($booking, $userDetail);
        $artistPercent = $payout->resolvePayoutArtistPercent($booking, $userDetail);
        $studioPercent = max(0, 100 - $artistPercent);
        $hasSplit = $paymentType === 'studio_account' && $studioPercent > 0;

        if (! $hasSplit) {
            return null;
        }

        $deposit = max(0, round((float) ($booking->deposit_amount ?? 0), 2));
        $stripeFee = $payout->estimateStripeFee($deposit);
        $studioShare = round($deposit * ($studioPercent / 100), 2);
        $studioStripe = round($stripeFee * ($studioPercent / 100), 2);

        // Artist-paid booking fee is shared by the same split % (client-paid fee is not).
        $artistSideFee = 0.0;
        if ($userDetail) {
            $feeDetail = $payout->artistFeeAllocationFor((int) $booking->artist_user_id, $userDetail)['details'][(int) $booking->id] ?? null;
            $artistSideFee = $feeDetail
                ? max(0, round((float) $feeDetail['fee_applied'], 2))
                : max(0, round(app(ArtistReferralFeeWaiverService::class)
                    ->artistFeeForBooking($booking, $userDetail), 2));
        }
        $artistBookingFee = round($artistSideFee * ($artistPercent / 100), 2);
        $studioBookingFee = max(0, round($artistSideFee - $artistBookingFee, 2));
        $studioFee = round($studioStripe + $studioBookingFee, 2);
        $studioGets = max(0, round($studioShare - $studioFee, 2));

        if ($studioGets < 0.01) {
            return null;
        }

        $payoutRow = $booking->artistPayout;
        $releasedStudio = ($payoutRow && $payoutRow->isCompleted())
            ? (float) ($payoutRow->studio_amount ?? 0)
            : 0.0;

        // Share already fell through to the artist (studio wasn't Connect-ready).
        if ($booking->pay_artist && $releasedStudio < 0.01) {
            return null;
        }

        $deadline = $payout->cancellationDeadlineAt($booking);
        $tz = config('app.timezone');
        $statusKey = 'held';
        $statusLabel = 'Held';
        $statusPill = 'a';
        $statusDate = null;

        if ($releasedStudio >= 0.01) {
            $statusKey = 'paid';
            $statusPill = 'g';
            $paidAt = $payoutRow?->updated_at ?? $booking->deposit_released_at ?? $booking->updated_at;
            $statusDate = $paidAt?->timezone($tz);
            $statusLabel = 'Paid'.($statusDate ? ' '.$statusDate->format('d/m') : '');
        } elseif (($booking->status ?? '') === 'completed' && $payout->hasPassedCancellationDeadline($booking)) {
            $statusKey = 'on_its_way';
            $statusPill = 'b';
            $statusLabel = 'On its way';
        } elseif ($deadline) {
            $statusKey = 'held';
            $statusPill = 'a';
            $statusDate = $deadline->copy()->timezone($tz);
            $statusLabel = 'Held until '.$statusDate->format('d/m');
        } else {
            $statusKey = 'held';
            $statusPill = 'a';
            $statusLabel = 'Held';
        }

        $artist = $booking->artist;
        $artistName = $artist?->userDetail?->publicDisplayName();
        if (! $artistName) {
            $artistName = $artist
                ? (trim(($artist->first_name ?? '').' '.($artist->last_name ?? '')) ?: 'Artist')
                : 'Artist';
        }

        $client = $booking->user;
        $clientName = $client
            ? trim(($client->first_name ?? '').' '.($client->last_name ?? ''))
            : '';
        if ($clientName === '') {
            $clientName = 'Client';
        }
        $clientShort = $this->shortClientName($clientName);

        $title = $booking->displayTitle();
        $bookingLabel = $title.' · '.$clientShort;
        $date = $booking->booking_date?->timezone($tz) ?? $booking->created_at?->timezone($tz);

        return [
            'id' => (int) $booking->id,
            'date' => $date?->format('d/m/Y') ?? '—',
            'date_sort' => $date?->timestamp ?? 0,
            'booking_label' => $bookingLabel,
            'artist' => $artistName,
            'client_paid' => $deposit,
            'studio_share' => $studioShare,
            'studio_percent' => $studioPercent,
            'studio_stripe' => $studioStripe,
            'studio_booking_fee' => $studioBookingFee,
            'studio_fee' => $studioFee,
            'studio_gets' => $studioGets,
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'status_pill' => $statusPill,
            'paid_at' => $statusKey === 'paid' ? $statusDate : null,
            'held_until' => $statusKey === 'held' ? $statusDate : null,
            'month_key' => $date?->format('Y-m') ?? '',
        ];
    }

    /**
     * Snapshot for studio Home KPIs.
     *
     * @return array<string, mixed>
     */
    public function dashboardEarningsSummary(?Studio $studio, ArtistPayoutService $payout, StripeConnectService $stripeConnect): array
    {
        $rows = $studio
            ? $this->studioEarningsRows($studio, $payout)
            : collect();

        return $this->studioEarningsSummary($rows, $studio, $stripeConnect);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function studioEarningsSummary(Collection $rows, ?Studio $studio, StripeConnectService $stripeConnect): array
    {
        $held = round((float) $rows->where('status_key', 'held')->sum('studio_gets'), 2);
        $onItsWay = round((float) $rows->where('status_key', 'on_its_way')->sum('studio_gets'), 2);
        $thisMonthKey = now()->format('Y-m');
        $lastMonthKey = now()->copy()->subMonthNoOverflow()->format('Y-m');
        $earnedThisMonth = round((float) $rows->where('month_key', $thisMonthKey)->sum('studio_gets'), 2);
        $earnedLastMonth = round((float) $rows->where('month_key', $lastMonthKey)->sum('studio_gets'), 2);

        $monthDeltaLabel = null;
        if ($earnedLastMonth >= 0.01) {
            $pct = round((($earnedThisMonth - $earnedLastMonth) / $earnedLastMonth) * 100);
            $monthDeltaLabel = ($pct >= 0 ? '+' : '').$pct.'% from last month';
        } elseif ($earnedThisMonth >= 0.01) {
            $monthDeltaLabel = 'First earnings this month';
        } else {
            $monthDeltaLabel = 'No earnings yet this month';
        }

        $nextAmount = $onItsWay;
        $nextMeta = 'Nothing queued yet';
        if ($nextAmount >= 0.01) {
            $nextMeta = 'After Stripe settles to your bank';
        } elseif ($held >= 0.01) {
            $nextAmount = 0.0;
            $nextMeta = 'Held amounts release when each window ends';
        }

        $bankLast4 = null;
        $accountId = $studio?->resolveStripeAccountId();
        if ($accountId && $stripeConnect->isConfigured()) {
            $bankLast4 = $stripeConnect->resolveBankLast4($accountId);
        }
        if ($bankLast4) {
            $nextMeta = ($nextAmount >= 0.01 ? $nextMeta.' · ' : '').'bank ••• '.$bankLast4;
        }

        return [
            'next_payout' => $nextAmount,
            'next_payout_meta' => $nextMeta,
            'held' => $held,
            'earned_this_month' => $earnedThisMonth,
            'month_delta_label' => $monthDeltaLabel,
            'bank_last4' => $bankLast4,
        ];
    }

    private function shortClientName(string $full): string
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        if (count($parts) < 2) {
            return $full;
        }

        $last = $parts[count($parts) - 1];

        return $parts[0].' '.mb_strtoupper(mb_substr($last, 0, 1)).'.';
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
