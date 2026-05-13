<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserBankDetail;
use App\Models\Studio;
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
                'payment_type' => ['required', 'in:artist_account,studio_account'],
            ];

            $messages = [
                'payment_type.required' => 'Please select a payment type.',
                'payment_type.in' => 'Invalid payment type selected.',
            ];

            $paymentType = $request->payment_type;
            if ($paymentType === 'artist_account') {
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
            } elseif ($paymentType === 'studio_account') {
                $rules['studio_email'] = ['required', 'email', 'max:255'];
                $messages['studio_email.required'] = 'Studio email is required.';
                $messages['studio_email.email'] = 'Please enter a valid email address.';
            }

            $validated = $request->validate($rules, $messages);
            $studio = null;

            $userDetail->payment_type = $validated['payment_type'];

            if ($paymentType === 'artist_account') {
                $userDetail->stripe_account_id = null;
                $userDetail->studio_id = null;
                $userDetail->payment_status = 'approved';
                $userDetail->currency = strtoupper($validated['currency']);
                $this->upsertUserBankDetails($user, $validated);
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
                'payment_type' => ['required', 'in:artist_account,studio_account'],
            ];

            $messages = [
                'payment_type.required' => 'Please select a payment type.',
                'payment_type.in' => 'Invalid payment type selected.',
            ];

            // Conditional validation based on payment_type
            $paymentType = $request->payment_type;
            
            if ($paymentType === 'artist_account') {
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
            } elseif ($paymentType === 'studio_account') {
                // Studio account: Studio email is required
                $rules['studio_email'] = ['required', 'email', 'max:255'];
                $messages['studio_email.required'] = 'Studio email is required.';
                $messages['studio_email.email'] = 'Please enter a valid email address.';
            }

            $validated = $request->validate($rules, $messages);
            $studio = null;

            // Always set payment_type and completed_steps
            $userDetail->payment_type = $validated['payment_type'];
            $userDetail->completed_steps = array_unique(array_merge($userDetail->completed_steps ?? [], [6]));

            if ($paymentType === 'artist_account') {
                $userDetail->stripe_account_id = null;
                $userDetail->studio_id = null;
                $userDetail->payment_status = 'approved';
                $userDetail->currency = strtoupper($validated['currency']);
                $this->upsertUserBankDetails($user, $validated);
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

        $storeUrl = URL::temporarySignedRoute(
            'studio.payout-info.store',
            now()->addDays(14),
            ['userDetail' => $userDetail->id]
        );

        return view('studio.payout-form', [
            'userDetail' => $userDetail,
            'studio' => $studio,
            'storeUrl' => $storeUrl,
        ]);
    }

    /**
     * Save studio bank details on the studio record and mark the artist payout request approved.
     */
    public function saveStudioPayoutForm(Request $request, UserDetail $userDetail)
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

        $validator = Validator::make(
            $request->all(),
            [
                'studio_display_name' => ['nullable', 'string', 'max:255'],
                'account_holder_name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:255',
                    'regex:/^[\p{L}\p{M}\s\'\-\.]+$/u',
                ],
                'bank_name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:255',
                    'regex:/^[\p{L}\p{N}\p{M}\s\-\.\,\&]+$/u',
                ],
                'account_number' => [
                    'required',
                    'string',
                    'max:64',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        $norm = $this->normalizedPayoutAccountNumber((string) $value);
                        if ($norm === '') {
                            $fail('Enter a valid account number or IBAN.');

                            return;
                        }
                        if (strlen($norm) > 34) {
                            $fail('Account number or IBAN must not exceed 34 characters (after removing spaces and hyphens).');

                            return;
                        }
                        if ($this->looksLikeIban($norm)) {
                            if (! $this->isValidIban($norm)) {
                                $fail('This IBAN is not valid. Check the country code, length, and digits.');
                            }

                            return;
                        }
                        if (strlen($norm) < 4) {
                            $fail('Account number must be at least 4 characters (after removing spaces).');

                            return;
                        }
                        if (! preg_match('/^[A-Z0-9]+$/', $norm)) {
                            $fail('Account number may only contain letters and digits (or use a valid IBAN).');

                            return;
                        }
                    },
                ],
                'swift_bic' => [
                    'required',
                    'string',
                    'max:15',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        $v = strtoupper(preg_replace('/\s+/', '', (string) $value));
                        if (! preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $v)) {
                            $fail('Enter a valid 8- or 11-character SWIFT/BIC (letters and numbers only, e.g. CHASUS33 or DEUTDEFF500).');
                        }
                    },
                ],
                'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
            ],
            [
                'account_holder_name.required' => 'Account holder name is required.',
                'account_holder_name.min' => 'Account holder name must be at least 2 characters.',
                'account_holder_name.regex' => 'Account holder name may only include letters, spaces, hyphens, apostrophes, and periods.',
                'bank_name.required' => 'Bank name is required.',
                'bank_name.min' => 'Bank name must be at least 2 characters.',
                'bank_name.regex' => 'Bank name contains invalid characters.',
                'account_number.required' => 'Account number or IBAN is required.',
                'account_number.max' => 'Account number or IBAN is too long. Remove extra spaces and try again.',
                'swift_bic.required' => 'SWIFT/BIC is required.',
                'swift_bic.max' => 'SWIFT/BIC must be at most 11 characters after removing spaces.',
                'currency.required' => 'Please select a currency.',
                'currency.size' => 'Please select a valid 3-letter currency.',
                'currency.regex' => 'Currency must be a 3-letter ISO code.',
            ]
        );

        if ($validator->fails()) {
            return redirect()->to(URL::temporarySignedRoute(
                'studio.payout-info.show',
                now()->addDays(30),
                ['userDetail' => $userDetail->id]
            ))->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $currency = strtoupper(trim($validated['currency']));
        $holder = trim($validated['account_holder_name']);
        $bankName = trim($validated['bank_name']);
        $accountNumber = $this->normalizedPayoutAccountNumber((string) $validated['account_number']);
        $swift = strtoupper(preg_replace('/\s+/', '', (string) $validated['swift_bic']));

        $studio->fill([
            'account_holder_name' => $holder,
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'swift_bic' => $swift,
            'bank_currency' => $currency,
        ]);
        if (! empty($validated['studio_display_name'])) {
            $studio->name = trim($validated['studio_display_name']);
        }
        $studio->save();

        $userDetail->payment_status = 'approved';
        $userDetail->currency = $currency;
        $userDetail->save();

        return view('studio.payout-form-result', [
            'success' => true,
            'message' => 'Thank you. Payout details have been saved successfully.',
        ]);
    }

    /**
     * Signed link: studio approves this artist receiving payouts through the studio (no artist user_bank_details changes).
     */
    public function approveStudioArtistBankLink(Request $request, UserDetail $userDetail)
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
        if (! $studio || ! $studio->hasStoredBankDetails()) {
            return view('studio.payout-form-result', [
                'success' => false,
                'title' => 'Details unavailable',
                'message' => 'Your studio does not have complete bank details on file anymore. Please use the secure bank form link from the latest email from us.',
            ]);
        }

        $currency = strtoupper(trim((string) $studio->bank_currency));

        $userDetail->payment_status = 'approved';
        $userDetail->currency = $currency;
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

        $userDetail->payment_status = 'rejected';
        $userDetail->save();

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
            'rejected' => 'Your studio payout setup was not completed. Please contact your studio or update your payment method in settings.',
            default => 'We are waiting for your studio to respond to the email we sent them (bank form, or approve/decline if they already have details on file).',
        };

        return view('studio.payment-request-status', [
            'status' => $status,
            'message' => $message,
            'hideSidebar' => true,
        ]);
    }

    private function sendStudioPayoutInfoRequestEmail(User $artistUser, UserDetail $userDetail, Studio $studio): void
    {
        $studio->refresh();

        $artistName = trim(($artistUser->first_name ?? '').' '.($artistUser->last_name ?? ''));
        if ($artistName === '') {
            $artistName = $artistUser->user_name ?? $artistUser->email ?? 'Artist';
        }

        $showApproveDecline = $studio->hasStoredBankDetails();

        $formUrl = URL::temporarySignedRoute(
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
                $declineUrl
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send studio payout info request email', [
                'studio_id' => $studio->id,
                'user_detail_id' => $userDetail->id,
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
