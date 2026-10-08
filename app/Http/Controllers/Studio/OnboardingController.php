<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Services\StripeConnectService;
use App\Support\StripeConnectCountries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class OnboardingController extends Controller
{
    public function profile(Request $request): View
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $studioName = trim((string) ($studio?->name ?? $user->userDetail?->resolvedStudioName() ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }

        $logoPath = trim((string) ($studio?->logo_url ?? ''));
        $logoUrl = $logoPath !== '' ? asset(ltrim($logoPath, '/')) : null;
        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        $links = is_array($studio?->links) ? $studio->links : [];

        return view('studio.onboarding.profile', [
            'studio' => $studio,
            'studioName' => $studioName,
            'studioInitials' => $initials,
            'studioLogoUrl' => $logoUrl,
            'businessPhone' => trim((string) ($studio?->business_phone ?? $user->phone_number ?? '')),
            'contactEmail' => trim((string) ($studio?->contact_email ?? $studio?->email ?? $user->email ?? '')),
            'tattooingSince' => $studio?->tattooing_since,
            'username' => trim((string) ($studio?->username ?? '')),
            'instagram' => trim((string) ($links['instagram'] ?? '')),
            'website' => trim((string) ($links['website'] ?? '')),
            'onboardingStep' => 'profile',
            'onboardingStepNumber' => 1,
            'onboardingStepTotal' => 5,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user) ?? $this->createStudioForUser($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[A-Za-z0-9._]+$/',
                Rule::unique('studios', 'username')->ignore($studio->id),
            ],
            'tattooing_since' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
            'business_phone' => ['required', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:500'],
            'logo' => [
                Rule::requiredIf(fn () => trim((string) ($studio->logo_url ?? '')) === ''),
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:5120',
            ],
        ], [
            'name.required' => 'Studio name is required.',
            'username.required' => 'Username is required.',
            'username.regex' => 'Use letters, numbers, periods and underscores only.',
            'username.unique' => 'That username is already taken.',
            'business_phone.required' => 'Business phone is required.',
            'contact_email.email' => 'Enter a valid contact email.',
            'logo.required' => 'Please upload a studio logo.',
            'logo.image' => 'Please choose a valid image file.',
            'logo.max' => 'Logo must be 5MB or smaller.',
        ]);

        $username = ltrim(trim($validated['username']), '@');

        $links = [];
        $instagram = trim((string) ($validated['instagram'] ?? ''));
        $website = trim((string) ($validated['website'] ?? ''));
        if ($instagram !== '') {
            $links['instagram'] = $instagram;
        }
        if ($website !== '') {
            $links['website'] = $website;
        }

        $studio->fill([
            'name' => trim($validated['name']),
            'username' => $username,
            'tattooing_since' => $validated['tattooing_since'] ?? null,
            'business_phone' => trim($validated['business_phone']),
            'contact_email' => trim((string) ($validated['contact_email'] ?? '')) ?: null,
            'links' => $links !== [] ? $links : null,
        ]);

        if (empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
        }

        $studio->save();

        if ($request->hasFile('logo')) {
            $logoPath = trim((string) ($studio->logo_url ?? ''));
            if ($logoPath !== '' && file_exists(public_path(ltrim($logoPath, '/')))) {
                File::delete(public_path(ltrim($logoPath, '/')));
            }

            $file = $request->file('logo');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $extension = 'jpg';
            }
            $filename = time().'_'.uniqid().'.'.$extension;
            $destination = public_path('uploads/studios');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $studio->forceFill(['logo_url' => 'uploads/studios/'.$filename])->save();
            $studio->refresh();
        }

        if ($user->userDetail) {
            $user->userDetail->studio_name = $studio->name;
            $user->userDetail->save();
        }

        $logoPath = trim((string) ($studio->logo_url ?? ''));
        $logoUrl = $logoPath !== '' ? asset(ltrim($logoPath, '/')) : null;
        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studio->name) ?: 'ST', 0, 2));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Studio profile saved.',
                'status' => 'onboarding-profile-saved',
                'redirect' => route('studio.onboarding.owner'),
                'studio' => [
                    'name' => $studio->name,
                    'username' => $studio->username,
                    'initials' => $initials,
                    'logo' => $logoUrl,
                    'links' => $studio->links ?? [],
                ],
            ]);
        }

        return redirect()
            ->route('studio.onboarding.owner')
            ->with('status', 'onboarding-profile-saved');
    }

    public function owner(Request $request): View
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $firstName = trim((string) ($user->first_name ?? ''));
        $lastName = trim((string) ($user->last_name ?? ''));
        $initials = strtoupper(substr(($firstName.$lastName) ?: 'ST', 0, 2));

        $imagePath = trim((string) ($studio?->image_url ?? ''));
        $ownerAvatarUrl = $imagePath !== '' ? asset(ltrim($imagePath, '/')) : null;

        $isTattooArtist = 'no';
        if ($studio && $studio->owner_is_artist === true) {
            $isTattooArtist = 'yes';
        } elseif ($studio && $studio->owner_is_artist === false) {
            $isTattooArtist = 'no';
        }

        return view('studio.onboarding.owner', [
            'studio' => $studio,
            'ownerFirstName' => $firstName,
            'ownerLastName' => $lastName,
            'ownerEmail' => $user->email,
            'ownerMobile' => trim((string) ($user->phone_number ?? '')),
            'ownerInitials' => $initials,
            'ownerAvatarUrl' => $ownerAvatarUrl,
            'isTattooArtist' => $isTattooArtist,
            'onboardingStep' => 'owner',
            'onboardingStepNumber' => 2,
            'onboardingStepTotal' => 5,
        ]);
    }

    public function updateOwner(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user) ?? $this->createStudioForUser($user);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:50'],
            'owner_is_artist' => ['required', Rule::in(['yes', 'no'])],
            'avatar' => [
                Rule::requiredIf(fn () => trim((string) ($studio->image_url ?? '')) === ''),
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:5120',
            ],
        ], [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'mobile.required' => 'Mobile is required.',
            'owner_is_artist.required' => 'Please choose whether you also tattoo here.',
            'owner_is_artist.in' => 'Please choose whether you also tattoo here.',
            'avatar.required' => 'Please upload a photo.',
            'avatar.image' => 'Please choose a valid image file.',
            'avatar.max' => 'Photo must be 5MB or smaller.',
        ]);

        $user->first_name = trim($validated['first_name']);
        $user->last_name = trim($validated['last_name']);
        $user->phone_number = trim($validated['mobile']);
        $user->save();

        $studio->owner_is_artist = $validated['owner_is_artist'] === 'yes';
        if (empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
        }
        $studio->save();

        $imagePath = trim((string) ($studio->image_url ?? ''));
        if ($request->hasFile('avatar')) {
            if ($imagePath !== '' && file_exists(public_path(ltrim($imagePath, '/')))) {
                File::delete(public_path(ltrim($imagePath, '/')));
            }

            $file = $request->file('avatar');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $extension = 'jpg';
            }
            $filename = time().'_'.uniqid().'.'.$extension;
            $destination = public_path('uploads/studios');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $imagePath = 'uploads/studios/'.$filename;
            $studio->forceFill(['image_url' => $imagePath])->save();
            $studio->refresh();
            $imagePath = trim((string) ($studio->image_url ?? $imagePath));
        }

        $initials = strtoupper(substr(($user->first_name.$user->last_name) ?: 'ST', 0, 2));
        $avatarUrl = $imagePath !== '' ? asset(ltrim($imagePath, '/')) : null;

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Your details saved.',
                'status' => 'onboarding-owner-saved',
                'redirect' => route('studio.onboarding.location'),
                'owner' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'mobile' => $user->phone_number ?? '',
                    'initials' => $initials,
                    'avatar' => $avatarUrl,
                    'owner_is_artist' => $studio->owner_is_artist ? 'yes' : 'no',
                ],
            ]);
        }

        return redirect()
            ->route('studio.onboarding.location')
            ->with('status', 'onboarding-owner-saved');
    }

    public function location(Request $request): View
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $studioType = trim((string) ($studio?->studio_type ?? 'tattoo'));
        if (! in_array($studioType, ['private', 'tattoo', 'home', 'collective'], true)) {
            $studioType = 'tattoo';
        }

        $addressValue = trim((string) ($studio?->address ?? ''));
        $mapsHref = trim((string) ($studio?->google_maps_link ?? ''));
        $mapsLabel = $mapsHref !== ''
            ? ((parse_url($mapsHref, PHP_URL_HOST) ?: 'google.com/maps').' · '.($addressValue !== '' ? $addressValue : 'Open map'))
            : 'Maps link will appear here';

        return view('studio.onboarding.location', [
            'studio' => $studio,
            'studioType' => $studioType,
            'addressValue' => $addressValue,
            'mapsHref' => $mapsHref,
            'mapsLabel' => $mapsLabel,
            'onboardingStep' => 'location',
            'onboardingStepNumber' => 3,
            'onboardingStepTotal' => 5,
        ]);
    }

    public function updateLocation(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user) ?? $this->createStudioForUser($user);

        $validated = $request->validate([
            'studio_type' => ['required', 'string', 'in:private,tattoo,home,collective'],
            'address' => ['nullable', 'string', 'max:1000'],
            'street_name' => ['required', 'string', 'max:255'],
            'street_number' => ['nullable', 'string', 'max:50'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:255'],
            'google_maps_link' => ['nullable', 'string', 'max:500'],
        ], [
            'studio_type.required' => 'Please select a studio type.',
            'studio_type.in' => 'Please select a valid studio type.',
            'street_name.required' => 'Street is required.',
            'city.required' => 'City is required.',
            'country.required' => 'Country is required.',
        ]);

        $address = trim((string) ($validated['address'] ?? ''));
        if ($address === '') {
            $address = collect([
                $validated['street_number'] ?? null,
                $validated['street_name'] ?? null,
                $validated['city'] ?? null,
                $validated['state'] ?? null,
                $validated['postal_code'] ?? null,
                $validated['country'] ?? null,
            ])->map(fn ($v) => trim((string) $v))->filter()->implode(', ');
        }

        $mapsLink = trim((string) ($validated['google_maps_link'] ?? ''));
        if ($mapsLink === '' && $address !== '') {
            $mapsLink = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address);
        }

        $studio->fill([
            'studio_type' => $validated['studio_type'],
            'address' => $address !== '' ? $address : null,
            'street_name' => trim($validated['street_name']),
            'street_number' => trim((string) ($validated['street_number'] ?? '')) ?: null,
            'city' => trim($validated['city']),
            'state' => trim((string) ($validated['state'] ?? '')) ?: null,
            'postal_code' => trim((string) ($validated['postal_code'] ?? '')) ?: null,
            'country' => trim($validated['country']),
            'google_maps_link' => $mapsLink !== '' ? $mapsLink : null,
        ]);

        if (empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
        }

        $studio->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Location saved.',
                'status' => 'onboarding-location-saved',
                'studio' => [
                    'studio_type' => $studio->studio_type,
                    'address' => $studio->address,
                    'google_maps_link' => $studio->google_maps_link,
                ],
            ]);
        }

        return redirect()
            ->route('studio.onboarding.terms')
            ->with('status', 'onboarding-location-saved');
    }

    public function terms(Request $request): View
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $offersSplit = $studio?->offers_revenue_split;
        $offersRent = $studio?->offers_workstation_rent;
        $artistPercent = $studio?->default_revenue_artist_percent;

        // Defaults match SO2 prototype when not yet saved.
        if ($offersSplit === null) {
            $offersSplit = true;
        }
        if ($offersRent === null) {
            $offersRent = true;
        }
        $artistPercent = (int) ($artistPercent ?? 60);
        $artistPercent = max(1, min(99, $artistPercent));

        return view('studio.onboarding.terms', [
            'studio' => $studio,
            'offersRevenueSplit' => (bool) $offersSplit,
            'offersWorkstationRent' => (bool) $offersRent,
            'defaultRevenueArtistPercent' => $artistPercent,
            'onboardingStep' => 'terms',
            'onboardingStepNumber' => 4,
            'onboardingStepTotal' => 5,
        ]);
    }

    public function updateTerms(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user) ?? $this->createStudioForUser($user);

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
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => [
                        'offers_revenue_split' => [$message],
                        'offers_workstation_rent' => [$message],
                    ],
                ], 422);
            }

            return redirect()
                ->route('studio.onboarding.terms')
                ->withErrors([
                    'offers_revenue_split' => $message,
                    'offers_workstation_rent' => $message,
                ])
                ->withInput();
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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Terms saved.',
                'status' => 'onboarding-terms-saved',
                'studio' => [
                    'offers_revenue_split' => (bool) $studio->offers_revenue_split,
                    'offers_workstation_rent' => (bool) $studio->offers_workstation_rent,
                    'default_revenue_artist_percent' => (int) $studio->default_revenue_artist_percent,
                ],
            ]);
        }

        return redirect()
            ->route('studio.onboarding.payouts')
            ->with('status', 'onboarding-terms-saved');
    }

    public function payouts(Request $request, StripeConnectService $stripeConnect): View|RedirectResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        // Don't skip ahead — finish earlier onboarding steps first.
        $next = self::nextIncompleteStepRoute($user, $studio);
        if ($next !== route('studio.onboarding.payouts')) {
            return redirect()->to($next);
        }

        $offersSplit = $studio?->offers_revenue_split;
        if ($offersSplit === null) {
            $offersSplit = true;
        }

        $stripeCountries = StripeConnectCountries::supportedForSelect();
        $currentCountry = $this->resolveOnboardingCountryCode($user, $studio, $stripeCountries);
        if (! StripeConnectCountries::isSupported($currentCountry) && $stripeCountries !== []) {
            $currentCountry = strtoupper((string) ($stripeCountries[0]['code'] ?? 'GR'));
        }

        $countryLabel = StripeConnectCountries::nameFor($currentCountry) ?? $currentCountry;
        $savedCurrency = strtoupper(trim((string) ($studio?->currency ?? '')));
        $currentCurrency = $savedCurrency !== ''
            ? $savedCurrency
            : (StripeConnectCountries::currencyForCountry($currentCountry) ?? 'EUR');

        $businessType = trim((string) ($studio?->business_type ?? ''));
        if (! in_array($businessType, ['company', 'individual'], true)) {
            $businessType = 'company';
        }

        $currencyByCountry = [];
        foreach ($stripeCountries as $country) {
            $code = strtoupper((string) $country['code']);
            $currencyByCountry[$code] = StripeConnectCountries::currencyForCountry($code) ?: 'EUR';
        }

        $stripeConnected = false;
        if ($studio && $stripeConnect->isConfigured()) {
            $accountId = $studio->resolveStripeAccountId();
            if ($accountId) {
                try {
                    $stripeConnected = $stripeConnect->isOnboardingSubmitted($accountId);
                } catch (\Throwable) {
                    $stripeConnected = false;
                }
            }
        }

        return view('studio.onboarding.payouts', [
            'studio' => $studio,
            'offersRevenueSplit' => (bool) $offersSplit,
            'stripeCountries' => $stripeCountries,
            'currentCountry' => $currentCountry,
            'countryLabel' => $countryLabel,
            'currentCurrency' => $currentCurrency,
            'businessType' => $businessType,
            'currencyByCountry' => $currencyByCountry,
            'stripeConnectConfigured' => $stripeConnect->isConfigured(),
            'stripePublishableKey' => config('services.stripe.key'),
            'stripeConnectLocale' => config('services.stripe.connect.locale', 'en-US'),
            'stripeConnected' => $stripeConnected,
            'onboardingStep' => 'payouts',
            'onboardingStepNumber' => 5,
            'onboardingStepTotal' => 5,
        ]);
    }

    public function updatePayouts(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user) ?? $this->createStudioForUser($user);

        $supportedCodes = collect(StripeConnectCountries::supportedForSelect())
            ->pluck('code')
            ->map(fn ($code) => strtoupper((string) $code))
            ->all();

        $validated = $request->validate([
            'currency' => ['required', 'string', 'size:3'],
            'business_type' => ['required', 'string', Rule::in(['company', 'individual'])],
            'country' => ['required', 'string', 'size:2', Rule::in($supportedCodes)],
        ], [
            'currency.required' => 'Please select a currency.',
            'currency.size' => 'Please select a valid currency.',
            'business_type.required' => 'Please select a business type.',
            'business_type.in' => 'Please select a valid business type.',
            'country.required' => 'Please select your business country.',
            'country.in' => 'The selected country is not supported for payouts.',
        ]);

        $studio->fill([
            'currency' => strtoupper($validated['currency']),
            'business_type' => $validated['business_type'],
            'country' => strtoupper($validated['country']),
        ]);

        if (empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
        }

        $studio->save();

        if ($request->boolean('complete_onboarding') && $user) {
            $user->on_boarding = 'yes';
            $user->save();
            app(\App\Services\MailcoachSubscriberService::class)
                ->queueSubscribeUser($user, \App\Services\MailcoachSubscriberService::TAG_STUDIO);
            app(\App\Services\MailcoachSubscriberService::class)->queueSubscribeStudio($studio);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payout settings saved.',
                'status' => 'onboarding-payouts-saved',
                'redirect' => $request->boolean('complete_onboarding')
                    ? route('studio.dashboard')
                    : null,
                'studio' => [
                    'currency' => $studio->currency,
                    'business_type' => $studio->business_type,
                    'country' => $studio->country,
                    'stripe_connected' => $studio->hasStripeConnect(),
                ],
            ]);
        }

        return redirect()
            ->route($request->boolean('complete_onboarding') ? 'studio.dashboard' : 'studio.onboarding.payouts')
            ->with('status', 'onboarding-payouts-saved');
    }

    public function createStripeSession(Request $request, StripeConnectService $stripeConnect): JsonResponse
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user) ?? $this->createStudioForUser($user);

        if (! $stripeConnect->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Stripe is not configured.'], 500);
        }

        $supportedCodes = collect(StripeConnectCountries::supportedForSelect())
            ->pluck('code')
            ->map(fn ($code) => strtoupper((string) $code))
            ->all();

        $validated = $request->validate([
            'currency' => ['required', 'string', 'size:3'],
            'business_type' => ['required', 'string', Rule::in(['company', 'individual'])],
            'country' => ['required', 'string', 'size:2', Rule::in($supportedCodes)],
            'industry' => ['nullable', 'string', Rule::in(['tattoo_studio', 'tattoo_beauty', 'other'])],
        ], [
            'currency.required' => 'Please select a currency.',
            'business_type.required' => 'Please select a business type.',
            'business_type.in' => 'Please select a valid business type.',
            'country.required' => 'Please select your business country.',
            'country.in' => 'The selected country is not supported for payouts.',
        ]);

        $country = strtoupper($validated['country']);
        $businessType = $validated['business_type'];
        $currency = strtoupper($validated['currency']);
        $industry = $validated['industry'] ?? 'tattoo_studio';

        $studio->fill([
            'currency' => $currency,
            'business_type' => $businessType,
            'country' => $country,
        ]);
        if (empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
        }
        $studio->save();

        try {
            $session = $stripeConnect->createStudioOwnerOnboardingSession($studio, [
                'business_type' => $businessType,
                'country' => $country,
                'industry' => $industry,
            ]);

            return response()->json([
                'success' => true,
                'client_secret' => $session['client_secret'],
                'account_id' => $session['account_id'],
                'publishable_key' => config('services.stripe.key'),
                'collection_options' => $session['collection_options'],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Studio onboarding Stripe session failed', [
                'studio_id' => $studio->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not start Stripe onboarding: '.$e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Studio onboarding Stripe session failed', [
                'studio_id' => $studio->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Could not start Stripe onboarding.'], 500);
        }
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
        $studio = $this->resolveStudio($user) ?? $this->createStudioForUser($user);

        $validated = $request->validate([
            'account_id' => ['required', 'string', 'regex:/^acct_[a-zA-Z0-9]+$/'],
        ]);

        try {
            $stripeConnect->finalizeStudioOwnerOnboarding($studio, $validated['account_id']);
            $studio->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Stripe payout setup completed.',
                'studio' => [
                    'stripe_account_id' => $studio->stripe_account_id,
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

    /**
     * @param  array<int, array{code:string,name:string}>  $countries
     */
    private function resolveOnboardingCountryCode($user, ?Studio $studio, array $countries): string
    {
        $codes = collect($countries)->pluck('code')->map(fn ($c) => strtoupper((string) $c))->all();

        $savedCurrencyCountry = strtoupper(trim((string) ($studio?->country ?? '')));
        if (strlen($savedCurrencyCountry) === 2 && in_array($savedCurrencyCountry, $codes, true)) {
            return $savedCurrencyCountry;
        }

        $userCode = strtoupper(trim((string) ($user->country_user_belongs_in ?? '')));
        if (strlen($userCode) === 2 && in_array($userCode, $codes, true)) {
            return $userCode;
        }

        $studioCountry = trim((string) ($studio?->country ?? ''));
        if ($studioCountry !== '') {
            $upper = strtoupper($studioCountry);
            if (strlen($upper) === 2 && in_array($upper, $codes, true)) {
                return $upper;
            }

            foreach ($countries as $country) {
                if (strcasecmp((string) $country['name'], $studioCountry) === 0) {
                    return strtoupper((string) $country['code']);
                }
            }
        }

        return strtoupper((string) ($countries[0]['code'] ?? 'GR'));
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

    private function createStudioForUser($user): Studio
    {
        return Studio::query()->create([
            'user_id' => $user->id,
            'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'Studio',
            'email' => $user->email,
            'contact_email' => $user->email,
            'business_phone' => $user->phone_number,
        ]);
    }

    /**
     * First unfinished studio onboarding step route (profile → owner → location → terms → payouts).
     */
    public static function nextIncompleteStepRoute($user, ?Studio $studio): string
    {
        if (! self::isProfileStepComplete($studio)) {
            return route('studio.onboarding.profile');
        }
        if (! self::isOwnerStepComplete($user, $studio)) {
            return route('studio.onboarding.owner');
        }
        if (! self::isLocationStepComplete($studio)) {
            return route('studio.onboarding.location');
        }
        if (! self::isTermsStepComplete($studio)) {
            return route('studio.onboarding.terms');
        }

        return route('studio.onboarding.payouts');
    }

    private static function isProfileStepComplete(?Studio $studio): bool
    {
        if (! $studio) {
            return false;
        }

        return trim((string) ($studio->name ?? '')) !== ''
            && trim((string) ($studio->username ?? '')) !== ''
            && trim((string) ($studio->business_phone ?? '')) !== ''
            && trim((string) ($studio->logo_url ?? '')) !== '';
    }

    private static function isOwnerStepComplete($user, ?Studio $studio): bool
    {
        if (! $user || ! $studio) {
            return false;
        }

        $first = trim((string) ($user->first_name ?? ''));
        $last = trim((string) ($user->last_name ?? ''));
        $phone = trim((string) ($user->phone_number ?? ''));

        return $first !== ''
            && strcasecmp($first, 'Studio') !== 0
            && $last !== ''
            && $phone !== ''
            && $studio->owner_is_artist !== null
            && trim((string) ($studio->image_url ?? '')) !== '';
    }

    private static function isLocationStepComplete(?Studio $studio): bool
    {
        if (! $studio) {
            return false;
        }

        return trim((string) ($studio->studio_type ?? '')) !== ''
            && trim((string) ($studio->street_name ?? '')) !== ''
            && trim((string) ($studio->city ?? '')) !== ''
            && trim((string) ($studio->country ?? '')) !== '';
    }

    private static function isTermsStepComplete(?Studio $studio): bool
    {
        if (! $studio) {
            return false;
        }

        // Both stay null until the terms step is saved.
        return $studio->offers_revenue_split !== null
            || $studio->offers_workstation_rent !== null;
    }
}
