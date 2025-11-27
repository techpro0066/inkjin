<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use App\Models\Question;
use App\Models\UserQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class OnboardingController extends Controller
{
    /**
     * Display the onboarding page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // If user has already completed onboarding, redirect to dashboard
        if ($user->on_boarding === 'yes') {
            return redirect()->route('dashboard');
        }

        // Get or create user detail
        $userDetail = $user->userDetail;
        if (!$userDetail) {
            $userDetail = new UserDetail();
            $userDetail->user_id = $user->id;
            $userDetail->current_step = 1;
            $userDetail->completed_steps = [];
        }
        
        // Get current step from user detail or default to 1
        $currentStep = $userDetail->current_step ?? 1;
        
        // Get completed steps
        $completedSteps = $userDetail->completed_steps ?? [];

        return view('onboarding.index', [
            'userDetail' => $userDetail,
            'currentStep' => $currentStep,
            'completedSteps' => $completedSteps,
        ]);
    }

    /**
     * Save step 1: Complete Profile
     */
    public function saveStep1(Request $request)
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
                'user_name' => [
                    'required', 
                    'string', 
                    'max:255',
                    'unique:user_details,user_name,' . ($userDetail ? $userDetail->id : 'NULL') . ',id'
                ],
                'mobile_number' => [
                    'required', 
                    'string', 
                    'max:20',
                    'unique:user_details,mobile_number,' . ($userDetail ? $userDetail->id : 'NULL') . ',id'
                ],
                'country' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
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

            $userDetail->update([
                'avatar' => $avatarPath,
                'user_name' => $validated['user_name'],
                'mobile_number' => $validated['mobile_number'],
                'country' => $validated['country'],
                'city' => $validated['city'],
                'current_step' => 2,
                'completed_steps' => array_unique(array_merge($userDetail->completed_steps ?? [], [1])),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Step 1 saved successfully',
                'nextStep' => 2,
                'avatar' => $avatarPath ? asset($avatarPath) : null,
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
     * Save step 2: Studio Information
     */
    public function saveStep2(Request $request)
    {
        try {
            $validated = $request->validate([
                'studio_name' => ['required', 'string', 'max:255'],
                'studio_address' => ['required', 'string'],
                'google_maps_link' => ['nullable', 'url', 'max:500'],
            ]);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $userDetail->update([
                'studio_name' => $validated['studio_name'],
                'studio_address' => $validated['studio_address'],
                'google_maps_link' => $validated['google_maps_link'] ?? null,
                'current_step' => 3,
                'completed_steps' => array_unique(array_merge($userDetail->completed_steps ?? [], [2])),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Step 2 saved successfully',
                'nextStep' => 3,
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
     * Save step 3: Calendar Connection or Preferences (from settings page)
     */
    public function saveStep3(Request $request)
    {
        try {
            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            // Check if this is a preferences update from settings page
            if ($request->has('currency') || $request->has('session_buffer_period')) {
                // This is a preferences update from settings page
                $validated = $request->validate([
                    'currency' => ['required'],
                    'timezone' => ['required'],
                    'date_time_format' => ['required'],
                    'minimum_deposit_amount' => ['required', 'numeric', 'min:0'],
                    'minimum_deposit_type' => ['required'],
                    'cancellation_window' => ['required'],
                    'reschedule_times' => ['required'],
                    'session_buffer_period' => ['required', 'integer', 'min:0'],
                    'require_consultation' => ['nullable', 'boolean'],
                ]);

                $minimumDepositAmount = (float) $validated['minimum_deposit_amount'];

                $userDetail->update([
                    'currency' => $validated['currency'],
                    'timezone' => $validated['timezone'],
                    'date_time_format' => $validated['date_time_format'],
                    'minimum_deposit_amount' => $minimumDepositAmount,
                    'minimum_deposit_type' => $validated['minimum_deposit_type'],
                    'cancellation_window' => $validated['cancellation_window'],
                    'reschedule_times' => $validated['reschedule_times'],
                    'session_buffer_period' => (int) $validated['session_buffer_period'],
                    'require_consultation' => $request->has('require_consultation') && $request->require_consultation == '1',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Preferences updated successfully',
                ]);
            }

            // Otherwise, this is calendar connection step
            $request->validate([
                'google_calendar_connected' => ['nullable', 'string'],
            ]);

            // Check if calendar is actually connected (not just the checkbox value)
            $calendarConnected = !empty($userDetail->google_calendar_token);
            
            $completedSteps = $userDetail->completed_steps ?? [];
            if (!in_array(3, $completedSteps)) {
                $completedSteps[] = 3;
            }

            $userDetail->update([
                'current_step' => 4,
                'completed_steps' => $completedSteps,
            ]);

            $message = $calendarConnected 
                ? 'Step 3 saved successfully. Google Calendar is connected.'
                : 'Step 3 saved successfully. You can connect Google Calendar later.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'nextStep' => 4,
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
     * Save step 4: Preferences
     */
    public function saveStep4(Request $request)
    {
        try {
            $validated = $request->validate([
                'currency' => ['required'],
                'timezone' => ['required'],
                'date_time_format' => ['required'],
                'minimum_deposit_amount' => ['required', 'numeric', 'min:0'],
                'minimum_deposit_type' => ['required'],
                'cancellation_window' => ['required'],
                'reschedule_times' => ['required'],
                'session_buffer_period' => ['required', 'integer', 'min:0'],
                'require_consultation' => ['nullable', 'boolean'],
            ]);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            // Convert minimum_deposit_amount to decimal
            $minimumDepositAmount = (float) $validated['minimum_deposit_amount'];

            // Only update onboarding progress if user is still in onboarding process
            $updateData = [
                'currency' => $validated['currency'],
                'timezone' => $validated['timezone'],
                'date_time_format' => $validated['date_time_format'],
                'minimum_deposit_amount' => $minimumDepositAmount,
                'minimum_deposit_type' => $validated['minimum_deposit_type'],
                'cancellation_window' => $validated['cancellation_window'],
                'reschedule_times' => $validated['reschedule_times'],
                'session_buffer_period' => (int) $validated['session_buffer_period'],
                'require_consultation' => $request->has('require_consultation') && $request->require_consultation == '1',
            ];
            
            // Only update onboarding progress if user hasn't completed onboarding
            if ($user->on_boarding !== 'yes') {
                $updateData['current_step'] = 5;
                $updateData['completed_steps'] = array_unique(array_merge($userDetail->completed_steps ?? [], [4]));
            }
            
            $userDetail->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Step 4 saved successfully',
                'nextStep' => 5,
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
     * Save step 5: Payments
     */
    public function saveStep5(Request $request)
    {
        try {
            $request->validate([
                'stripe_account_id' => ['nullable', 'string', 'max:255'],
            ]);

            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            $userDetail->update([
                'stripe_account_id' => $request->stripe_account_id ?? null,
                'completed_steps' => array_unique(array_merge($userDetail->completed_steps ?? [], [5])),
            ]);

            // Mark onboarding as complete
            $user->update(['on_boarding' => 'yes']);

            // Assign all default questions to the user
            $defaultQuestions = Question::where('status', 'active')->get();
            foreach ($defaultQuestions as $defaultQuestion) {
                UserQuestion::create([
                    'user_id' => $user->id,
                    'question' => $defaultQuestion->question,
                    'type' => $defaultQuestion->type ?? 'free',
                    'options' => $defaultQuestion->options,
                    'max_images' => $defaultQuestion->max_images,
                    'status' => 'active',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Onboarding completed successfully!',
                'redirect' => route('dashboard'),
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

    /**
     * Update profile (for settings page)
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

            // Make avatar optional if user already has one
            $avatarRule = $userDetail->avatar 
                ? ['nullable'] 
                : ['required'];
            
            $user = $request->user();
            $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);
            
            $validated = $request->validate([
                'avatar' => array_merge($avatarRule, ['image', 'mimes:jpg,jpeg,png,heif,heic', 'max:2048']),
                'user_name' => [
                    'required', 
                    'string', 
                    'max:255',
                    'unique:user_details,user_name,' . $userDetail->id . ',id'
                ],
                'mobile_number' => [
                    'required', 
                    'string', 
                    'max:20',
                    'unique:user_details,mobile_number,' . $userDetail->id . ',id'
                ],
                'country' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
            ]);

            // Handle avatar upload using helper function
            $avatarPath = $userDetail->avatar;
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($userDetail->avatar && file_exists(public_path($userDetail->avatar))) {
                    File::delete(public_path($userDetail->avatar));
                }
                
                $avatarPath = $this->imageUploader($request->file('avatar'), 'avatars');
            }

            $userDetail->update([
                'avatar' => $avatarPath,
                'user_name' => $validated['user_name'],
                'mobile_number' => $validated['mobile_number'],
                'country' => $validated['country'],
                'city' => $validated['city'],
            ]);

            return redirect()->route('settings.profile')
                ->with('success', 'Profile updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'An error occurred: ' . $e->getMessage())
                ->withInput();
        }
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
