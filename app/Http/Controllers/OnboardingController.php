<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use App\Models\UserBankDetail;
use App\Models\Studio;
use App\Mail\StudioPaymentDecisionMail;
use App\Mail\StudioFirstTimeConnectMail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Models\QuestionSorting;

class OnboardingController extends Controller
{
    /** Slugs allowed for primary + other tattoo styles (must match onboarding UI). */
    private const TATTOO_STYLE_SLUGS = [
        'traditional', 'neo-traditional', 'japanese', 'realism', 'blackwork',
        'minimalist', 'geometric', 'watercolor', 'tribal', 'dotwork', 'new-school', 'illustrative',
    ];

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

        return view('onboarding.styles-social', $this->onboardingViewData($request) + ['activeNav' => 'styles-social']);
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

    public function payment(Request $request)
    {
        if ($redirect = $this->ensureOnboardingPage($request, 6)) {
            return $redirect;
        }

        return view('onboarding.payment', $this->onboardingViewData($request) + ['activeNav' => 'payment']);
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
                    'unique:user_details,user_name,' . ($userDetail ? $userDetail->id : 'NULL') . ',id'
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
            $validated = $request->validate([
                'tattooing_since' => ['required', 'integer', 'min:1970', 'max:'.$maxYear],
                'primary_style' => ['required', 'string', Rule::in(self::TATTOO_STYLE_SLUGS)],
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
                if (! in_array($slug, self::TATTOO_STYLE_SLUGS, true)) {
                    throw ValidationException::withMessages([
                        'other_styles' => ['One or more additional styles are invalid. Please pick styles from the list.'],
                    ]);
                }
            }

            $socialIn = $validated['social_links'] ?? [];
            $website = isset($socialIn['website']) ? trim((string) $socialIn['website']) : '';
            if ($website !== '') {
                if (! preg_match('#^https?://#i', $website)) {
                    throw ValidationException::withMessages([
                        'social_links.website' => ['Website must start with http:// or https://'],
                    ]);
                }
                if (filter_var($website, FILTER_VALIDATE_URL) === false) {
                    throw ValidationException::withMessages([
                        'social_links.website' => ['Please enter a valid website URL.'],
                    ]);
                }
            }

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $social = array_filter($validated['social_links'] ?? [], fn ($v) => $v !== null && $v !== '');

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
            $validated = $request->validate([
                'tattooing_since' => ['required', 'integer', 'min:1970', 'max:'.$maxYear],
                'primary_style' => ['required', 'string', Rule::in(self::TATTOO_STYLE_SLUGS)],
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
                if (! in_array($slug, self::TATTOO_STYLE_SLUGS, true)) {
                    throw ValidationException::withMessages([
                        'other_styles' => ['One or more additional styles are invalid. Please pick styles from the list.'],
                    ]);
                }
            }

            $socialIn = $validated['social_links'] ?? [];
            $website = isset($socialIn['website']) ? trim((string) $socialIn['website']) : '';
            if ($website !== '') {
                if (! preg_match('#^https?://#i', $website)) {
                    throw ValidationException::withMessages([
                        'social_links.website' => ['Website must start with http:// or https://'],
                    ]);
                }
                if (filter_var($website, FILTER_VALIDATE_URL) === false) {
                    throw ValidationException::withMessages([
                        'social_links.website' => ['Please enter a valid website URL.'],
                    ]);
                }
            }

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
            $social = array_filter($validated['social_links'] ?? [], fn ($v) => $v !== null && $v !== '');
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
                'workspace_type' => ['required', 'string', 'max:32'],
            ], [
                'workspace_type.required' => 'Please select a workspace type.',
            ]);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $userDetail->update([
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
            ]);

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
            ]);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $userDetail->update([
                'studio_name' => $validated['studio_name'],
                'studio_address' => $validated['studio_address'],
                'street_name' => $validated['street_name'],
                'street_number' => $validated['street_number'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country'],
                'google_maps_link' => $validated['google_maps_link'] ?? null,
            ]);

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
    public function updateCalendar(Request $request)
    {
        try {
            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $validated = $request->validate([
                'scheduling_type' => ['required', 'in:auto,managed'],
            ]);

            $schedulingType = $validated['scheduling_type'];

            // If auto scheduling is selected, calendar connection is required
            if ($schedulingType === 'auto') {
                $calendarConnected = !empty($userDetail->google_calendar_token);
                
                if (!$calendarConnected) {
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
            } else if ($schedulingType === 'managed') {
                // Clear calendar connection when switching to managed
                $userDetail->update([
                    'google_calendar_token' => null,
                    'google_calendar_id' => null,
                ]);
            }

            $userDetail->update([
                'scheduling_type' => $schedulingType,
            ]);

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
                    'message' => 'An error occurred: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'An error occurred: ' . $e->getMessage())
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

            if ((int) $request->input('disconnect_studio', 0) === 1) {
                if (($userDetail->payment_type ?? null) !== 'studio_account' || empty($userDetail->studio_id)) {
                    $msg = 'No linked studio payout to disconnect.';
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return redirect()->back()->with('error', $msg);
                }

                $userDetail->payment_type = 'artist_account';
                $userDetail->studio_id = null;
                $userDetail->stripe_account_id = null;
                $userDetail->payment_status = null;
                $userDetail->save();

                $msg = 'Studio payout disconnected. Please connect another payment method.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => true, 'message' => $msg]);
                }
                return redirect()->route('settings.payment')->with('success', $msg);
            }

            $rules = [
                'payment_type' => ['required', 'in:artist_account,studio_account,inkjin_account'],
            ];

            $messages = [
                'payment_type.required' => 'Please select a payment type.',
                'payment_type.in' => 'Invalid payment type selected.',
            ];

            $paymentType = $request->payment_type;
            if ($paymentType === 'artist_account') {
                $rules['stripe_account_id'] = ['required', 'string', 'max:255'];
                $messages['stripe_account_id.required'] = 'Please connect your Stripe account to receive payments.';
            } elseif ($paymentType === 'studio_account') {
                $rules['studio_email'] = ['required', 'email', 'max:255'];
                $messages['studio_email.required'] = 'Studio email is required.';
                $messages['studio_email.email'] = 'Please enter a valid email address.';
            } elseif ($paymentType === 'inkjin_account') {
                $rules['account_holder_name'] = ['required', 'string', 'max:255'];
                $rules['bank_name'] = ['required', 'string', 'max:255'];
                $rules['account_number'] = ['required', 'string', 'max:255'];
                $rules['swift_bic'] = ['required', 'string', 'max:50'];
                $rules['currency'] = ['required', 'string', 'size:3'];

                $messages['account_holder_name.required'] = 'Account holder name is required.';
                $messages['bank_name.required'] = 'Bank name is required.';
                $messages['account_number.required'] = 'Account number is required.';
                $messages['swift_bic.required'] = 'SWIFT/BIC is required.';
                $messages['currency.required'] = 'Please select a currency.';
            }

            $validated = $request->validate($rules, $messages);

            // If user is switching from studio payouts to artist payouts, do not allow reusing the studio's Stripe ID
            if (
                $paymentType === 'artist_account' &&
                ($userDetail->payment_type ?? null) === 'studio_account' &&
                !empty($userDetail->stripe_account_id) &&
                isset($validated['stripe_account_id']) &&
                $validated['stripe_account_id'] === $userDetail->stripe_account_id
            ) {
                return redirect()->back()
                    ->with('error', 'Please connect your own Stripe account for Artist payouts.')
                    ->withInput();
            }

            $userDetail->payment_type = $validated['payment_type'];

            if ($paymentType === 'artist_account') {
                $userDetail->stripe_account_id = $validated['stripe_account_id'];
                $userDetail->studio_id = null;
                $userDetail->payment_status = 'approved';
            } elseif ($paymentType === 'studio_account') {
                $studioName = $userDetail->studio_name ?? 'Studio';
                $studioEmail = strtolower(trim($validated['studio_email']));
                $studio = Studio::firstWhere('email', $studioEmail);
                $existingStudio = (bool) $studio;
                if (!$studio) {
                    $studio = Studio::create([
                        'name' => $studioName,
                        'email' => $studioEmail,
                        'stripe_account_id' => null,
                    ]);
                }

                $userDetail->studio_id = $studio->id;
                $userDetail->stripe_account_id = $studio->stripe_account_id;
                $userDetail->payment_status = 'pending';
            } else {
                // inkjin_account
                $userDetail->studio_id = null;
                $userDetail->stripe_account_id = null;
                $userDetail->payment_status = 'approved';
                $userDetail->currency = strtoupper($validated['currency']);

                $this->upsertUserBankDetails($user, $validated);
            }

            $userDetail->save();

            if ($paymentType === 'studio_account') {
                if ($existingStudio) {
                    $this->sendStudioDecisionEmail($user, $userDetail, $studio, true);
                } else {
                    $this->sendStudioFirstTimeConnectEmail($user, $userDetail, $studio);
                }
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment settings updated successfully!',
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
     * Save onboarding calendar / scheduling type (auto vs managed, Google Calendar when auto).
     */
    public function saveCalendar(Request $request)
    {
        try {
            $user = $request->user();   
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            // Check if this is a preferences update from settings page
            if ($request->has('currency') || $request->has('session_buffer_period')) {
                // This is a preferences update from settings page
                // Check if consultation is required
                $requireConsultation = $request->has('require_consultation') && $request->require_consultation == '1';
                
                $validationRules = [
                    'currency' => ['required'],
                    'timezone' => ['required'],
                    'date_time_format' => ['required'],
                    'size_unit' => ['required'],
                    'minimum_deposit_amount' => ['required', 'numeric', 'min:0'],
                    'minimum_deposit_type' => ['required'],
                    'cancellation_window' => ['required'],
                    'reschedule_times' => ['required'],
                    'session_buffer_period' => ['required', 'integer', 'min:0'],
                    'require_consultation' => ['nullable', 'boolean'],
                ];
                
                // Only require session type, duration, and consultation timing if consultation is required
                if ($requireConsultation) {
                    $validationRules['session_type'] = ['required', 'in:online,physical,both'];
                    $validationRules['session_duration_minutes'] = ['required', 'integer', 'min:15', 'max:480'];
                    $validationRules['consultation_timing'] = ['required', 'in:combined,separate'];
                    
                    // Validate gap fields if consultation timing is separate
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
                
                $validated = $request->validate($validationRules);

                $minimumDepositAmount = (float) $validated['minimum_deposit_amount'];

                $updateData = [
                    'currency' => $validated['currency'],
                    'timezone' => $validated['timezone'],
                    'date_time_format' => $validated['date_time_format'],
                    'size_unit' => $validated['size_unit'],
                    'minimum_deposit_amount' => $minimumDepositAmount,
                    'minimum_deposit_type' => $validated['minimum_deposit_type'],
                    'cancellation_window' => $validated['cancellation_window'],
                    'reschedule_times' => $validated['reschedule_times'],
                    'session_buffer_period' => (int) $validated['session_buffer_period'],
                    'require_consultation' => $requireConsultation,
                ];
                
                // Only save session type, duration, and consultation timing if consultation is required
                if ($requireConsultation) {
                    $updateData['session_type'] = $validated['session_type'];
                    $updateData['session_duration_minutes'] = (int) $validated['session_duration_minutes'];
                    $updateData['consultation_timing'] = $validated['consultation_timing'];
                    
                    // Handle gap fields for separate consultation timing
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
                        // Clear gap fields when not separate
                        $updateData['require_gap_between_consultation_tattoo'] = false;
                        $updateData['consultation_tattoo_gap_value'] = null;
                        $updateData['consultation_tattoo_gap_unit'] = null;
                    }
                } else {
                    // Clear session type, duration, consultation timing, and gap fields when consultation is disabled
                    $updateData['session_type'] = null;
                    $updateData['session_duration_minutes'] = null;
                    $updateData['consultation_timing'] = null;
                    $updateData['require_gap_between_consultation_tattoo'] = false;
                    $updateData['consultation_tattoo_gap_value'] = null;
                    $updateData['consultation_tattoo_gap_unit'] = null;
                }
                
                $userDetail->update($updateData);

                return response()->json([
                    'success' => true,
                    'message' => 'Preferences updated successfully',
                ]);
            }

            // This is the scheduling type selection step (onboarding step 3)
            $validated = $request->validate([
                'scheduling_type' => ['required', 'in:auto,managed'],
            ]);

            $schedulingType = $validated['scheduling_type'];

            // If auto scheduling is selected, calendar connection is required
            if ($schedulingType === 'auto') {
            $calendarConnected = !empty($userDetail->google_calendar_token);
                
                if (!$calendarConnected) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please connect your Google Calendar for auto scheduling.',
                        'errors' => [
                            'google_calendar_connected' => ['Google Calendar connection is required for auto scheduling.']
                        ],
                    ], 422);
                }
            }
            else if($schedulingType === 'managed'){
                $userDetail->update([
                    'google_calendar_token' => null,
                    'google_calendar_id' => null,
                ]);
            }
            
            $completedSteps = $userDetail->completed_steps ?? [];
            if (! in_array(5, $completedSteps)) {
                $completedSteps[] = 5;
            }

            $userDetail->update([
                'scheduling_type' => $schedulingType,
                'current_step' => 6,
                'completed_steps' => $completedSteps,
            ]);

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
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save onboarding preferences (currency, deposits, booking rules, consultation options).
     */
    public function savePreferences(Request $request)
    {
        try {
            // Check if consultation is required
            $requireConsultation = $request->has('require_consultation') && $request->require_consultation == '1';
            
            $validationRules = [
                'currency' => ['required'],
                'timezone' => ['required'],
                'date_time_format' => ['required'],
                'size_unit' => ['required'],
                'minimum_deposit_amount' => ['required', 'numeric', 'min:0'],
                'minimum_deposit_type' => ['required'],
                'booking_fee_type' => ['required', 'in:client,artist,split'],
                'reschedule_times' => ['required'],
                'cancellation_window' => ['required'],
                'session_buffer_period' => ['required', 'integer', 'min:0'],
                'require_consultation' => ['nullable', 'boolean'],
            ];
            
            // Only require session type, duration, and consultation timing if consultation is required
            if ($requireConsultation) {
                $validationRules['session_type'] = ['required', 'in:online,physical,both'];
                $validationRules['session_duration_minutes'] = ['required', 'integer', 'min:15', 'max:480'];
                $validationRules['consultation_timing'] = ['required', 'in:combined,separate'];
                
                // Validate gap fields if consultation timing is separate
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
            
            $validated = $request->validate($validationRules);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            // Convert minimum_deposit_amount to decimal
            $minimumDepositAmount = (float) $validated['minimum_deposit_amount'];

            // Only update onboarding progress if user is still in onboarding process
            $updateData = [
                'currency' => $validated['currency'],
                'timezone' => $validated['timezone'],
                'date_time_format' => $validated['date_time_format'],
                'size_unit' => $validated['size_unit'],
                'minimum_deposit_amount' => $minimumDepositAmount,
                'minimum_deposit_type' => $validated['minimum_deposit_type'],
                'booking_fee_type' => $validated['booking_fee_type'],
                'reschedule_times' => $validated['reschedule_times'],
                'cancellation_window' => $validated['cancellation_window'],
                'session_buffer_period' => (int) $validated['session_buffer_period'],
                'require_consultation' => $requireConsultation,
            ];
            
            // Only save session type, duration, and consultation timing if consultation is required
            if ($requireConsultation) {
                $updateData['session_type'] = $validated['session_type'];
                $updateData['session_duration_minutes'] = (int) $validated['session_duration_minutes'];
                $updateData['consultation_timing'] = $validated['consultation_timing'];
                
                // Handle gap fields for separate consultation timing
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
                    // Clear gap fields when not separate
                    $updateData['require_gap_between_consultation_tattoo'] = false;
                    $updateData['consultation_tattoo_gap_value'] = null;
                    $updateData['consultation_tattoo_gap_unit'] = null;
                }
            } else {
                // Clear session type, duration, consultation timing, and gap fields when consultation is disabled
                $updateData['session_type'] = null;
                $updateData['session_duration_minutes'] = null;
                $updateData['consultation_timing'] = null;
                $updateData['require_gap_between_consultation_tattoo'] = false;
                $updateData['consultation_tattoo_gap_value'] = null;
                $updateData['consultation_tattoo_gap_unit'] = null;
            }
            
            // Only update onboarding progress if user hasn't completed onboarding
            if ($user->on_boarding !== 'yes') {
                $updateData['current_step'] = 5;
                $updateData['completed_steps'] = array_unique(array_merge($userDetail->completed_steps ?? [], [4]));
            }
            
            $userDetail->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Preferences saved',
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
                'message' => 'An error occurred: ' . $e->getMessage(),
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

            // Base validation - payment_type is always required
            $rules = [
                'payment_type' => ['required', 'in:artist_account,studio_account,inkjin_account'],
            ];

            $messages = [
                'payment_type.required' => 'Please select a payment type.',
                'payment_type.in' => 'Invalid payment type selected.',
            ];

            // Conditional validation based on payment_type
            $paymentType = $request->payment_type;
            
            if ($paymentType === 'artist_account') {
                // Artist account: Stripe account ID is required
                $rules['stripe_account_id'] = ['required', 'string', 'max:255'];
                $messages['stripe_account_id.required'] = 'Please connect your Stripe account to complete onboarding.';
            } elseif ($paymentType === 'studio_account') {
                // Studio account: Studio email is required
                $rules['studio_email'] = ['required', 'email', 'max:255'];
                $messages['studio_email.required'] = 'Studio email is required.';
                $messages['studio_email.email'] = 'Please enter a valid email address.';
            } elseif ($paymentType === 'inkjin_account') {
                $rules['account_holder_name'] = ['required', 'string', 'max:255'];
                $rules['bank_name'] = ['required', 'string', 'max:255'];
                $rules['account_number'] = ['required', 'string', 'max:255'];
                $rules['swift_bic'] = ['required', 'string', 'max:50'];
                $rules['currency'] = ['required', 'string', 'size:3'];
                $messages['account_holder_name.required'] = 'Account holder name is required.';
                $messages['bank_name.required'] = 'Bank name is required.';
                $messages['account_number.required'] = 'Account number is required.';
                $messages['swift_bic.required'] = 'SWIFT/BIC is required.';
                $messages['currency.required'] = 'Please select a currency.';
            }

            $validated = $request->validate($rules, $messages);

            // Always set payment_type and completed_steps
            $userDetail->payment_type = $validated['payment_type'];
            $userDetail->completed_steps = array_unique(array_merge($userDetail->completed_steps ?? [], [6]));

            // Handle artist account: Stripe ID saved on user_details
            if ($paymentType === 'artist_account' && isset($validated['stripe_account_id'])) {
                $userDetail->stripe_account_id = $validated['stripe_account_id'];
                $userDetail->studio_id = null;
                $userDetail->payment_status = 'approved';
            } elseif ($paymentType === 'studio_account') {
                // Studio account: find or create studio record
                $studioName = $userDetail->studio_name ?? 'Studio';
                $studioEmail = strtolower(trim($validated['studio_email']));
                $studio = Studio::firstWhere('email', $studioEmail);
                $existingStudio = (bool) $studio;
                if (!$studio) {
                    $studio = Studio::create([
                        'name' => $studioName,
                        'email' => $studioEmail,
                        'stripe_account_id' => null,
                    ]);
                }

                // Link artist to studio and mirror studio Stripe state
                $userDetail->studio_id = $studio->id;
                $userDetail->stripe_account_id = $studio->stripe_account_id;
                $userDetail->payment_status = 'pending';
            } elseif ($paymentType === 'inkjin_account') {
                // Inkjin account: clear studio and artist Stripe references
                $userDetail->studio_id = null;
                $userDetail->stripe_account_id = null;
                $userDetail->payment_status = 'approved';
                $userDetail->currency = strtoupper($validated['currency']);
                $this->upsertUserBankDetails($user, $validated);
            }

            $userDetail->save();

            if ($paymentType === 'studio_account') {
                if ($existingStudio) {
                    $this->sendStudioDecisionEmail($user, $userDetail, $studio, true);
                } else {
                    $this->sendStudioFirstTimeConnectEmail($user, $userDetail, $studio);
                }
            }

            // Mark onboarding as complete
            $user->update(['on_boarding' => 'yes']);

            $questions = QuestionSorting::where('user_id', '1')->where('is_active', true)->orderBy('order')->get();

            foreach ($questions as $question) {
                QuestionSorting::create([
                    'user_id' => $user->id,
                    'question_id' => $question->question_id,
                    'order' => $question->order,
                    'is_active' => $question->is_active,
                ]);
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

    public function studioPaymentDecision(Request $request, UserDetail $userDetail, string $decision)
    {
        if (!$request->hasValidSignature()) {
            return view('studio.payment-decision-result', [
                'status' => 'error',
                'message' => 'Invalid or expired link.',
            ]);
        }

        $decision = strtolower($decision);
        if (!in_array($decision, ['allow', 'decline'], true)) {
            return view('studio.payment-decision-result', [
                'status' => 'error',
                'message' => 'Invalid decision.',
            ]);
        }

        if ($userDetail->payment_type !== 'studio_account' || empty($userDetail->studio_id)) {
            return view('studio.payment-decision-result', [
                'status' => 'error',
                'message' => 'This request is no longer active.',
            ]);
        }

        if (in_array((string) $userDetail->payment_status, ['approved', 'rejected'], true)) {
            return view('studio.payment-decision-result', [
                'status' => 'locked',
                'message' => 'Decision already submitted and cannot be changed.',
            ]);
        }

        $studio = Studio::find($userDetail->studio_id);
        if (!$studio) {
            return view('studio.payment-decision-result', [
                'status' => 'error',
                'message' => 'Studio not found.',
            ]);
        }

        if ($decision === 'allow') {
            $userDetail->payment_status = 'approved';
            $userDetail->stripe_account_id = $studio->stripe_account_id;
            $userDetail->save();

            return view('studio.payment-decision-result', [
                'status' => 'approved',
                'message' => 'Artist request approved successfully.',
            ]);
        }

        $userDetail->payment_status = 'rejected';
        $userDetail->stripe_account_id = null;
        $userDetail->save();

        return view('studio.payment-decision-result', [
            'status' => 'rejected',
            'message' => 'Artist request declined successfully.',
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
            'approved' => 'Your studio payment request has been approved.',
            'rejected' => 'Your studio payment request was declined. Please contact your studio or update your payment method.',
            default => 'Your studio payment request is pending approval. You will get access after studio approval.',
        };

        return view('studio.payment-request-status', [
            'status' => $status,
            'message' => $message,
            'hideSidebar' => true,
        ]);
    }

    private function sendStudioDecisionEmail($artistUser, UserDetail $userDetail, Studio $studio, bool $existingStudio): void
    {
        $allowUrl = URL::temporarySignedRoute(
            'studio.payment.decision',
            now()->addDays(30),
            ['userDetail' => $userDetail->id, 'decision' => 'allow']
        );

        $declineUrl = URL::temporarySignedRoute(
            'studio.payment.decision',
            now()->addDays(30),
            ['userDetail' => $userDetail->id, 'decision' => 'decline']
        );

        $artistName = trim(($artistUser->first_name ?? '') . ' ' . ($artistUser->last_name ?? ''));
        if ($artistName === '') {
            $artistName = $artistUser->user_name ?? $artistUser->email ?? 'Artist';
        }

        Mail::to($studio->email)->send(new StudioPaymentDecisionMail(
            $studio->name ?? 'Studio',
            $artistName,
            $allowUrl,
            $declineUrl,
            $existingStudio
        ));
    }

    private function sendStudioFirstTimeConnectEmail($artistUser, UserDetail $userDetail, Studio $studio): void
    {
        $artistName = trim(($artistUser->first_name ?? '') . ' ' . ($artistUser->last_name ?? ''));
        if ($artistName === '') {
            $artistName = $artistUser->user_name ?? $artistUser->email ?? 'Artist';
        }

        $connectUrl = URL::temporarySignedRoute(
            'studio.stripe.connect',
            now()->addDays(30),
            ['userDetail' => $userDetail->id]
        );

        Mail::to($studio->email)->send(new StudioFirstTimeConnectMail(
            $studio->name ?? 'Studio',
            $artistName,
            $connectUrl
        ));
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
