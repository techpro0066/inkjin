<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class PersonalPageController extends Controller
{
    public function index(StripeConnectService $stripeConnect)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail;
        $stripeStatus = null;
        $showStripeIdentity = $this->shouldShowStripeIdentity($userDetail, $stripeConnect);

        if ($showStripeIdentity && $userDetail?->stripe_account_id && $stripeConnect->isConfigured()) {
            try {
                $stripeStatus = $stripeConnect->getOnboardingStatus($userDetail->stripe_account_id);
            } catch (ApiErrorException $e) {
                Log::warning('Could not load Stripe identity status for personal page', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('artist.personal-page.index', [
            'user' => $user,
            'userDetail' => $userDetail,
            'stripeConnectConfigured' => $stripeConnect->isConfigured(),
            'stripePublishableKey' => config('services.stripe.key'),
            'stripeConnectLocale' => $stripeConnect->connectLocale(),
            'showStripeIdentity' => $showStripeIdentity,
            'stripeStatus' => $stripeStatus,
            'stripeIdentityComplete' => $stripeStatus
                ? ! ($stripeStatus['identity_verification_due'] ?? true)
                : false,
        ]);
    }

    public function stripeIdentitySession(Request $request, StripeConnectService $stripeConnect)
    {
        $user = $request->user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        if (! $this->shouldShowStripeIdentity($userDetail, $stripeConnect)) {
            return response()->json([
                'success' => false,
                'message' => 'Identity verification is not available for this account.',
            ], 422);
        }

        if (! $stripeConnect->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe is not configured.',
            ], 500);
        }

        try {
            $session = $stripeConnect->createIdentityVerificationSession($user, $userDetail);

            return response()->json([
                'success' => true,
                'client_secret' => $session['client_secret'],
                'account_id' => $session['account_id'],
                'collection_options' => $session['collection_options'],
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe identity session creation failed on personal page', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not start identity verification: '.$e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Stripe identity session creation failed on personal page', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Could not start identity verification.',
            ], 422);
        }
    }

    public function stripeIdentityStatus(Request $request, StripeConnectService $stripeConnect)
    {
        $userDetail = $request->user()->userDetail;

        if (! $userDetail?->stripe_account_id) {
            return response()->json([
                'success' => true,
                'identity_verification_due' => true,
                'complete' => false,
            ]);
        }

        if (! $stripeConnect->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe is not configured.',
            ], 500);
        }

        try {
            $status = $stripeConnect->getOnboardingStatus($userDetail->stripe_account_id);

            return response()->json([
                'success' => true,
                ...$status,
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not load verification status.',
            ], 422);
        }
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $userDetail = $user->userDetail ?? UserDetail::create(['user_id' => $user->id]);

        $validated = $request->validate([
            'personal_page_background_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'personal_page_color' => ['required', 'string', 'max:50'],
            'personal_page_tagline' => ['required', 'string', 'max:255'],
            'personal_page_description' => ['required', 'string', 'max:500'],
            'personal_page_name_alias' => ['required', 'in:full,username,both'],
        ]);

        if (! $request->hasFile('personal_page_background_image') && empty($userDetail->personal_page_background_image)) {
            throw ValidationException::withMessages([
                'personal_page_background_image' => ['Background image is required.'],
            ]);
        }

        $backgroundPath = $userDetail->personal_page_background_image;
        if ($request->hasFile('personal_page_background_image')) {
            if ($backgroundPath && file_exists(public_path($backgroundPath))) {
                File::delete(public_path($backgroundPath));
            }

            $file = $request->file('personal_page_background_image');
            $filename = time().'_'.uniqid().'.'.strtolower($file->getClientOriginalExtension());
            $destination = public_path('uploads/personal-pages');
            if (! File::exists($destination)) {
                File::makeDirectory($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $backgroundPath = 'uploads/personal-pages/'.$filename;
        }

        $userDetail->update([
            'personal_page_background_image' => $backgroundPath,
            'personal_page_color' => $validated['personal_page_color'] ?? null,
            'personal_page_tagline' => trim((string) ($validated['personal_page_tagline'] ?? '')) ?: null,
            'personal_page_description' => trim((string) ($validated['personal_page_description'] ?? '')) ?: null,
            'personal_page_name_alias' => $validated['personal_page_name_alias'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Personal page updated successfully.',
            'banner' => $backgroundPath ? asset($backgroundPath) : null,
        ]);
    }

    private function shouldShowStripeIdentity(?UserDetail $userDetail, StripeConnectService $stripeConnect): bool
    {
        if (! $stripeConnect->isConfigured()) {
            return false;
        }

        if (! $userDetail) {
            return false;
        }

        if ($userDetail->payout_waiting_list_country) {
            return false;
        }

        $paymentType = $userDetail->payment_type ?? 'artist_account';

        return $paymentType === 'artist_account';
    }
}
