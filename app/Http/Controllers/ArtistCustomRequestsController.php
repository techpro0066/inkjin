<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendCustomRequestQuoteRequest;
use App\Mail\CustomRequestDeclinedArtistMail;
use App\Mail\CustomRequestDeclinedUserMail;
use App\Mail\CustomRequestQuoteArtistMail;
use App\Mail\CustomRequestQuoteUserMail;
use App\Models\CustomRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class ArtistCustomRequestsController extends Controller
{
    public function index(): View
    {
        $requests = CustomRequest::query()
            ->with(['user'])
            ->where('artist_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        $requestsPayload = $requests
            ->map(fn (CustomRequest $request) => $request->toArtistPanelArray())
            ->values()
            ->all();

        return view('artist.custom-requests.index', [
            'requests' => $requests,
            'requestsPayload' => $requestsPayload,
            'pendingCount' => $requests->where('status', 'pending')->count(),
        ]);
    }

    public function decline(Request $request, CustomRequest $customRequest): JsonResponse
    {
        if ((int) $customRequest->artist_id !== (int) Auth::id()) {
            abort(403);
        }

        if ($customRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be declined.',
            ], 422);
        }

        $validated = $request->validate([
            'reason_decline' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $customRequest->update([
            'status' => 'cancelled',
            'reason_decline' => $validated['reason_decline'],
        ]);

        $customRequest->refresh();
        $customRequest->load(['user', 'artist']);

        $this->sendDeclineEmails($customRequest);

        return response()->json([
            'success' => true,
            'message' => 'Request declined successfully.',
            'request' => $customRequest->toArtistPanelArray(),
        ]);
    }

    public function sendQuote(SendCustomRequestQuoteRequest $request, CustomRequest $customRequest): JsonResponse
    {
        if ($customRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can receive a quote.',
            ], 422);
        }

        $payload = $request->normalizedPayload();

        $update = [
            'status' => 'confirmed',
            'estimated_price' => $payload['estimated_price'],
            'estimated_time' => $payload['estimated_time'],
            'number_of_sessions' => $payload['number_of_sessions'],
            'message_for_client' => $payload['message_for_client'],
        ];

        if ($customRequest->isManagedRequest()) {
            $update['artist_session_slots'] = $payload['artist_session_slots'];
        }

        $customRequest->update($update);

        $customRequest->refresh();
        $customRequest->load(['user', 'artist']);

        $this->sendQuoteEmails($customRequest);

        return response()->json([
            'success' => true,
            'message' => 'Quote sent to the client successfully.',
            'request' => $customRequest->toArtistPanelArray(),
        ]);
    }

    private function sendDeclineEmails(CustomRequest $customRequest): void
    {
        $clientEmail = trim((string) ($customRequest->user?->email ?? ''));
        if ($clientEmail !== '') {
            try {
                Mail::to($clientEmail)->send(new CustomRequestDeclinedUserMail(
                    $customRequest,
                    route('user.dashboard'),
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send custom request declined email to client', [
                    'custom_request_id' => $customRequest->id,
                    'email' => $clientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $artistEmail = trim((string) ($customRequest->artist?->email ?? ''));
        if ($artistEmail !== '') {
            try {
                Mail::to($artistEmail)->send(new CustomRequestDeclinedArtistMail(
                    $customRequest,
                    route('artist.custom-requests.index'),
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send custom request declined email to artist', [
                    'custom_request_id' => $customRequest->id,
                    'email' => $artistEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendQuoteEmails(CustomRequest $customRequest): void
    {
        $user = $customRequest->user;
        $accessUrl = ($user instanceof User)
            ? $this->makePostCustomRequestAccessUrl($user, $customRequest)
            : route('user.dashboard');

        $clientEmail = trim((string) ($user?->email ?? ''));
        if ($clientEmail !== '') {
            try {
                Mail::to($clientEmail)->send(new CustomRequestQuoteUserMail(
                    $customRequest,
                    $accessUrl,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send custom request quote email to client', [
                    'custom_request_id' => $customRequest->id,
                    'email' => $clientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $artistEmail = trim((string) ($customRequest->artist?->email ?? ''));
        if ($artistEmail !== '') {
            try {
                Mail::to($artistEmail)->send(new CustomRequestQuoteArtistMail(
                    $customRequest,
                    route('artist.custom-requests.index'),
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send custom request quote email to artist', [
                    'custom_request_id' => $customRequest->id,
                    'email' => $artistEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function makePostCustomRequestAccessUrl(User $user, CustomRequest $customRequest): string
    {
        return URL::temporarySignedRoute(
            'user.post-custom-request.access',
            now()->addDays(14),
            ['user' => $user->id, 'customRequest' => $customRequest->id]
        );
    }
}
