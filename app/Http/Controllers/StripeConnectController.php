<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\AccountLink;
use Stripe\Account;
use Stripe\Exception\ApiErrorException;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

            // Create account if it doesn't exist
            if (!$userDetail->stripe_account_id) {
                $accountCreated = $this->connectAccount($userDetail);
                if (!$accountCreated) {
                    return redirect()->back()->with('error', 'Failed to create Stripe account. Please try again.');
                }
                // Refresh userDetail to get the new stripe_account_id
                $userDetail->refresh();
            }

            // Check if account exists
            if (!$userDetail->stripe_account_id) {
                return redirect()->back()->with('error', 'Something went wrong. Please try again.');
            }

            // Check if user is in onboarding process
            if ($user->on_boarding === 'no') {
                // Create account onboarding link
                $accountLink = AccountLink::create([
                    'account' => $userDetail->stripe_account_id,
                    'refresh_url' => route('connect.stripe.callback', ['status' => 'refresh']),
                    'return_url' => route('connect.stripe.callback', ['status' => 'success']),
                    'type' => 'account_onboarding',
                ]);

                return redirect($accountLink->url);
            } else {
                // User already completed onboarding, create login link
                $accountLink = Account::createLoginLink($userDetail->stripe_account_id);
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
            if (!$userDetail || !$userDetail->stripe_account_id) {
                return redirect()->route('onboarding.index')
                    ->with('error', 'Stripe account not found. Please try connecting again.');
            }

            // Initialize Stripe
            $this->initializeStripe();

            // Verify account status
            $accountStatus = $this->verifyAccountStatus($userDetail->stripe_account_id);

            if ($status === 'refresh') {
                // User refreshed the page, redirect back to onboarding
                return redirect()->route('onboarding.index')
                    ->with('info', 'Please complete your Stripe account setup.');
            }

            // Check if account is fully onboarded
            if ($accountStatus['charges_enabled'] && $accountStatus['payouts_enabled']) {
                // Account is fully set up
                if ($user->on_boarding === 'no') {
                    // Mark onboarding as complete if not already done
                    $user->on_boarding = 'yes';
                    $user->save();
                    
                    // Update userDetail completed steps
                    $completedSteps = $userDetail->completed_steps ?? [];
                    if (!in_array(4, $completedSteps)) {
                        $completedSteps[] = 4;
                        $userDetail->completed_steps = $completedSteps;
                        $userDetail->save();
                    }
                }

                return redirect()->route('onboarding.index')
                    ->with('success', 'Stripe account connected successfully!');
            } elseif ($accountStatus['details_submitted']) {
                // Account setup in progress
                return redirect()->route('onboarding.index')
                    ->with('info', 'Stripe account setup is in progress. You will be notified once it\'s complete.');
            } else {
                // Account setup not complete
                return redirect()->route('onboarding.index')
                    ->with('warning', 'Stripe account setup is not complete. Please try again.');
            }
        } catch (ApiErrorException $e) {
            Log::error('Stripe Callback API Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error' => $e->getError()
            ]);
            return redirect()->route('onboarding.index')
                ->with('error', 'Failed to verify Stripe account status. Please try again.');
        } catch (\Exception $e) {
            Log::error('Stripe Callback Error: ' . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);
            return redirect()->route('onboarding.index')
                ->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Create Stripe Express account
     */
    private function connectAccount($userDetail, $by_seller = false)
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

            $userDetail->stripe_account_id = $account->id;
            $userDetail->save();

            Log::info('Stripe account created successfully', [
                'user_id' => $userDetail->user_id,
                'account_id' => $account->id
            ]);

            return true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe Account Creation API Error: ' . $e->getMessage(), [
                'user_id' => $userDetail->user_id ?? null,
                'error' => $e->getError()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Stripe Account Creation Error: ' . $e->getMessage(), [
                'user_id' => $userDetail->user_id ?? null
            ]);
            return false;
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
            
            if (!$userDetail || !$userDetail->stripe_account_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe account not connected'
                ], 400);
            }

            // Initialize Stripe
            $this->initializeStripe();

            try {
                // Delete the Stripe account
                $account = Account::retrieve($userDetail->stripe_account_id);
                $account->delete();
            } catch (ApiErrorException $e) {
                // If account doesn't exist or already deleted, just clear the local reference
                Log::warning('Stripe account deletion error (may already be deleted): ' . $e->getMessage());
            }

            // Clear stripe_account_id from user detail
            $userDetail->stripe_account_id = null;
            $userDetail->save();

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
}
