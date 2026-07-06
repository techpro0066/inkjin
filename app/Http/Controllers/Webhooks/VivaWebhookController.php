<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\VivaBookingConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class VivaWebhookController extends Controller
{
    public function __construct(
        private readonly VivaBookingConfirmationService $confirmation,
    ) {}

    /**
     * Viva webhook URL verification (Settings → API Access → Webhooks → Verify).
     *
     * @see https://developer.viva.com/webhooks-for-payments/setting-up-webhooks/
     */
    public function verify(): JsonResponse
    {
        $key = (string) config('services.viva.webhook_key');

        if ($key === '') {
            return response()->json([
                'message' => 'VIVA_WEBHOOK_KEY is not configured.',
            ], 503);
        }

        return response()->json([
            'Key' => $key,
        ]);
    }

    public function handle(Request $request): Response
    {
        $payload = $request->all();

        Log::info('Viva webhook received', ['payload' => $payload]);

        try {
            $this->confirmation->confirmFromWebhookPayload($payload);
        } catch (\Throwable $e) {
            Log::error('Viva webhook processing failed', [
                'error' => $e->getMessage(),
                'order_code' => $payload['OrderCode'] ?? null,
            ]);
        }

        // Viva retries until it receives 2xx.
        return response('', 200);
    }
}
