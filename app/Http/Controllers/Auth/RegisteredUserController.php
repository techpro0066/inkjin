<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CountryNotAvailableMail;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserNotRegistered;
use App\Support\StripeConnectCountries;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'registrationCountries' => StripeConnectCountries::registrationCountriesForSelect(),
            'unlistedCountries' => StripeConnectCountries::unsupportedCountryNamesForWaitingList(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $registrationCodes = array_map(
            fn (array $country) => $country['code'],
            StripeConnectCountries::registrationCountriesForSelect()
        );
        $unlistedCountries = StripeConnectCountries::unsupportedCountryNamesForWaitingList();
        $isUnlisted = $request->input('payout_bank_country') === '__not_listed__';

        $rules = [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'payout_bank_country' => ['required', 'string', Rule::in(array_merge($registrationCodes, ['__not_listed__']))],
            'referral_source' => ['nullable', 'string', 'max:255'],
        ];

        if ($isUnlisted) {
            $rules['unlisted_country'] = ['required', 'string', Rule::in($unlistedCountries)];
        }

        $validated = $request->validate($rules, [
            'payout_bank_country.required' => 'Please select your country.',
            'payout_bank_country.in' => 'Please select a valid country.',
            'unlisted_country.required' => 'Please select your country.',
            'unlisted_country.in' => 'Please select a valid country.',
        ]);

        if ($isUnlisted) {
            UserNotRegistered::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'country' => $validated['unlisted_country'],
                    'hear_about_us' => $validated['referral_source'] ?? '',
                ]
            );

            Mail::to($validated['email'])->send(
                new CountryNotAvailableMail($validated['unlisted_country'])
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'country_not_available' => true,
                ]);
            }

            return redirect()->route('register')->with('country_not_available', true);
        }

        $countryCode = strtoupper($validated['payout_bank_country']);

        $userData = [
            'first_name' => '',
            'last_name' => '',
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'artist',
            'on_boarding' => 'no',
            'on_app' => 1,
            'app_id' => null,
            'country_user_belongs_in' => $countryCode,
        ];

        $user = User::create($userData);

        $userDetail = UserDetail::create([
            'user_id' => $user->id,
            'payout_bank_country' => $countryCode,
        ]);
        StripeConnectCountries::syncCurrencyFromBankCountry($userDetail);
        $userDetail->save();

        // Login user temporarily so they can access verification page
        Auth::login($user);

        // The Registered event triggers Laravel's built-in SendEmailVerificationNotification listener,
        // which automatically calls $user->sendEmailVerificationNotification() since User implements MustVerifyEmail.
        // No need to call it manually — doing so would send duplicate emails.
        event(new Registered($user));

        // Set session flag to indicate email was sent during registration
        $request->session()->put('email_sent_on_registration', true);

        // Redirect to verification notice
        return redirect()->route('verification.notice');
    }
}
