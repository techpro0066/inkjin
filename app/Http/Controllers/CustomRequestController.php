<?php

namespace App\Http\Controllers;

use App\Mail\CustomRequestSubmittedArtistMail;
use App\Mail\CustomRequestSubmittedUserMail;
use App\Models\CustomRequest;
use App\Models\GuestSpot;
use App\Models\QuestionSorting;
use App\Models\Placement;
use App\Models\Style;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class CustomRequestController extends Controller
{
    public function requestCustom(Request $request, string $userName): View|RedirectResponse
    {
        $userDetail = UserDetail::query()
            ->with('user')
            ->where('user_name', $userName)
            ->first();

        if (
            !$userDetail
            || !$userDetail->user
            || $userDetail->user->role !== 'artist'
            || $userDetail->user->on_boarding !== 'yes'
        ) {
            abort(404, 'User not found');
        }

        if (in_array($userDetail->availability_status, ['closed', 'design_only'], true)) {
            return redirect()->route('public.artist', ['username' => $userName]);
        }

        $guestSpotId = null;
        $guestSpot = null;
        if ($request->has('guest_spot')) {
            $guestSpot = $this->resolveGuestSpotForArtist(
                $request->query('guest_spot'),
                (int) $userDetail->user_id
            );

            if (! $guestSpot) {
                return redirect()->route('public.artist', ['username' => $userName]);
            }

            $guestSpotId = $guestSpot->id;
        }

        // Entry pills on the regular custom flow only (not when already scoped to a guest spot).
        $activeGuestSpots = collect();
        if ($guestSpotId === null && ($userDetail->display_guest_spots ?? false)) {
            $activeGuestSpots = GuestSpot::query()
                ->where('user_id', $userDetail->user_id)
                ->where('status', 'available')
                ->whereDate('to_date', '>=', now()->toDateString())
                ->orderBy('from_date')
                ->orderBy('id')
                ->get()
                ->filter(fn (GuestSpot $spot) => $spot->isReservable())
                ->values();
        }

        $questions = QuestionSorting::activeQuestionsPayloadForArtist($userDetail->user_id, 'custom');

        if (count($questions) === 0) {
            return redirect()->route('public.artist', ['username' => $userName]);
        }

        $artistName = $userDetail->publicDisplayName();

        $studioLabel = $guestSpot?->studioNameWithCityCountry()
            ?: $userDetail->studioNameWithCityCountry();

        $fallbackTattooSlug = (string) ($userDetail->user->artistDesigns()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('slug') ?? '');

        $isManagedScheduling = ($userDetail->scheduling_type ?? '') === 'managed';

        $hiddenStyleOptions = Style::query()
            ->active()
            ->where('appear_on_question', false)
            ->ordered()
            ->pluck('name')
            ->values()
            ->all();

        $hiddenPlacementOptions = Placement::query()
            ->active()
            ->where('appear_on_question', false)
            ->ordered()
            ->pluck('name')
            ->values()
            ->all();

        return view('public.request-custom', [
            'userDetail' => $userDetail,
            'artistName' => $artistName !== '' ? $artistName : 'Artist',
            'questions' => $questions,
            'requiredBookingQuestions' => $questions,
            'hasArtistQuestions' => !empty($questions),
            'artistUsername' => $userDetail->user_name,
            'fallbackTattooSlug' => $fallbackTattooSlug,
            'artistProfileUrl' => route('public.artist', ['username' => $userDetail->user_name]),
            'isManagedScheduling' => $isManagedScheduling,
            'hiddenStyleOptions' => $hiddenStyleOptions,
            'hiddenPlacementOptions' => $hiddenPlacementOptions,
            'guestSpotId' => $guestSpotId,
            'guestSpot' => $guestSpot,
            'studioLabel' => $studioLabel,
            'activeGuestSpots' => $activeGuestSpots,
        ]);
    }

    public function submitCustomRequest(Request $request): JsonResponse
    {
        $userDetail = UserDetail::query()->where('user_name', $request->input('artist_username'))->first();
        if (
            !$userDetail
            || !$userDetail->user
            || $userDetail->user->role !== 'artist'
            || $userDetail->user->on_boarding !== 'yes'
        ) {
            return response()->json(['message' => 'Artist not found.'], 404);
        }

        $isManagedScheduling = ($userDetail->scheduling_type ?? '') === 'managed';

        $rules = [
            'artist_username' => ['required', 'string'],
            'request_payload' => ['required', 'array'],
            'request_payload.email' => ['required', 'email'],
            'request_payload.name' => ['nullable', 'string', 'max:255'],
            'request_payload.phone' => ['nullable', 'string', 'max:50'],
            'request_payload.notes' => ['nullable', 'string', 'max:10000'],
            'request_payload.referral_source' => ['nullable', 'string', 'max:255'],
            'request_payload.questions_answers' => ['nullable', 'array'],
            'request_payload.guest_id' => ['nullable', 'integer'],
        ];

        if ($isManagedScheduling) {
            $rules['request_payload.preferences'] = ['nullable', 'array'];
            $rules['request_payload.preferred_days'] = ['required', 'array', 'min:1'];
            $rules['request_payload.how_much_flexible'] = ['required', 'string', 'max:255'];
            $rules['request_payload.avoid_dates'] = ['nullable', 'string', 'max:500'];
            $rules['request_payload.urgency'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        $payload = $validated['request_payload'];
        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));

        $verified = $request->session()->get('booking_verified_emails', []);
        $verifiedEntry = is_array($verified[$email] ?? null) ? $verified[$email] : null;
        if (
            !$verifiedEntry
            || empty($verifiedEntry['user_id'])
            || now()->timestamp > (int) ($verifiedEntry['verified_until'] ?? 0)
        ) {
            return response()->json(['message' => 'Please verify your email before submitting your request.'], 422);
        }

        $guestSpot = $this->resolveGuestSpotForArtist(
            $payload['guest_id'] ?? null,
            (int) $userDetail->user_id
        );
        $isGuestRequest = $guestSpot !== null;

        if (! empty($payload['guest_id']) && ! $isGuestRequest) {
            return response()->json(['message' => 'This guest spot is no longer available.'], 422);
        }

        if (in_array($userDetail->availability_status, ['closed', 'design_only'], true)) {
            return response()->json(['message' => 'This artist is not accepting custom requests right now.'], 422);
        }

        $requestUser = User::query()->find((int) $verifiedEntry['user_id']);
        if (!$requestUser || mb_strtolower((string) $requestUser->email) !== $email) {
            return response()->json(['message' => 'User not found. Please verify email again.'], 422);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name !== '') {
            $parts = preg_split('/\s+/', $name) ?: [];
            $requestUser->first_name = $parts[0] ?? $requestUser->first_name;
            $requestUser->last_name = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : ($requestUser->last_name ?: 'User');
            $requestUser->save();
        }

        $requestUser->syncPhoneNumber($payload['phone'] ?? null);

        $questionsAnswers = is_array($payload['questions_answers'] ?? null) ? $payload['questions_answers'] : [];
        $phone = trim((string) ($payload['phone'] ?? ''));
        $referral = trim((string) ($payload['referral_source'] ?? ''));
        if ($phone !== '' || $referral !== '') {
            $questionsAnswers['_contact'] = array_filter([
                'phone' => $phone !== '' ? $phone : null,
                'referral_source' => $referral !== '' ? $referral : null,
            ]);
        }

        $schedulingType = $isManagedScheduling ? 'managed' : 'auto';

        $customRequest = CustomRequest::create([
            'user_id' => $requestUser->id,
            'artist_id' => $userDetail->user_id,
            'is_guest' => $isGuestRequest,
            'guest_id' => $guestSpot?->id,
            'type' => $schedulingType,
            'questions_answers' => $questionsAnswers,
            'anything_else_notes' => trim((string) ($payload['notes'] ?? '')) ?: null,
            'status' => 'pending',
            'preferences' => $isManagedScheduling ? ($payload['preferences'] ?? []) : null,
            'preferred_days' => $isManagedScheduling ? ($payload['preferred_days'] ?? []) : null,
            'avoid_dates' => $isManagedScheduling ? (trim((string) ($payload['avoid_dates'] ?? '')) ?: null) : null,
            'how_much_flexible' => $isManagedScheduling ? trim((string) ($payload['how_much_flexible'] ?? '')) : null,
            'urgency' => $isManagedScheduling ? trim((string) ($payload['urgency'] ?? '')) : null,
        ]);

        app(\App\Services\MailcoachSubscriberService::class)
            ->queueSubscribeUser($requestUser, \App\Services\MailcoachSubscriberService::TAG_USER);

        $isNewUser = !empty($verifiedEntry['is_new_user']);
        $accessUrl = $this->makePostCustomRequestAccessUrl($requestUser, $customRequest);
        $artistName = $userDetail->publicDisplayName() ?: 'Your artist';
        $recipientName = trim(implode(' ', array_filter([
            (string) ($requestUser->first_name ?? ''),
            (string) ($requestUser->last_name ?? ''),
        ])));

        $clientEmail = (string) ($requestUser->email ?? '');
        if ($clientEmail !== '') {
            try {
                Mail::to($clientEmail)->send(new CustomRequestSubmittedUserMail(
                    $accessUrl,
                    $recipientName,
                    $artistName,
                    $customRequest->referenceLabel(),
                    $isNewUser,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send custom request user email', [
                    'custom_request_id' => $customRequest->id,
                    'user_id' => $requestUser->id,
                    'email' => $clientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $artistEmail = (string) ($userDetail->user->email ?? '');
        if ($artistEmail !== '') {
            try {
                $customRequest->load(['user', 'artist']);
                Mail::to($artistEmail)->send(new CustomRequestSubmittedArtistMail(
                    $customRequest,
                    route('artist.custom-requests.index'),
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send custom request artist email', [
                    'custom_request_id' => $customRequest->id,
                    'artist_id' => $userDetail->user_id,
                    'email' => $artistEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'saved' => true,
            'custom_request_id' => $customRequest->id,
            'request_reference' => $customRequest->referenceLabel(),
            'post_request_access_url' => $accessUrl,
        ]);
    }

    private function makePostCustomRequestAccessUrl(User $user, CustomRequest $customRequest): string
    {
        return URL::temporarySignedRoute(
            'user.post-custom-request.access',
            now()->addDays(14),
            ['user' => $user->id, 'customRequest' => $customRequest->id]
        );
    }

    private function resolveGuestSpotForArtist(mixed $guestSpotId, int $artistUserId): ?GuestSpot
    {
        if ($guestSpotId === null || $guestSpotId === '') {
            return null;
        }

        $id = (int) $guestSpotId;
        if ($id <= 0) {
            return null;
        }

        return GuestSpot::query()
            ->whereKey($id)
            ->where('user_id', $artistUserId)
            ->where('status', 'available')
            ->whereDate('to_date', '>=', now()->toDateString())
            ->get()
            ->first(fn (GuestSpot $spot) => $spot->isReservable());
    }
}
