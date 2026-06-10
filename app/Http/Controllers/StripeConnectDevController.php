<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class StripeConnectDevController extends Controller
{
    public function showDeleteForm()
    {
        $this->ensureDeleteToolEnabled();

        return view('stripe.delete-account', [
            'requiresToken' => $this->requiresDevToken(),
            'stripeConfigured' => app(StripeConnectService::class)->isConfigured(),
        ]);
    }

    public function deleteAccount(Request $request, StripeConnectService $stripeConnect)
    {
        $this->ensureDeleteToolEnabled();

        if ($this->requiresDevToken() && ! $this->devTokenValid($request)) {
            return back()
                ->withInput()
                ->withErrors(['dev_token' => 'Invalid dev token.']);
        }

        if (! $stripeConnect->isConfigured()) {
            return back()
                ->withInput()
                ->withErrors(['stripe_account_id' => 'Stripe is not configured on this server.']);
        }

        $validated = $request->validate([
            'stripe_account_id' => ['required', 'string', 'regex:/^acct_[a-zA-Z0-9]+$/'],
            'dev_token' => ['nullable', 'string', 'max:255'],
            'clear_local' => ['nullable', 'boolean'],
        ], [
            'stripe_account_id.required' => 'Please enter a Stripe account ID.',
            'stripe_account_id.regex' => 'Account ID must look like acct_1234567890.',
        ]);

        $accountId = $validated['stripe_account_id'];

        try {
            $result = $stripeConnect->deleteConnectedAccount($accountId);

            $clearedUsers = 0;
            if ($request->boolean('clear_local')) {
                $clearedUsers = UserDetail::query()
                    ->where('stripe_account_id', $accountId)
                    ->update([
                        'stripe_account_id' => null,
                        'payout_bank_country' => null,
                    ]);
            }

            Log::info('Stripe connected account deleted via dev tool', [
                'stripe_account_id' => $accountId,
                'cleared_local_users' => $clearedUsers,
                'ip' => $request->ip(),
            ]);

            return back()->with('success', sprintf(
                'Deleted Stripe account %s.%s',
                $result['id'],
                $clearedUsers > 0 ? " Cleared local link for {$clearedUsers} user(s)." : ''
            ));
        } catch (ApiErrorException $e) {
            return back()
                ->withInput()
                ->withErrors(['stripe_account_id' => $e->getMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['stripe_account_id' => $e->getMessage()]);
        }
    }

    private function ensureDeleteToolEnabled(): void
    {
        if (app()->environment('production') && ! config('services.stripe.connect.dev_delete_token')) {
            abort(404);
        }
    }

    private function requiresDevToken(): bool
    {
        return (bool) config('services.stripe.connect.dev_delete_token');
    }

    private function devTokenValid(Request $request): bool
    {
        $expected = (string) config('services.stripe.connect.dev_delete_token');

        return hash_equals($expected, (string) $request->input('dev_token', ''));
    }
}
