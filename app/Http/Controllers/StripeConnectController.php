<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\AccountLink;
use Stripe\Account;
use Stripe\Exception\ApiErrorException;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Studio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class StripeConnectController extends Controller
{
    /**
     * Initialize Stripe API key
     */
    private function initializeStripe()
    {
        $stripeSecret = env('STRIPE_SECRET');
        if (!$stripeSecret) {
            throw new \Exception('Stripe secret key is not configured. Please set STRIPE_SECRET in your .env file.');
        }
        Stripe::setApiKey($stripeSecret);
    }

    /**
     * Pending Connect account id before onboarding is finished (not persisted on studios yet).
     */
    private function studioStripePendingCacheKey(int $studioId): string
    {
        return 'studio_stripe_pending:studio:'.$studioId;
    }

    /**
     * Stripe account id for studio flow: persisted id, else pending id from cache.
     */
    private function resolveStudioStripeAccountId(Studio $studio): ?string
    {
        if (! empty($studio->stripe_account_id)) {
            return $studio->stripe_account_id;
        }

        return Cache::get($this->studioStripePendingCacheKey($studio->id));
    }

    /**
     * Connect Stripe account - redirect to onboarding or login link
     */
    public function connectStripe(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Please login to connect your Stripe account.');
            }

            // Get or create user detail
            $userDetail = $user->userDetail;
            if (!$userDetail) {
                $userDetail = UserDetail::create(['user_id' => $user->id]);
            }

            // Initialize Stripe
            $this->initializeStripe();

            // Resolve Connect account id: do NOT persist to DB until onboarding succeeds (callback).
            // Keep in-progress accounts in session so abandoning Stripe does not leave a saved ID.
            $accountId = $userDetail->stripe_account_id
                ?? session('stripe_connect_pending_account_id');

            if (!$accountId) {
                $accountId = $this->createExpressAccount();
                if (!$accountId) {
                    return redirect()->back()->with('error', 'Failed to create Stripe account. Please try again.');
                }
                session(['stripe_connect_pending_account_id' => $accountId]);
            } elseif (! $userDetail->stripe_account_id) {
                session(['stripe_connect_pending_account_id' => $accountId]);
            }

            if (! $accountId) {
                return redirect()->back()->with('error', 'Something went wrong. Please try again.');
            }

            // Check if user is in onboarding process
            if ($user->on_boarding === 'no') {
                // Create account onboarding link
                $accountLink = AccountLink::create([
                    'account' => $accountId,
                    'refresh_url' => route('connect.stripe.callback', ['status' => 'refresh']),
                    'return_url' => route('connect.stripe.callback', ['status' => 'success']),
                    'type' => 'account_onboarding',
                ]);

                return redirect($accountLink->url);
            } else {
                // User already completed onboarding, create login link
                $accountLink = Account::createLoginLink($accountId);
                return redirect($accountLink->url);
            }
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error' => $e->getError()
            ]);
            return redirect()->back()->with('error', 'Stripe API error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Stripe Connect Error: ' . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);
            return redirect()->back()->with('error', 'Failed to connect Stripe account. Please try again.');
        }
    }

    /**
     * Handle Stripe callback after onboarding
     */
    public function callback(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $status = $request->get('status', 'success');

            if (!$user) {
                return redirect()->route('login')->with('error', 'Please login to continue.');
            }

            $userDetail = $user->userDetail;
            if (!$userDetail) {
                return redirect()->route('onboarding.payment')
                    ->with('error', 'Profile not found. Please try again.');
            }

            $accountId = session('stripe_connect_pending_account_id') ?? $userDetail->stripe_account_id;
            if (!$accountId) {
                return redirect()->route('onboarding.payment')
                    ->with('error', 'Stripe account not found. Please try connecting again.');
            }

            // Initialize Stripe
            $this->initializeStripe();

            // Verify account status
            $accountStatus = $this->verifyAccountStatus($accountId);

            if ($status === 'refresh') {
                // User refreshed the page, redirect back to onboarding
                return redirect()->route('onboarding.payment')
                    ->with('info', 'Please complete your Stripe account setup.');
            }

            $fullyLive = $accountStatus['charges_enabled'] && $accountStatus['payouts_enabled'];
            $formSubmitted = (bool) ($accountStatus['details_submitted'] ?? false);

            // Persist once the user has finished Stripe's hosted onboarding form, or the account is fully live.
            // (Charges/payouts can stay pending for hours while Stripe verifies — do not wait for both or the ID never saves.)
            if ($formSubmitted || $fullyLive) {
                $userDetail->stripe_account_id = $accountId;
                $userDetail->save();
                session()->forget('stripe_connect_pending_account_id');
            }

            if ($fullyLive) {
                // Account can charge and receive payouts
                if ($user->on_boarding === 'no') {
                    $user->on_boarding = 'yes';
                    $user->save();

                    $completedSteps = $userDetail->completed_steps ?? [];
                    if (! in_array(6, $completedSteps)) {
                        $completedSteps[] = 6;
                        $userDetail->completed_steps = $completedSteps;
                        $userDetail->save();
                    }
                }

                return redirect()->route('onboarding.payment')
                    ->with('success', 'Stripe account connected successfully!');
            }

            if ($formSubmitted) {
                return redirect()->route('onboarding.payment')
                    ->with('info', 'Stripe account setup is in progress. You will be notified once it\'s complete.');
            }

            return redirect()->route('onboarding.payment')
                ->with('warning', 'Stripe account setup is not complete. Please try again.');
        } catch (ApiErrorException $e) {
            Log::error('Stripe Callback API Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error' => $e->getError()
            ]);
            return redirect()->route('onboarding.payment')
                ->with('error', 'Failed to verify Stripe account status. Please try again.');
        } catch (\Exception $e) {
            Log::error('Stripe Callback Error: ' . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);
            return redirect()->route('onboarding.payment')
                ->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Create Stripe Express account (not persisted until onboarding completes in callback).
     */
    private function createExpressAccount(): ?string
    {
        try {
            $this->initializeStripe();

            $account = Account::create([
                'country' => 'AE',
                'type' => 'express',
                'capabilities' => [
                    'transfers' => [
                        'requested' => true,
                    ],
                ]
            ]);

            Log::info('Stripe Express account created (pending onboarding)', [
                'account_id' => $account->id
            ]);

            return $account->id;
        } catch (ApiErrorException $e) {
            Log::error('Stripe Account Creation API Error: ' . $e->getMessage(), [
                'error' => $e->getError()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Stripe Account Creation Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify Stripe account status
     */
    private function verifyAccountStatus($accountId)
    {
        try {
            $this->initializeStripe();
            $account = Account::retrieve($accountId);

            return [
                'charges_enabled' => $account->charges_enabled ?? false,
                'payouts_enabled' => $account->payouts_enabled ?? false,
                'details_submitted' => $account->details_submitted ?? false,
                'requirements' => $account->requirements ?? null,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Account Verification API Error: ' . $e->getMessage(), [
                'account_id' => $accountId,
                'error' => $e->getError()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Stripe Account Verification Error: ' . $e->getMessage(), [
                'account_id' => $accountId
            ]);
            throw $e;
        }
    }

    /**
     * Get account status (for API/JSON responses)
     */
    public function getAccountStatus(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $userDetail = $user->userDetail;
            if (!$userDetail || !$userDetail->stripe_account_id) {
                return response()->json([
                    'success' => false,
                    'connected' => false,
                    'message' => 'Stripe account not connected'
                ]);
            }

            $this->initializeStripe();
            $status = $this->verifyAccountStatus($userDetail->stripe_account_id);

            return response()->json([
                'success' => true,
                'connected' => true,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            Log::error('Get Account Status Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get account status'
            ], 500);
        }
    }

    /**
     * Disconnect Stripe account
     */
    public function disconnect(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $userDetail = $user->userDetail;

            $accountId = $userDetail?->stripe_account_id ?? session('stripe_connect_pending_account_id');

            if (!$accountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe account not connected'
                ], 400);
            }

            // Initialize Stripe
            $this->initializeStripe();

            try {
                // Delete the Stripe account
                $account = Account::retrieve($accountId);
                $account->delete();
            } catch (ApiErrorException $e) {
                // If account doesn't exist or already deleted, just clear the local reference
                Log::warning('Stripe account deletion error (may already be deleted): ' . $e->getMessage());
            }

            if ($userDetail) {
                $userDetail->stripe_account_id = null;
                $userDetail->save();
            }
            session()->forget('stripe_connect_pending_account_id');

            Log::info('Stripe account disconnected', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stripe account disconnected successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe Disconnect Error: ' . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect Stripe account. Please try again.'
            ], 500);
        }
    }

    /**
     * Public signed link: connect Stripe for a studio (first-time flow).
     */
    public function studioConnect(Request $request, UserDetail $userDetail)
    {
        try {
            if (!$request->hasValidSignature()) {
                return view('studio.payment-decision-result', [
                    'status' => 'error',
                    'message' => 'Invalid or expired studio connect link.',
                ]);
            }

            if ($userDetail->payment_type !== 'studio_account' || empty($userDetail->studio_id)) {
                return view('studio.payment-decision-result', [
                    'status' => 'error',
                    'message' => 'This studio payment request is no longer active.',
                ]);
            }

            $studio = Studio::find($userDetail->studio_id);
            if (!$studio) {
                return view('studio.payment-decision-result', [
                    'status' => 'error',
                    'message' => 'Studio not found.',
                ]);
            }

            $this->initializeStripe();

            $accountId = $this->resolveStudioStripeAccountId($studio);
            if (! $accountId) {
                $account = Account::create([
                    'country' => 'AE',
                    'type' => 'express',
                    'capabilities' => [
                        'transfers' => ['requested' => true],
                    ],
                    'email' => $studio->email,
                ]);
                $accountId = $account->id;
                Cache::put(
                    $this->studioStripePendingCacheKey($studio->id),
                    $accountId,
                    now()->addDays(7)
                );
            }

            $callbackParams = ['userDetail' => $userDetail->id];
            $refreshUrl = URL::temporarySignedRoute('studio.stripe.callback', now()->addDays(30), array_merge($callbackParams, ['status' => 'refresh']));
            $returnUrl = URL::temporarySignedRoute('studio.stripe.callback', now()->addDays(30), array_merge($callbackParams, ['status' => 'success']));

            $accountLink = AccountLink::create([
                'account' => $accountId,
                'refresh_url' => $refreshUrl,
                'return_url' => $returnUrl,
                'type' => 'account_onboarding',
            ]);

            return redirect($accountLink->url);
        } catch (ApiErrorException $e) {
            Log::error('Studio Stripe connect API error: '.$e->getMessage(), [
                'user_detail_id' => $userDetail->id,
            ]);
            return view('studio.payment-decision-result', [
                'status' => 'error',
                'message' => 'Stripe error while preparing studio onboarding.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Studio Stripe connect error: '.$e->getMessage(), [
                'user_detail_id' => $userDetail->id,
            ]);
            return view('studio.payment-decision-result', [
                'status' => 'error',
                'message' => 'Could not start studio Stripe onboarding.',
            ]);
        }
    }

    /**
     * Public signed callback: finalize studio Stripe onboarding.
     */
    public function studioCallback(Request $request, UserDetail $userDetail)
    {
        try {
            if (!$request->hasValidSignature()) {
                return view('studio.payment-decision-result', [
                    'status' => 'error',
                    'message' => 'Invalid or expired callback link.',
                ]);
            }

            if ($userDetail->payment_type !== 'studio_account' || empty($userDetail->studio_id)) {
                return view('studio.payment-decision-result', [
                    'status' => 'error',
                    'message' => 'This studio payment request is no longer active.',
                ]);
            }

            $studio = Studio::find($userDetail->studio_id);
            if (! $studio) {
                return view('studio.payment-decision-result', [
                    'status' => 'error',
                    'message' => 'Studio not found.',
                ]);
            }

            $accountId = $this->resolveStudioStripeAccountId($studio);
            if (! $accountId) {
                return view('studio.payment-decision-result', [
                    'status' => 'error',
                    'message' => 'Studio Stripe account was not found. Please open the connect link from your email again.',
                ]);
            }

            $this->initializeStripe();
            $status = $request->get('status', 'success');
            $accountStatus = $this->verifyAccountStatus($accountId);

            if ($status === 'refresh') {
                $refreshUrl = URL::temporarySignedRoute('studio.stripe.callback', now()->addDays(30), ['userDetail' => $userDetail->id, 'status' => 'refresh']);
                $returnUrl = URL::temporarySignedRoute('studio.stripe.callback', now()->addDays(30), ['userDetail' => $userDetail->id, 'status' => 'success']);
                $accountLink = AccountLink::create([
                    'account' => $accountId,
                    'refresh_url' => $refreshUrl,
                    'return_url' => $returnUrl,
                    'type' => 'account_onboarding',
                ]);
                return redirect($accountLink->url);
            }

            $fullyLive = ($accountStatus['charges_enabled'] ?? false) && ($accountStatus['payouts_enabled'] ?? false);
            $formSubmitted = (bool) ($accountStatus['details_submitted'] ?? false);

            if ($formSubmitted || $fullyLive) {
                $studio->stripe_account_id = $accountId;
                $studio->save();

                $userDetail->stripe_account_id = $accountId;
                $userDetail->payment_status = 'approved';
                $userDetail->save();

                Cache::forget($this->studioStripePendingCacheKey($studio->id));

                return view('studio.payment-decision-result', [
                    'status' => 'approved',
                    'message' => 'Stripe connected. Artist payment request is approved.',
                ]);
            }

            return view('studio.payment-decision-result', [
                'status' => 'error',
                'message' => 'Stripe onboarding not completed yet. Please complete all steps.',
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Studio Stripe callback API error: '.$e->getMessage(), [
                'user_detail_id' => $userDetail->id,
            ]);
            return view('studio.payment-decision-result', [
                'status' => 'error',
                'message' => 'Stripe verification failed. Please try again.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Studio Stripe callback error: '.$e->getMessage(), [
                'user_detail_id' => $userDetail->id,
            ]);
            return view('studio.payment-decision-result', [
                'status' => 'error',
                'message' => 'An unexpected error occurred during studio Stripe callback.',
            ]);
        }
    }
}
