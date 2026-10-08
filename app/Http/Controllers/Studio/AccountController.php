<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Services\LocationPreferencesService;
use App\Support\StripeConnectCountries;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        private LocationPreferencesService $locationPreferences
    ) {}

    public function profile(Request $request): View
    {
        return $this->accountView($request, 'profile');
    }

    public function updateProfile(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ], [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'avatar.image' => 'Please choose a valid image file.',
            'avatar.max' => 'Photo must be 5MB or smaller.',
        ]);

        $user = $request->user();
        $user->first_name = trim($validated['first_name']);
        $user->last_name = trim($validated['last_name']);
        $user->phone_number = trim((string) ($validated['mobile'] ?? '')) ?: null;
        $user->save();

        $studio = $this->resolveStudio($user);
        $imagePath = trim((string) ($studio?->image_url ?? ''));

        if ($request->hasFile('avatar')) {
            if (! $studio) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Studio record not found.',
                        'errors' => ['avatar' => ['Studio record not found.']],
                    ], 422);
                }

                return redirect()
                    ->route('studio.account.profile')
                    ->withErrors(['avatar' => 'Studio record not found.']);
            }

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
                'message' => 'Profile saved.',
                'status' => 'profile-updated',
                'owner' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'mobile' => $user->phone_number ?? '',
                    'initials' => $initials,
                    'avatar' => $avatarUrl,
                ],
            ]);
        }

        return redirect()
            ->route('studio.account.profile')
            ->with('status', 'profile-updated');
    }

    public function password(Request $request): View
    {
        return $this->accountView($request, 'password');
    }

    public function studio(Request $request): View
    {
        return $this->accountView($request, 'studio');
    }

    public function updateStudio(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'studio_type' => ['nullable', 'string', 'in:private,tattoo,home,collective'],
            'address' => ['nullable', 'string', 'max:1000'],
            'street_name' => ['required', 'string', 'max:255'],
            'street_number' => ['nullable', 'string', 'max:50'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:255'],
            'google_maps_link' => ['nullable', 'string', 'max:500'],
            'business_phone' => ['required', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'tattooing_since' => ['nullable', 'integer', 'min:1900', 'max:'.((int) date('Y') + 1)],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ], [
            'name.required' => 'Studio name is required.',
            'street_name.required' => 'Street is required.',
            'city.required' => 'City is required.',
            'country.required' => 'Country is required.',
            'business_phone.required' => 'Business phone is required.',
            'contact_email.email' => 'Enter a valid contact email.',
            'tattooing_since.integer' => 'Enter a valid year.',
            'logo.image' => 'Please choose a valid image file.',
            'logo.max' => 'Logo must be 5MB or smaller.',
        ]);

        $user = $request->user();
        $studio = $this->resolveStudio($user);

        if (! $studio) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Studio record not found.',
                    'errors' => ['name' => ['Studio record not found.']],
                ], 422);
            }

            return redirect()
                ->route('studio.account.studio')
                ->withErrors(['name' => 'Studio record not found.']);
        }

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
            'name' => trim($validated['name']),
            'studio_type' => $validated['studio_type'] ?? null,
            'address' => $address !== '' ? $address : null,
            'street_name' => trim($validated['street_name']),
            'street_number' => trim((string) ($validated['street_number'] ?? '')) ?: null,
            'city' => trim($validated['city']),
            'state' => trim((string) ($validated['state'] ?? '')) ?: null,
            'postal_code' => trim((string) ($validated['postal_code'] ?? '')) ?: null,
            'country' => trim($validated['country']),
            'google_maps_link' => $mapsLink !== '' ? $mapsLink : null,
            'business_phone' => trim($validated['business_phone']),
            'contact_email' => trim((string) ($validated['contact_email'] ?? '')) ?: null,
            'tattooing_since' => $validated['tattooing_since'] ?? null,
        ]);

        if (empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
        }

        $studio->save();

        $logoPath = trim((string) ($studio->logo_url ?? ''));
        if ($request->hasFile('logo')) {
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
            $logoPath = 'uploads/studios/'.$filename;
            $studio->forceFill(['logo_url' => $logoPath])->save();
            $studio->refresh();
            $logoPath = trim((string) ($studio->logo_url ?? $logoPath));
        }

        if ($user->userDetail) {
            $user->userDetail->studio_name = $studio->name;
            $user->userDetail->save();
        }

        $studioInitials = strtoupper(substr(preg_replace('/\s+/', '', $studio->name) ?: 'ST', 0, 2));
        $logoUrl = $logoPath !== '' ? asset(ltrim($logoPath, '/')) : null;

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Studio details saved.',
                'status' => 'studio-updated',
                'studio' => [
                    'name' => $studio->name,
                    'studio_type' => $studio->studio_type,
                    'address' => $studio->address,
                    'google_maps_link' => $studio->google_maps_link,
                    'initials' => $studioInitials,
                    'logo' => $logoUrl,
                ],
            ]);
        }

        return redirect()
            ->route('studio.account.studio')
            ->with('status', 'studio-updated');
    }

    public function regional(Request $request): View
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $registrationCountries = StripeConnectCountries::registrationCountriesForSelect();
        $countryCodes = array_map(fn (array $country) => $country['code'], $registrationCountries);

        $currentCountry = $this->resolveRegionalCountryCode($user, $studio, $registrationCountries);
        $defaults = $this->locationPreferences->preferencesMapForCountries($countryCodes);
        $countryDefault = $defaults[$currentCountry] ?? null;

        return $this->accountView($request, 'regional', [
            'registrationCountries' => $registrationCountries,
            'timezones' => DateTimeZone::listIdentifiers(),
            'countryDefaults' => $defaults,
            'currentCountry' => $currentCountry,
            'currentTimezone' => $studio?->timezone
                ?? $user->userDetail?->timezone
                ?? ($countryDefault['timezone'] ?? 'UTC'),
            'currentDateFormat' => $studio?->date_time_format
                ?? $user->userDetail?->date_time_format
                ?? ($countryDefault['date_time_format'] ?? 'DD/MM/YYYY'),
            'currentSizeUnit' => $studio?->size_unit
                ?? $user->userDetail?->size_unit
                ?? ($countryDefault['size_unit'] ?? 'cm'),
        ]);
    }

    public function updateRegional(Request $request): RedirectResponse|JsonResponse
    {
        $countryCodes = collect(StripeConnectCountries::registrationCountriesForSelect())
            ->pluck('code')
            ->map(fn ($code) => strtoupper((string) $code))
            ->all();

        $validated = $request->validate([
            'country' => ['required', 'string', 'size:2', Rule::in($countryCodes)],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'date_time_format' => ['required', Rule::in(['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'])],
            'size_unit' => ['required', Rule::in(['cm', 'in'])],
        ], [
            'country.required' => 'Please select your country.',
            'country.in' => 'Please select a valid country.',
            'timezone.required' => 'Please select a timezone.',
            'timezone.in' => 'Please select a valid timezone.',
            'date_time_format.required' => 'Please select a date format.',
            'size_unit.required' => 'Please select a unit.',
        ]);

        $user = $request->user();
        $studio = $this->resolveStudio($user);

        if (! $studio) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Studio record not found.',
                    'errors' => ['country' => ['Studio record not found.']],
                ], 422);
            }

            return redirect()
                ->route('studio.account.regional')
                ->withErrors(['country' => 'Studio record not found.']);
        }

        $countryCode = strtoupper($validated['country']);

        $studio->fill([
            'timezone' => $validated['timezone'],
            'date_time_format' => $validated['date_time_format'],
            'size_unit' => $validated['size_unit'],
        ]);

        if (empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
        }

        $studio->save();

        // Regional market country (ISO) — keep address country name on studios.country untouched
        $user->country_user_belongs_in = $countryCode;
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Regional settings saved.',
                'status' => 'regional-updated',
            ]);
        }

        return redirect()
            ->route('studio.account.regional')
            ->with('status', 'regional-updated');
    }

    /**
     * Prefer ISO market country on the user; fall back to studios.country when it is already a code or a known name.
     *
     * @param  array<int, array{code:string,name:string}>  $registrationCountries
     */
    private function resolveRegionalCountryCode($user, ?Studio $studio, array $registrationCountries): string
    {
        $userCode = strtoupper(trim((string) ($user->country_user_belongs_in ?? '')));
        if (strlen($userCode) === 2) {
            return $userCode;
        }

        $studioCountry = trim((string) ($studio?->country ?? ''));
        if ($studioCountry !== '') {
            $upper = strtoupper($studioCountry);
            if (strlen($upper) === 2) {
                return $upper;
            }

            foreach ($registrationCountries as $country) {
                if (strcasecmp((string) $country['name'], $studioCountry) === 0) {
                    return strtoupper((string) $country['code']);
                }
            }
        }

        return strtoupper((string) ($registrationCountries[0]['code'] ?? 'US'));
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

        // Soft-link owner if the FK is still empty (pre-migration rows / email match)
        if ($studio && empty($studio->user_id) && $user?->id) {
            $studio->user_id = $user->id;
            $studio->save();
        }

        return $studio;
    }

    private function accountView(Request $request, string $accountTab, array $extra = []): View
    {
        $user = $request->user();
        $studio = $this->resolveStudio($user);

        $studioName = trim((string) ($studio?->name ?? $user->userDetail?->resolvedStudioName() ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        $ownerFirst = trim((string) ($user->first_name ?? ''));
        $ownerLast = trim((string) ($user->last_name ?? ''));
        $ownerInitials = strtoupper(substr(($ownerFirst.$ownerLast) ?: 'ST', 0, 2));
        $studioInitials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        $imagePath = trim((string) ($studio?->image_url ?? ''));
        $ownerAvatarUrl = $imagePath !== '' ? asset(ltrim($imagePath, '/')) : null;
        $logoPath = trim((string) ($studio?->logo_url ?? ''));
        $studioLogoUrl = $logoPath !== '' ? asset(ltrim($logoPath, '/')) : null;

        return view('studio.account.'.$accountTab, array_merge([
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio?->email ?? $user->email,
            'studioInitials' => $studioInitials,
            'ownerFirstName' => $ownerFirst,
            'ownerLastName' => $ownerLast,
            'ownerMobile' => trim((string) ($user->phone_number ?? '')),
            'ownerEmail' => $user->email,
            'ownerInitials' => $ownerInitials,
            'ownerAvatarUrl' => $ownerAvatarUrl,
            'studioLogoUrl' => $studioLogoUrl,
            'profileComplete' => (string) ($user->on_boarding ?? '') === 'yes',
            'activeNav' => 'account',
            'accountTab' => $accountTab,
        ], $extra));
    }
}
