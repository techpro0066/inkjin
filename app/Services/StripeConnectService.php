<?php

namespace App\Services;

use App\Models\Studio;
use App\Models\User;
use App\Models\UserDetail;
use App\Support\StripeConnectCountries;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\AccountSession;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Transfer;

class StripeConnectService
{
    /** @var array<string, string> */
    private const COUNTRY_NAME_TO_ISO = [
        'greece' => 'GR',
        'germany' => 'DE',
        'cyprus' => 'CY',
        'france' => 'FR',
        'italy' => 'IT',
        'spain' => 'ES',
        'netherlands' => 'NL',
        'belgium' => 'BE',
        'austria' => 'AT',
        'portugal' => 'PT',
        'ireland' => 'IE',
        'united kingdom' => 'GB',
        'uk' => 'GB',
        'united states' => 'US',
        'usa' => 'US',
    ];

    public function isConfigured(): bool
    {
        return (bool) config('services.stripe.secret') && (bool) config('services.stripe.key');
    }

    public function connectLocale(): string
    {
        return (string) config('services.stripe.connect.locale', 'en-US');
    }

    /**
     * Collection options for the embedded account-onboarding component (camelCase for Connect JS).
     *
     * @return array<string, mixed>
     */
    public function embeddedCollectionOptions(?string $phase = null, bool $currentlyDueOnly = false): array
    {
        if ($this->usePhasedOnboarding() && $phase !== null && $phase !== '') {
            return $this->embeddedCollectionOptionsForPhase($phase);
        }

        $options = [
            'fields' => $currentlyDueOnly
                ? 'currently_due'
                : (string) config('services.stripe.connect.onboarding_fields', 'eventually_due'),
            'futureRequirements' => $currentlyDueOnly
                ? 'omit'
                : (string) config('services.stripe.connect.future_requirements', 'include'),
        ];

        $exclude = $this->excludedEmbeddedRequirements();
        if ($exclude !== []) {
            $options['requirements'] = [
                'exclude' => $exclude,
            ];
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    public function embeddedCollectionOptionsForPhase(string $phase): array
    {
        $only = match ($phase) {
            'personal' => $this->personalOnboardingRequirements(),
            'identity' => $this->identityOnboardingRequirements(),
            'bank' => $this->bankOnboardingRequirements(),
            default => [],
        };

        $options = [
            'fields' => (string) config('services.stripe.connect.onboarding_fields', 'eventually_due'),
            'futureRequirements' => (string) config('services.stripe.connect.future_requirements', 'include'),
        ];

        if ($only === []) {
            return $this->embeddedCollectionOptions(null);
        }

        $requirements = ['only' => $only];
        $exclude = $this->excludedEmbeddedRequirements();
        if ($phase === 'bank' && $exclude !== []) {
            $requirements['exclude'] = array_values(array_filter(
                $exclude,
                fn (string $item) => str_starts_with($item, 'external_account')
            ));
        }

        $options['requirements'] = $requirements;

        return $options;
    }

    /**
     * @return list<string>
     */
    private function personalOnboardingRequirements(): array
    {
        return [
            'individual.first_name',
            'individual.last_name',
            'individual.email',
            'individual.phone',
            'individual.dob.day',
            'individual.dob.month',
            'individual.dob.year',
            'individual.address.line1',
            'individual.address.line2',
            'individual.address.city',
            'individual.address.state',
            'individual.address.postal_code',
            'individual.id_number',
        ];
    }

    /**
     * @return list<string>
     */
    private function identityOnboardingRequirements(): array
    {
        return [
            'individual.verification.document',
            'individual.verification.document.front',
            'individual.verification.document.back',
            'individual.verification.additional_document',
            'individual.verification.additional_document.front',
            'individual.verification.additional_document.back',
        ];
    }

    /**
     * @return list<string>
     */
    private function bankOnboardingRequirements(): array
    {
        return [
            'external_account',
            'tos_acceptance.date',
            'tos_acceptance.ip',
        ];
    }

    /**
     * @return array{
     *     personal: bool,
     *     identity: bool,
     *     bank: bool,
     *     active_phase: string|null,
     *     phased: bool
     * }
     */
    public function getOnboardingPhaseStatus(string $accountId): array
    {
        $status = $this->getOnboardingStatus($accountId);
        $due = array_values(array_unique(array_merge(
            $status['currently_due'],
            $status['eventually_due']
        )));

        $personalComplete = ! $this->requirementsDueForPhase('personal', $due);
        $identityComplete = ! $this->requirementsDueForPhase('identity', $due);
        $bankComplete = ! $this->requirementsDueForPhase('bank', $due);

        $activePhase = null;
        if (! $personalComplete) {
            $activePhase = 'personal';
        } elseif (! $identityComplete) {
            $activePhase = 'identity';
        } elseif (! $bankComplete) {
            $activePhase = 'bank';
        }

        return [
            'personal' => $personalComplete,
            'identity' => $identityComplete,
            'bank' => $bankComplete,
            'active_phase' => $activePhase,
            'phased' => $this->usePhasedOnboarding(),
        ];
    }

    /**
     * @param  list<string>  $due
     */
    private function requirementsDueForPhase(string $phase, array $due): bool
    {
        foreach ($due as $requirement) {
            if ($this->requirementMatchesPhase($phase, $requirement)) {
                return true;
            }
        }

        return false;
    }

    private function requirementMatchesPhase(string $phase, string $requirement): bool
    {
        $needles = match ($phase) {
            'personal' => [
                'individual.first_name',
                'individual.last_name',
                'individual.email',
                'individual.phone',
                'individual.dob.',
                'individual.address.',
                'individual.id_number',
            ],
            'identity' => ['individual.verification.'],
            'bank' => ['external_account', 'tos_acceptance'],
            default => [],
        };

        foreach ($needles as $needle) {
            if (str_ends_with($needle, '.')) {
                if (str_starts_with($requirement, $needle)) {
                    return true;
                }
            } elseif ($requirement === $needle || str_starts_with($requirement, $needle.'.')) {
                return true;
            }
        }

        return false;
    }

    private function usePhasedOnboarding(): bool
    {
        return (bool) config('services.stripe.connect.phased_onboarding', false);
    }

    /**
     * Requirements intentionally excluded from the embedded UI (identity verification is not excluded).
     *
     * @return list<string>
     */
    private function excludedEmbeddedRequirements(): array
    {
        $exclude = [];

        if ($this->forceIndividualBusinessType()) {
            $exclude[] = 'business_type';
        }

        if ($this->excludeBusinessDetailsFromUi()) {
            $exclude[] = 'business_profile.*';
        }

        if ($this->excludeBankCountryFromUi()) {
            $exclude[] = 'external_account.*.country';
            $exclude[] = 'external_account.*.currency';
        }

        return $exclude;
    }

    /**
     * @return array{enabled: bool, features?: array<string, bool>}
     */
    private function accountOnboardingComponents(): array
    {
        $components = ['enabled' => true];

        if ($this->disableStripeUserAuthentication()) {
            // Skip Stripe SMS/popup user-auth step; go straight to personal details.
            // Requires controller.requirement_collection = application on the connected account.
            $components['features'] = [
                'disable_stripe_user_authentication' => true,
            ];
        }

        return $components;
    }

    private function disableStripeUserAuthentication(): bool
    {
        return (bool) config('services.stripe.connect.disable_user_authentication', true);
    }

    /**
     * @return array{account_id: string, client_secret: string, collection_options: array<string, mixed>}
     */
    public function createOnboardingSession(User $user, UserDetail $userDetail, ?string $phase = null, bool $currentlyDueOnly = false): array
    {
        $this->initialize();

        $accountId = $this->ensureConnectedAccount($user, $userDetail);
        $this->ensureAccountCapabilitiesRequested($accountId);

        $session = AccountSession::create([
            'account' => $accountId,
            'components' => [
                'account_onboarding' => $this->accountOnboardingComponents(),
            ],
        ]);

        return [
            'account_id' => $accountId,
            'client_secret' => $session->client_secret,
            'collection_options' => $this->embeddedCollectionOptions($phase, $currentlyDueOnly),
            'phase' => $phase,
            'phased' => $this->usePhasedOnboarding(),
        ];
    }

    public function resolveStudioStripeAccountId(Studio $studio): ?string
    {
        return $studio->resolveStripeAccountId();
    }

    /**
     * @param  array{business_type: string, country: string, industry: string}  $setup
     * @return array{account_id: string, client_secret: string, collection_options: array<string, mixed>}
     */
    public function createStudioOnboardingSession(Studio $studio, UserDetail $userDetail, array $setup): array
    {
        $this->initialize();

        $accountId = $this->ensureStudioConnectedAccount($studio, $userDetail, $setup);
        $this->ensureAccountCapabilitiesRequested($accountId);

        $session = AccountSession::create([
            'account' => $accountId,
            'components' => [
                'account_onboarding' => $this->accountOnboardingComponents(),
            ],
        ]);

        return [
            'account_id' => $accountId,
            'client_secret' => $session->client_secret,
            'collection_options' => $this->embeddedCollectionOptionsForStudio($setup['business_type']),
        ];
    }

    /**
     * Account session for an already-connected studio that still has Stripe fields due.
     *
     * @return array{account_id: string, client_secret: string, collection_options: array<string, mixed>}
     */
    public function createStudioRequirementsSession(Studio $studio): array
    {
        $this->initialize();

        $accountId = trim((string) ($studio->resolveStripeAccountId() ?? ''));
        if ($accountId === '' || ! preg_match('/^acct_[a-zA-Z0-9]+$/', $accountId)) {
            throw new \RuntimeException('Studio does not have a connected Stripe account yet.');
        }

        if ($studio->stripe_account_id !== $accountId) {
            $studio->stripe_account_id = $accountId;
            $studio->save();
        }

        $this->ensureAccountCapabilitiesRequested($accountId);

        $session = AccountSession::create([
            'account' => $accountId,
            'components' => [
                'account_onboarding' => $this->accountOnboardingComponents(),
            ],
        ]);

        return [
            'account_id' => $accountId,
            'client_secret' => $session->client_secret,
            'collection_options' => $this->embeddedCollectionOptions(null, true),
        ];
    }

    /**
     * @param  array{business_type: string, country: string, industry: string}  $setup
     */
    public function ensureStudioConnectedAccount(Studio $studio, UserDetail $userDetail, array $setup): string
    {
        $this->initialize();

        $country = strtoupper(trim((string) ($setup['country'] ?? '')));
        if ($country === '') {
            throw new \RuntimeException('Studio country must be selected before starting Stripe onboarding.');
        }

        $businessType = $setup['business_type'] === 'individual' ? 'individual' : 'company';

        $existingId = $this->resolveStudioStripeAccountId($studio);
        if ($existingId !== null && $existingId !== '') {
            try {
                $account = Account::retrieve($existingId);
                if (strtoupper((string) ($account->country ?? '')) !== $country) {
                    Log::info('Studio Stripe account country mismatch; creating a new connected account', [
                        'studio_id' => $studio->id,
                        'stripe_account_id' => $existingId,
                        'account_country' => $account->country ?? null,
                        'expected_country' => $country,
                    ]);
                } else {
                    $this->ensureAccountCapabilitiesRequested($existingId);
                    $this->syncStudioProfileToAccount($existingId, $studio, $userDetail, $setup);

                    return $existingId;
                }
            } catch (ApiErrorException $e) {
                Log::warning('Stored studio Stripe account not found, creating a new one', [
                    'studio_id' => $studio->id,
                    'stripe_account_id' => $existingId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $payload = array_merge(
            $this->baseConnectedAccountCreatePayload($businessType, [
                'inkjin_studio_id' => (string) $studio->id,
                'inkjin_studio_industry' => $setup['industry'],
                'inkjin_studio_business_type' => $businessType,
            ], $country),
            [
                'business_profile' => $this->buildStudioBusinessProfile($studio, $setup),
            ],
        );

        $account = Account::create(array_filter($payload, fn ($value) => $value !== null));

        return $account->id;
    }

    /**
     * @return array{mcc: string, description: string}
     */
    public function studioIndustryMeta(string $industry): array
    {
        return match ($industry) {
            'tattoo_beauty' => [
                'mcc' => '7230',
                'description' => 'Tattoo and beauty studio offering tattoos, body art, beauty, piercing, and barber services.',
            ],
            'tattoo_studio' => [
                'mcc' => '7299',
                'description' => 'Tattoo studio offering professional tattoos and body art.',
            ],
            default => [
                'mcc' => '7299',
                'description' => 'Professional tattoo and body art services.',
            ],
        };
    }

    public function resolveCurrencyForAccount(string $accountId): ?string
    {
        try {
            $this->initialize();
            $account = Account::retrieve($accountId);
            $currency = strtoupper(trim((string) ($account->default_currency ?? '')));
            if ($currency !== '') {
                return $currency;
            }
            if (! empty($account->country)) {
                return StripeConnectCountries::currencyForCountry((string) $account->country);
            }
        } catch (ApiErrorException $e) {
            Log::warning('Could not resolve Stripe account currency', [
                'stripe_account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Display profile for an existing studio Stripe account (approval review page).
     *
     * @return array{
     *     name: string,
     *     email: ?string,
     *     address: ?string,
     *     country: ?string,
     *     country_name: ?string,
     *     business_type: ?string,
     *     business_type_label: ?string
     * }
     */
    public function resolveStudioDisplayProfile(string $accountId, Studio $studio): array
    {
        $fallback = [
            'name' => $studio->name ?? 'Studio',
            'email' => $studio->email,
            'address' => null,
            'country' => null,
            'country_name' => null,
            'business_type' => null,
            'business_type_label' => null,
        ];

        try {
            $this->initialize();
            $account = Account::retrieve($accountId);

            $name = $studio->name ?? 'Studio';
            $email = $studio->email;
            $address = null;
            $country = ! empty($account->country) ? strtoupper((string) $account->country) : null;
            $businessType = ! empty($account->business_type) ? (string) $account->business_type : null;

            if ($businessType === 'company' && ! empty($account->company)) {
                $company = $account->company;
                if (! empty($company->name)) {
                    $name = (string) $company->name;
                }
                if (! empty($account->email)) {
                    $email = (string) $account->email;
                }
                $address = $this->formatStripeAddressLine($company->address ?? null);
            } elseif ($businessType === 'individual' && ! empty($account->individual)) {
                $individual = $account->individual;
                $fullName = trim(((string) ($individual->first_name ?? '')).' '.((string) ($individual->last_name ?? '')));
                if ($fullName !== '') {
                    $name = $fullName;
                }
                if (! empty($individual->email)) {
                    $email = (string) $individual->email;
                }
                $address = $this->formatStripeAddressLine($individual->address ?? null);
            }

            $businessTypeLabel = match ($businessType) {
                'individual' => 'Individual',
                'company' => 'Business',
                default => null,
            };

            return [
                'name' => $name,
                'email' => $email,
                'address' => $address,
                'country' => $country,
                'country_name' => $country ? StripeConnectCountries::nameFor($country) : null,
                'business_type' => $businessType,
                'business_type_label' => $businessTypeLabel,
            ];
        } catch (\Throwable $e) {
            Log::warning('Could not resolve studio display profile from Stripe', [
                'stripe_account_id' => $accountId,
                'studio_id' => $studio->id,
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }

    /**
     * @param  object|null  $address
     */
    private function formatStripeAddressLine(?object $address): ?string
    {
        if ($address === null) {
            return null;
        }

        $cityState = trim((string) ($address->city ?? ''));
        if (! empty($address->state)) {
            $cityState = $cityState === ''
                ? (string) $address->state
                : $cityState.', '.((string) $address->state);
        }

        $parts = array_filter([
            $address->line1 ?? null,
            $address->line2 ?? null,
            $cityState !== '' ? $cityState : null,
            $address->postal_code ?? null,
        ], fn ($value) => $value !== null && trim((string) $value) !== '');

        if ($parts === []) {
            return null;
        }

        return implode(', ', array_map(fn ($value) => trim((string) $value), $parts));
    }

    /**
     * Mark the artist as approved after the studio finishes Stripe embedded onboarding.
     */
    public function finalizeStudioOnboarding(Studio $studio, UserDetail $userDetail, string $accountId): void
    {
        if (! $this->isOnboardingSubmitted($accountId)) {
            throw new \RuntimeException('Stripe onboarding is not complete yet.');
        }

        $studio->stripe_account_id = $accountId;
        $studio->save();

        $userDetail->stripe_account_id = $accountId;
        $userDetail->payment_status = 'approved';

        $currency = $this->resolveCurrencyForAccount($accountId);
        if ($currency !== null) {
            $userDetail->currency = $currency;
        }

        try {
            app(\App\Services\StripeRequirementSyncService::class)->syncStudio($studio);
            $userDetail->refresh();
        } catch (\Throwable) {
            $studio->stripe_requirement = true;
            $studio->save();
            $userDetail->stripe_requirement = true;
            $userDetail->save();
        }

        app(\App\Services\MailcoachSubscriberService::class)->queueSubscribeStudio($studio);
    }

    /**
     * @return array<string, mixed>
     */
    public function embeddedCollectionOptionsForStudio(string $businessType): array
    {
        $options = [
            'fields' => (string) config('services.stripe.connect.onboarding_fields', 'eventually_due'),
            'futureRequirements' => (string) config('services.stripe.connect.future_requirements', 'include'),
        ];

        $exclude = [
            'business_type',
            'business_profile.mcc',
            'business_profile.product_description',
            'business_profile.url',
        ];

        if ($businessType === 'individual') {
            $exclude[] = 'business_profile.*';
        }

        if ($this->excludeBankCountryFromUi()) {
            $exclude[] = 'external_account.*.country';
            $exclude[] = 'external_account.*.currency';
        }

        $options['requirements'] = ['exclude' => array_values(array_unique($exclude))];

        return $options;
    }

    /**
     * @param  array{business_type: string, country: string, industry: string}  $setup
     * @return array<string, string>
     */
    private function buildStudioBusinessProfile(Studio $studio, array $setup): array
    {
        $industryMeta = $this->studioIndustryMeta($setup['industry']);
        $isIndividual = ($setup['business_type'] ?? '') === 'individual';

        if ($isIndividual) {
            return array_filter([
                'mcc' => $industryMeta['mcc'],
                'product_description' => $industryMeta['description'],
                'url' => $this->individualBusinessWebsiteUrl(),
            ]);
        }

        $studioName = $this->nonEmpty($studio->name) ?? 'Studio';

        return array_filter([
            'name' => $studioName,
            'support_email' => $this->nonEmpty($studio->email),
            'mcc' => $industryMeta['mcc'],
            'product_description' => $industryMeta['description'],
        ]);
    }

    /**
     * @param  array{business_type: string, country: string, industry: string}  $setup
     */
    private function syncStudioProfileToAccount(string $accountId, Studio $studio, UserDetail $userDetail, array $setup): void
    {
        $updates = array_filter([
            'business_profile' => $this->buildStudioBusinessProfile($studio, $setup),
            'metadata' => [
                'inkjin_studio_id' => (string) $studio->id,
                'inkjin_studio_industry' => $setup['industry'],
                'inkjin_studio_business_type' => $setup['business_type'] === 'individual' ? 'individual' : 'company',
            ],
        ], fn ($value) => $value !== null && $value !== []);

        if ($updates === []) {
            return;
        }

        try {
            Account::update($accountId, $updates);
        } catch (ApiErrorException $e) {
            Log::warning('Could not sync studio profile to Stripe Connect account', [
                'stripe_account_id' => $accountId,
                'studio_id' => $studio->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array{business_type: string, country: string, industry: string}  $setup
     * @return array<string, mixed>
     */
    private function buildStudioStripeAccountPrefill(Studio $studio, UserDetail $userDetail, array $setup): array
    {
        $country = strtoupper(trim($setup['country']));
        $businessType = $setup['business_type'] === 'individual' ? 'individual' : 'company';
        $address = $this->buildStudioAddressFromUserDetail($userDetail, $country);

        if ($businessType === 'individual') {
            return $address !== null ? ['individual' => ['address' => $address]] : [];
        }

        $company = array_filter([
            'name' => $this->nonEmpty($studio->name) ?? 'Studio',
        ], fn ($value) => $value !== null && $value !== '');

        if ($address !== null) {
            $company['address'] = $address;
        }

        return $company !== [] ? ['company' => $company] : [];
    }

    /**
     * Build Stripe address fields from the artist's saved studio location.
     *
     * @return array<string, string>|null
     */
    private function buildStudioAddressFromUserDetail(UserDetail $userDetail, string $accountCountryIso): ?array
    {
        $line1 = trim(trim((string) ($userDetail->street_number ?? '')).' '.trim((string) ($userDetail->street_name ?? '')));
        if ($line1 === '') {
            $line1 = trim((string) ($userDetail->studio_address ?? ''));
        }

        $address = array_filter([
            'line1' => $this->nonEmpty($line1),
            'city' => $this->nonEmpty($userDetail->city),
            'state' => $this->nonEmpty($userDetail->state),
            'postal_code' => $this->nonEmpty($userDetail->postal_code),
            'country' => strtoupper($accountCountryIso),
        ], fn ($value) => $value !== null && $value !== '');

        if (empty($address['line1'])) {
            return null;
        }

        return $address;
    }

    public function ensureConnectedAccount(User $user, UserDetail $userDetail): string
    {
        $this->initialize();

        $country = $this->resolvePayoutBankCountry($userDetail);
        if ($country === null) {
            throw new \RuntimeException('Signup country is missing or not supported for Stripe payouts.');
        }

        if (! empty($userDetail->stripe_account_id)) {
            try {
                $account = Account::retrieve($userDetail->stripe_account_id);
                if (strtoupper((string) ($account->country ?? '')) !== $country) {
                    Log::info('Stripe account country mismatch; creating a new connected account', [
                        'user_id' => $user->id,
                        'stripe_account_id' => $userDetail->stripe_account_id,
                        'account_country' => $account->country ?? null,
                        'expected_country' => $country,
                    ]);
                    $userDetail->stripe_account_id = null;
                    $userDetail->save();
                } else {
                    $this->ensureIndividualBusinessType($userDetail->stripe_account_id);
                    $this->ensureAccountCapabilitiesRequested($userDetail->stripe_account_id);
                    $this->syncProfileToAccount($userDetail->stripe_account_id, $user, $userDetail);

                    return $userDetail->stripe_account_id;
                }
            } catch (ApiErrorException $e) {
                Log::warning('Stored Stripe account not found, creating a new one', [
                    'user_id' => $user->id,
                    'stripe_account_id' => $userDetail->stripe_account_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $account = Account::create(array_filter(array_merge(
            $this->baseConnectedAccountCreatePayload('individual', [
                'inkjin_user_id' => (string) $user->id,
            ], $country),
            $this->buildStripeAccountPrefill($user, $userDetail),
        ), fn ($value) => $value !== null));

        $userDetail->stripe_account_id = $account->id;
        $userDetail->save();

        return $account->id;
    }

    /**
     * @return array{
     *     complete: bool,
     *     payout_ready: bool,
     *     details_submitted: bool,
     *     charges_enabled: bool,
     *     payouts_enabled: bool,
     *     currently_due: array<int, string>,
     *     eventually_due: array<int, string>,
     *     disabled_reason: string|null
     * }
     */
    public function getOnboardingStatus(string $accountId): array
    {
        $this->initialize();

        $account = Account::retrieve($accountId);
        $requirements = $account->requirements ?? null;
        $currentlyDue = $requirements->currently_due ?? [];
        $eventuallyDue = $requirements->eventually_due ?? [];
        $pendingVerification = $requirements->pending_verification ?? [];
        $detailsSubmitted = (bool) ($account->details_submitted ?? false);
        $identityStillDue = $this->identityRequirementsOutstanding($currentlyDue, []);

        // User finished embedded onboarding — nothing left for them to submit now.
        // Stripe may still be reviewing (pending_verification); that is OK for InkJin onboarding.
        $complete = $currentlyDue === []
            && ($detailsSubmitted || $pendingVerification !== []);

        $payoutReady = (bool) $account->charges_enabled
            && (bool) $account->payouts_enabled
            && $currentlyDue === [];

        return [
            'complete' => $complete,
            'payout_ready' => $payoutReady,
            'details_submitted' => $detailsSubmitted,
            'charges_enabled' => (bool) $account->charges_enabled,
            'payouts_enabled' => (bool) $account->payouts_enabled,
            'currently_due' => $currentlyDue,
            'eventually_due' => $eventuallyDue,
            'pending_verification' => $pendingVerification,
            'identity_verification_due' => $identityStillDue,
            'submitted' => $detailsSubmitted,
            'disabled_reason' => $requirements->disabled_reason ?? null,
        ];
    }

    /**
     * Whether Stripe still needs user action (same rules as stripe:sync-requirements).
     *
     * @param  array<string, mixed>  $status
     */
    public function accountNeedsUserAction(array $status): bool
    {
        if (! empty($status['disabled_reason'])) {
            return true;
        }

        if (! empty($status['currently_due'])) {
            return true;
        }

        if (empty($status['charges_enabled']) || empty($status['payouts_enabled'])) {
            return true;
        }

        return false;
    }

    /**
     * Whether the artist still has Stripe form fields to submit (not pending review alone).
     *
     * @param  array<string, mixed>  $status
     */
    public function accountNeedsUserSubmission(array $status): bool
    {
        if (! empty($status['disabled_reason'])) {
            return true;
        }

        return ! empty($status['currently_due']);
    }

    /**
     * @param  array<int, string>  $currentlyDue
     * @param  array<int, string>  $eventuallyDue
     */
    private function identityRequirementsOutstanding(array $currentlyDue, array $eventuallyDue): bool
    {
        $prefixes = [
            'individual.verification',
            'individual.id_number',
            'individual.dob',
        ];

        foreach (array_merge($currentlyDue, $eventuallyDue) as $requirement) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($requirement, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isOnboardingComplete(string $accountId): bool
    {
        return $this->getOnboardingStatus($accountId)['complete'];
    }

    /**
     * True when the user has submitted their Stripe details (InkJin can finish onboarding).
     * Details may still be under review or have later requirements.
     */
    public function isOnboardingSubmitted(string $accountId): bool
    {
        $status = $this->getOnboardingStatus($accountId);

        return (bool) ($status['details_submitted'] ?? false)
            || (bool) ($status['complete'] ?? false);
    }

    public function isPayoutReady(string $accountId): bool
    {
        return $this->getOnboardingStatus($accountId)['payout_ready'];
    }

    /**
     * Transfer funds from the platform balance to a connected account (Separate charges & transfers).
     *
     * @param  array<string, string>  $metadata
     * @return array{id: string, amount: int, currency: string, destination: string}
     */
    public function transferToConnectedAccount(
        string $destinationAccountId,
        int $amountCents,
        string $currency,
        ?string $sourceChargeId = null,
        array $metadata = [],
        ?string $idempotencyKey = null,
    ): array {
        $this->initialize();

        $destinationAccountId = trim($destinationAccountId);
        if ($destinationAccountId === '' || ! preg_match('/^acct_[a-zA-Z0-9]+$/', $destinationAccountId)) {
            throw new \InvalidArgumentException('Invalid Stripe connected account ID.');
        }

        if ($amountCents < 1) {
            throw new \InvalidArgumentException('Transfer amount must be at least one cent.');
        }

        $payload = [
            'amount' => $amountCents,
            'currency' => strtolower($currency),
            'destination' => $destinationAccountId,
            'metadata' => $metadata,
        ];

        if ($sourceChargeId !== null && $sourceChargeId !== '') {
            $payload['source_transaction'] = $sourceChargeId;
        }

        $options = [];
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $options['idempotency_key'] = $idempotencyKey;
        }

        $transfer = Transfer::create($payload, $options);

        return [
            'id' => $transfer->id,
            'amount' => (int) $transfer->amount,
            'currency' => strtoupper((string) $transfer->currency),
            'destination' => (string) $transfer->destination,
        ];
    }

    public function resolveChargeIdFromPaymentIntent(?string $paymentIntentId): ?string
    {
        if ($paymentIntentId === null || trim($paymentIntentId) === '') {
            return null;
        }

        $this->initialize();

        try {
            $intent = PaymentIntent::retrieve($paymentIntentId);
            $latestCharge = $intent->latest_charge ?? null;

            if (is_string($latestCharge) && $latestCharge !== '') {
                return $latestCharge;
            }

            if (is_object($latestCharge) && isset($latestCharge->id)) {
                return (string) $latestCharge->id;
            }
        } catch (ApiErrorException $e) {
            Log::warning('Could not resolve Stripe charge from payment intent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function forceIndividualBusinessType(): bool
    {
        return (bool) config('services.stripe.connect.force_individual', true);
    }

    private function excludeBusinessDetailsFromUi(): bool
    {
        return (bool) config('services.stripe.connect.exclude_business_details', true);
    }

    private function excludeBankCountryFromUi(): bool
    {
        return (bool) config('services.stripe.connect.exclude_bank_country', true);
    }

    /**
     * Resolve the ISO country for the Stripe connected account (bank payout country).
     */
    public function resolvePayoutBankCountry(UserDetail $userDetail): ?string
    {
        $code = strtoupper(trim((string) $userDetail->payout_bank_country));
        if ($code !== '' && StripeConnectCountries::isSupported($code)) {
            return $code;
        }

        return null;
    }

    private function ensureIndividualBusinessType(string $accountId): void
    {
        if (! $this->forceIndividualBusinessType()) {
            return;
        }

        $account = Account::retrieve($accountId);
        if (($account->business_type ?? null) === 'individual') {
            return;
        }

        Account::update($accountId, ['business_type' => 'individual']);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStripeAccountPrefill(User $user, UserDetail $userDetail): array
    {
        $prefill = [];

        $businessProfile = [];
        $mcc = $this->artistMerchantCategoryCode();
        if ($mcc !== null) {
            $businessProfile['mcc'] = $mcc;
        }

        if ($this->excludeBusinessDetailsFromUi()) {
            $aliasMode = $userDetail->personal_page_name_alias;
            $displayName = match ($aliasMode) {
                'username' => $this->nonEmpty($userDetail->user_name),
                'display_name' => $this->nonEmpty($userDetail->display_name),
                default => $this->nonEmpty($userDetail->display_name) ?? $this->nonEmpty($userDetail->user_name),
            };

            $businessProfile = array_merge($businessProfile, array_filter([
                'name' => $this->nonEmpty($displayName),
                'url' => $this->individualBusinessWebsiteUrl(),
                'product_description' => $this->nonEmpty($userDetail->personal_page_tagline)
                    ?? $this->nonEmpty($userDetail->personal_page_description)
                    ?? 'Professional tattoo artist and body art services.',
            ], fn ($value) => $value !== null && $value !== ''));
        }

        if ($businessProfile !== []) {
            $prefill['business_profile'] = $businessProfile;
        }

        return $prefill;
    }

    /**
     * Default merchant category code (industry) for tattoo artist connected accounts.
     * Stripe maps MCC to the industry shown in Connect onboarding / Dashboard.
     */
    private function artistMerchantCategoryCode(): ?string
    {
        $mcc = trim((string) config('services.stripe.connect.artist_mcc', '7299'));
        if ($mcc === '' || ! preg_match('/^\d{4}$/', $mcc)) {
            return null;
        }

        return $mcc;
    }

    /**
     * Connect account create payload. Country must be set by the platform (InkJin)
     * because accounts with no Stripe Dashboard cannot change country in embedded UI.
     *
     * @param  array<string, string>  $metadata
     * @return array<string, mixed>
     */
    private function baseConnectedAccountCreatePayload(string $businessType, array $metadata, string $country): array
    {
        $country = strtoupper(trim($country));
        $defaultCurrency = StripeConnectCountries::currencyForCountry($country);

        return array_filter([
            'country' => $country,
            'business_type' => $businessType,
            'default_currency' => $defaultCurrency ? strtolower($defaultCurrency) : null,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'controller' => [
                'fees' => ['payer' => 'application'],
                'losses' => ['payments' => 'application'],
                'stripe_dashboard' => ['type' => 'none'],
                'requirement_collection' => 'application',
            ],
            'metadata' => $metadata,
        ], fn ($value) => $value !== null);
    }

    private function ensureAccountCapabilitiesRequested(string $accountId): void
    {
        try {
            Account::update($accountId, [
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);
        } catch (ApiErrorException $e) {
            Log::warning('Could not request Stripe Connect capabilities', [
                'stripe_account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function syncProfileToAccount(string $accountId, User $user, UserDetail $userDetail): void
    {
        $prefill = $this->buildStripeAccountPrefill($user, $userDetail);
        if ($prefill === []) {
            return;
        }

        try {
            Account::update($accountId, $prefill);
        } catch (ApiErrorException $e) {
            Log::warning('Could not prefill Stripe Connect account from onboarding profile', [
                'stripe_account_id' => $accountId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function nonEmpty(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function individualBusinessWebsiteUrl(): string
    {
        $url = trim((string) config('services.stripe.connect.individual_business_website_url', 'https://www.inkjin.com'));
        if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        return $this->isStripeAcceptableUrl($url) ? $url : 'https://www.inkjin.com';
    }

    private function artistProfileUrlForStripe(UserDetail $userDetail): ?string
    {
        $userName = $this->nonEmpty($userDetail->user_name);
        if ($userName === null) {
            return null;
        }

        $configuredBase = $this->nonEmpty((string) config('services.stripe.connect.business_url_base'));
        $url = $configuredBase !== null
            ? rtrim($configuredBase, '/').'/'.ltrim($userName, '/')
            : url('/'.$userName);

        return $this->isStripeAcceptableUrl($url) ? $url : null;
    }

    private function isStripeAcceptableUrl(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        $blockedHosts = ['localhost', '127.0.0.1', '::1'];
        if (in_array($host, $blockedHosts, true)) {
            return false;
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
            return false;
        }

        return true;
    }

    private function initialize(): void
    {
        $secret = config('services.stripe.secret');
        if (! $secret) {
            throw new \RuntimeException('Stripe is not configured.');
        }

        Stripe::setApiKey($secret);
    }

    private function resolveCountryCode(?string $country): string
    {
        $default = strtoupper((string) config('services.stripe.connect.default_country', 'GR'));
        $country = trim((string) $country);

        if ($country === '') {
            return $default;
        }

        if (strlen($country) === 2 && ctype_alpha($country)) {
            return strtoupper($country);
        }

        $normalized = strtolower($country);

        return self::COUNTRY_NAME_TO_ISO[$normalized] ?? $default;
    }

    /**
     * Delete a connected account from Stripe (test accounts can always be deleted).
     *
     * @return array{id: string, deleted: bool}
     */
    public function deleteConnectedAccount(string $accountId): array
    {
        $this->initialize();

        $accountId = trim($accountId);
        if ($accountId === '' || ! preg_match('/^acct_[a-zA-Z0-9]+$/', $accountId)) {
            throw new \InvalidArgumentException('Invalid Stripe account ID. Expected format: acct_...');
        }

        $account = Account::retrieve($accountId);
        $account->delete();

        return [
            'id' => $account->id,
            'deleted' => (bool) ($account->deleted ?? true),
        ];
    }
}
