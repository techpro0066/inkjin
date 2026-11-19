<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\ConsentFormSetting;
use App\Models\UserDetail;
use App\Services\LocationPreferencesService;
use App\Support\ArtistNotificationPreferences;
use App\Support\StripeConnectCountries;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OtherSettingsController extends Controller
{
    public function __construct(
        private LocationPreferencesService $locationPreferences
    ) {}

    public function edit(Request $request): View
    {
        $user = $request->user();
        $userDetail = $user->userDetail
            ?? UserDetail::create(['user_id' => $user->id]);

        $registrationCountries = StripeConnectCountries::registrationCountriesForSelect();
        $countryCodes = array_map(fn (array $country) => $country['code'], $registrationCountries);
        $currentCountry = ConsentFormSetting::defaultMarketForArtist($user, $userDetail);

        return view('artist.settings.other', [
            'userDetail' => $userDetail,
            'timezones' => DateTimeZone::listIdentifiers(),
            'registrationCountries' => $registrationCountries,
            'currentCountry' => $currentCountry,
            'countryDefaults' => $this->locationPreferences->preferencesMapForCountries($countryCodes),
            'activeNav' => 'account',
        ]);
    }

    public function update(Request $request): JsonResponse
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
        $userDetail = $user->userDetail
            ?? UserDetail::create(['user_id' => $user->id]);

        $user->country_user_belongs_in = strtoupper($validated['country']);
        $user->save();

        $userDetail->update([
            'timezone' => $validated['timezone'],
            'date_time_format' => $validated['date_time_format'],
            'size_unit' => $validated['size_unit'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Regional settings updated successfully.',
        ]);
    }

    public function notifications(Request $request): View
    {
        $userDetail = $request->user()->userDetail
            ?? UserDetail::create(['user_id' => $request->user()->id]);
        $notAllowedNotifications = array_values(array_intersect(
            ArtistNotificationPreferences::KEYS,
            is_array($userDetail->not_allowed_notification) ? $userDetail->not_allowed_notification : []
        ));

        return view('artist.settings.notifications', [
            'notAllowedNotifications' => $notAllowedNotifications,
            'activeNav' => 'account',
        ]);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'not_allowed_notification' => ['present', 'array'],
            'not_allowed_notification.*' => ['required', 'string', Rule::in(ArtistNotificationPreferences::KEYS)],
        ]);

        $user = $request->user();
        $userDetail = $user->userDetail
            ?? UserDetail::create(['user_id' => $user->id]);
        $userDetail->not_allowed_notification = array_values(array_unique(
            $validated['not_allowed_notification']
        ));
        $userDetail->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences saved.',
        ]);
    }
}
