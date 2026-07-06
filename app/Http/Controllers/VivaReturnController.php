<?php

namespace App\Http\Controllers;

use App\Models\PendingVivaPayment;
use App\Services\VivaBookingConfirmationService;
use App\Services\VivaCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VivaReturnController extends Controller
{
    public function __construct(
        private readonly VivaCheckoutService $vivaCheckout,
        private readonly VivaBookingConfirmationService $confirmation,
    ) {}

    public function success(Request $request): RedirectResponse
    {
        $orderCode = $request->query('s');
        $transactionId = (string) $request->query('t', '');

        Log::info('Viva success return', [
            'order_code' => $orderCode,
            'transaction_id' => $transactionId,
        ]);

        $pending = $orderCode
            ? $this->vivaCheckout->findPendingByOrderCode($orderCode)
            : null;

        if ($pending && $transactionId !== '') {
            try {
                $this->confirmation->confirmPaid($pending, $transactionId);
            } catch (\Throwable $e) {
                Log::warning('Viva success return could not confirm immediately', [
                    'order_code' => $orderCode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($pending && $pending->fresh()->isPaid()) {
            return redirect()->to($this->vivaCheckout->successRedirectUrl($pending));
        }

        if ($pending) {
            return redirect()->to($this->vivaCheckout->failureRedirectUrl($pending))
                ->with('viva_error', 'Payment is still processing. We will confirm it shortly.');
        }

        return redirect()->route('login')
            ->with('viva_error', 'Payment is still processing. We will confirm it shortly.');
    }

    public function fail(Request $request): RedirectResponse
    {
        $orderCode = $request->query('s');
        $transactionId = (string) $request->query('t', '');

        Log::info('Viva failure return', [
            'order_code' => $orderCode,
            'transaction_id' => $transactionId,
        ]);

        $message = 'Payment was not completed. Please try again.';

        $pending = $orderCode
            ? $this->vivaCheckout->findPendingByOrderCode($orderCode)
            : null;

        if ($pending) {
            $pending->update(['status' => PendingVivaPayment::STATUS_CANCELLED]);

            return redirect()->to($this->vivaCheckout->failureRedirectUrl($pending))
                ->with('viva_error', $message);
        }

        return redirect()->route('login')->with('viva_error', $message);
    }
}
