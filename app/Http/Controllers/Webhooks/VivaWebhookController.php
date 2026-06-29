<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\VivaBookingConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class VivaWebhookController extends Controller
{
    public function __construct(
        private readonly VivaBookingConfirmationService $confirmation,
    ) {}

    public function handle(Request $request): JsonResponse|Response
    {
        if ($request->isMethod('get')) {
            return response()->json([
                'Key' => config('services.viva.webhook_key'),
            ]);
        }

        $payload = $request->all();
        Log::info('Viva webhook received', ['payload' => $payload]);

        try {
            $this->confirmation->confirmFromWebhookPayload($payload);
        } catch (Throwable $e) {
            Log::error('Viva webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }

        return response('', 200);
    }
}
