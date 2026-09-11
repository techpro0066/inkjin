<?php

namespace App\Http\Controllers;

use App\Models\ConsentAnswer;
use App\Models\User;
use App\Services\BookingConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ClientConsentController extends Controller
{
    public function __construct(
        private BookingConsentService $bookingConsents
    ) {}

    /**
     * Live client consent form via secure token.
     */
    public function show(string $token): View
    {
        $consent = ConsentAnswer::query()
            ->where('token', $token)
            ->with(['booking', 'artist.userDetail'])
            ->first();

        if (! $consent) {
            throw new NotFoundHttpException('Consent form not found.');
        }

        if ($consent->status === ConsentAnswer::STATUS_CANCELLED) {
            return view('public.client-consent-unavailable', [
                'message' => 'This consent form is no longer available because the booking was cancelled.',
            ]);
        }

        if ($consent->status === ConsentAnswer::STATUS_EXPIRED) {
            return view('public.client-consent-unavailable', [
                'message' => 'This consent form has expired.',
            ]);
        }

        $payload = $this->bookingConsents->clientPayload($consent);

        return view('public.client-consent', [
            'payload' => $payload,
        ]);
    }

    /**
     * Design/preview by artist id (no booking, no submit).
     */
    public function preview(int $artist): View
    {
        $user = User::query()
            ->where('id', $artist)
            ->where('role', 'artist')
            ->with('userDetail')
            ->first();

        if (! $user) {
            throw new NotFoundHttpException('Artist not found.');
        }

        return view('public.client-consent', [
            'payload' => $this->bookingConsents->previewPayload($user),
        ]);
    }

    /**
     * Persist submitted consent answers.
     */
    public function submit(Request $request, string $token): JsonResponse
    {
        $consent = ConsentAnswer::query()->where('token', $token)->first();
        if (! $consent) {
            return response()->json(['success' => false, 'message' => 'Consent form not found.'], 404);
        }

        if ($consent->isCompleted()) {
            return response()->json([
                'success' => true,
                'already_completed' => true,
                'message' => 'Already signed.',
            ]);
        }

        if (! $consent->isOpenForClient()) {
            return response()->json([
                'success' => false,
                'message' => 'This consent form is no longer available.',
            ], 422);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:64'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_relationship' => ['nullable', 'string', 'max:255'],
            'guardian_id_reference' => ['nullable', 'string', 'max:255'],
            'guardian_signature' => ['nullable', 'string', 'max:255'],
            'accepted_risks' => ['required', 'boolean'],
            'accepted_health_consent' => ['required', 'boolean'],
            'accepted_aftercare' => ['required', 'boolean'],
            'accepted_data_notice' => ['required', 'boolean'],
            'accepted_photo' => ['sometimes', 'boolean'],
            'typed_signature' => ['required', 'string', 'max:255'],
            'form_language' => ['nullable', 'string', 'max:8'],
            'answers' => ['required', 'array'],
            'answers.health' => ['nullable', 'array'],
            'answers.risk' => ['nullable', 'array'],
            'answers.aftercare' => ['nullable', 'array'],
            'answers.other_health_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $validated['accepted_risks']
            || ! $validated['accepted_health_consent']
            || ! $validated['accepted_aftercare']
            || ! $validated['accepted_data_notice']) {
            return response()->json([
                'success' => false,
                'message' => 'Please accept all required consent items.',
            ], 422);
        }

        try {
            $saved = $this->bookingConsents->submit($consent, $validated);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Unable to save consent.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'already_completed' => false,
            'status' => $saved->status,
            'completed_at' => $saved->completed_at?->toIso8601String(),
        ]);
    }
}
