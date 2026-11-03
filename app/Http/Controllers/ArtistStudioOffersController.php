<?php

namespace App\Http\Controllers;

use App\Mail\CustomRequestQuoteArtistMail;
use App\Mail\CustomRequestQuoteUserMail;
use App\Models\CustomRequest;
use App\Models\StudioRequest;
use App\Models\User;
use App\Services\ArtistPayoutService;
use App\Support\ArtistSessionSlots;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ArtistStudioOffersController extends Controller
{
    /**
     * Accept the studio offer and send a quote in one step.
     */
    public function sendQuote(Request $request, StudioRequest $studioRequest): JsonResponse
    {
        $artistId = (int) Auth::id();
        if ((int) $studioRequest->assigned_artist_id !== $artistId) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }
        if (! $studioRequest->isAwaitingArtistDecision()) {
            return response()->json([
                'success' => false,
                'message' => 'This offer is no longer available.',
            ], 422);
        }

        $userDetail = Auth::user()?->userDetail;
        $payoutService = app(ArtistPayoutService::class);
        if ($userDetail && ! $payoutService->canAcceptClientPayments($userDetail)) {
            return response()->json([
                'success' => false,
                'message' => $payoutService->clientPaymentsBlockedMessage($userDetail),
            ], 422);
        }

        $validated = $request->validate([
            'estimated_price' => ['required', 'numeric', 'min:0.01'],
            'estimated_time' => ['required', 'string', 'max:255'],
            'number_of_sessions' => ['required', 'string', 'max:255'],
            'message_for_client' => ['required', 'string', 'min:5', 'max:2000'],
            'artist_session_slots' => ['required', 'array', 'min:1'],
            'artist_session_slots.*.date' => ['required', 'date', 'after_or_equal:today'],
            'artist_session_slots.*.ranges' => ['required', 'array', 'min:1'],
            'artist_session_slots.*.ranges.*.from' => ['required', 'date_format:H:i'],
            'artist_session_slots.*.ranges.*.to' => ['required', 'date_format:H:i'],
        ], [
            'artist_session_slots.required' => 'Add at least one tattoo session date with time windows.',
            'artist_session_slots.min' => 'Add at least one tattoo session date with time windows.',
            'artist_session_slots.*.date.after_or_equal' => 'Session dates must be today or in the future.',
        ]);

        $validator = validator($validated);
        ArtistSessionSlots::validateCollection(
            $validator,
            $validated['artist_session_slots'] ?? [],
            'artist_session_slots',
            'Tattoo session'
        );
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'Invalid session times.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sessionSlots = ArtistSessionSlots::normalize($validated['artist_session_slots'] ?? []);
        if ($sessionSlots === []) {
            return response()->json([
                'success' => false,
                'message' => 'Add at least one tattoo session date with time windows.',
                'errors' => ['artist_session_slots' => ['Add at least one tattoo session date with time windows.']],
            ], 422);
        }

        $custom = null;
        try {
            DB::transaction(function () use ($studioRequest, $artistId, $validated, $sessionSlots, &$custom) {
                $studioRequest->refresh();
                if (! $studioRequest->isAwaitingArtistDecision()
                    || (int) $studioRequest->assigned_artist_id !== $artistId) {
                    throw new \RuntimeException('This offer is no longer available.');
                }

                $answers = is_array($studioRequest->questions_answers) ? $studioRequest->questions_answers : [];

                $custom = CustomRequest::create([
                    'user_id' => $studioRequest->user_id,
                    'artist_id' => $artistId,
                    'is_guest' => false,
                    'from_waitlist' => false,
                    'type' => 'managed',
                    'questions_answers' => $answers,
                    'anything_else_notes' => $studioRequest->anything_else_notes,
                    'status' => 'confirmed',
                    'estimated_price' => round((float) $validated['estimated_price'], 2),
                    'estimated_time' => trim((string) $validated['estimated_time']),
                    'number_of_sessions' => trim((string) $validated['number_of_sessions']),
                    'message_for_client' => trim((string) $validated['message_for_client']),
                    'artist_session_slots' => $sessionSlots,
                    'preferences' => $studioRequest->preferences,
                    'preferred_days' => $studioRequest->preferred_days,
                    'avoid_dates' => $studioRequest->avoid_dates,
                    'how_much_flexible' => $studioRequest->how_much_flexible,
                    'urgency' => $studioRequest->urgency,
                ]);

                $studioRequest->forceFill([
                    'custom_request_id' => $custom->id,
                    'status' => StudioRequest::STATUS_MOVED_TO_CUSTOM,
                    'reason_decline' => null,
                ])->save();
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $custom?->refresh();
        $custom?->load(['user', 'artist']);
        $studioRequest->loadMissing('studio');

        if ($custom) {
            $this->sendQuoteEmails($custom);
        }

        $studioName = trim((string) ($studioRequest->studio?->name ?? ''));
        $panel = $custom?->toArtistPanelArray() ?? [];
        $panel['viaStudio'] = $studioName !== '' ? $studioName : null;
        $panel['canSendBack'] = false;
        $panel['sendBackUrl'] = null;
        $panel['studioRequestId'] = $studioRequest->id;
        $panel['studioOfferId'] = $studioRequest->id;
        $panel['isStudioOffer'] = false;

        return response()->json([
            'success' => true,
            'message' => 'Quote sent. You have taken this studio request.',
            'request' => $panel,
            'studioOfferId' => $studioRequest->id,
            'reload' => true,
        ]);
    }

    public function sendBack(Request $request, StudioRequest $studioRequest): JsonResponse
    {
        $artistId = (int) Auth::id();
        if ((int) $studioRequest->assigned_artist_id !== $artistId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $awaiting = $studioRequest->isAwaitingArtistDecision();
        $acceptedPending = $studioRequest->status === StudioRequest::STATUS_MOVED_TO_CUSTOM
            && ! empty($studioRequest->custom_request_id);

        if (! $awaiting && ! $acceptedPending) {
            return response()->json(['message' => 'This offer is no longer available.'], 422);
        }

        // If already quoted (custom confirmed), don't allow send-back via this path for quoted items —
        // only pending customs after an old accept, or awaiting offers.
        if ($acceptedPending) {
            $custom = CustomRequest::query()->find($studioRequest->custom_request_id);
            if ($custom && $custom->status !== 'pending') {
                return response()->json(['message' => 'This offer is no longer available.'], 422);
            }
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $reason = trim((string) ($validated['reason'] ?? '')) ?: null;

        DB::transaction(function () use ($studioRequest, $reason, $acceptedPending, $artistId) {
            if ($acceptedPending && $studioRequest->custom_request_id) {
                $custom = CustomRequest::query()->find($studioRequest->custom_request_id);
                if ($custom && $custom->status === 'pending') {
                    $custom->forceFill([
                        'status' => 'cancelled',
                        'reason_decline' => $reason ?: 'Sent back to studio',
                    ])->save();
                }
            }

            $artist = Auth::user();
            $artist?->loadMissing('userDetail');
            $artistName = trim((string) (
                $artist?->userDetail?->publicDisplayName()
                ?: trim(($artist?->first_name ?? '').' '.($artist?->last_name ?? ''))
            ));
            if ($artistName === '') {
                $artistName = 'Artist';
            }

            $studioRequest->appendSendBackHistory(
                'artist_send_back',
                $reason,
                $artistId,
                $artistName,
                $artistName,
            );
            $studioRequest->forceFill([
                'status' => StudioRequest::STATUS_SENT_BACK,
                'custom_request_id' => null,
                'assigned_artist_id' => null,
                'reason_decline' => $reason,
            ])->save();
        });

        return response()->json([
            'ok' => true,
            'message' => 'Sent back to the studio.',
        ]);
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
                Mail::to($clientEmail)->send(new CustomRequestQuoteUserMail($customRequest, $accessUrl));
            } catch (\Throwable $e) {
                Log::error('Failed to send studio-offer quote email to client', [
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
                    route('artist.requests.index', ['type' => 'custom']),
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send studio-offer quote email to artist', [
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
