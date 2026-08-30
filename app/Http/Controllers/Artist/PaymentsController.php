<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Services\ArtistPaymentsService;
use App\Services\ArtistPayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class PaymentsController extends Controller
{
    public function __construct(
        private ArtistPaymentsService $paymentsService,
        private ArtistPayoutService $payoutService,
    ) {}

    public function index(Request $request): View
    {
        $payload = $this->paymentsService->buildForArtist((int) $request->user()->id);

        return view('artist.payments.index', [
            'payments' => $payload['payments'],
            'stats' => $payload['stats'],
        ]);
    }

    public function requestPayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ], [
            'amount.required' => 'Enter an amount to withdraw.',
            'amount.min' => 'Enter an amount greater than zero.',
        ]);

        $user = $request->user();
        $userDetail = $user->userDetail;
        if (! $userDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Complete payout setup before requesting a payout.',
            ], 422);
        }

        if (($userDetail->payout_mode ?? 'manual') === 'automatic') {
            return response()->json([
                'success' => false,
                'message' => 'Your payout mode is automatic. Switch to manual to request payouts.',
            ], 422);
        }

        try {
            $result = $this->payoutService->requestManualPayout(
                $userDetail,
                (float) $validated['amount'],
            );
        } catch (ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe could not process this payout. Please try again or contact support.',
            ], 422);
        }

        $symbol = '€';
        $formatted = $symbol.number_format($result['amount'], 2);

        return response()->json([
            'success' => true,
            'message' => 'Payout of '.$formatted.' sent to your connected account.',
            'amount' => $result['amount'],
            'currency' => $result['currency'],
            'bookings' => $result['bookings'],
            'available_balance' => $this->payoutService->availableBalanceForArtist((int) $user->id, $userDetail->fresh()),
        ]);
    }
}
