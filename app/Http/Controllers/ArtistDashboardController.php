<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleCalendarEventRequiredException;
use App\Http\Controllers\GoogleCalendarController;
use App\Models\Availability;
use App\Models\AvailabilityOverride;
use App\Models\Booking;
use App\Models\CustomRequest;
use App\Models\PaymentLink;
use App\Models\Placement;
use App\Models\QuestionSorting;
use App\Models\Style;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\ArtistDashboardService;
use App\Services\PaymentLinkCheckoutService;
use App\Support\ArtistPolicyCopy;
use App\Support\PaymentMethods;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArtistDashboardController extends Controller
{
    public function __construct(private ArtistDashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $needsWeeklyAvailabilitySetup = $user && ! $user->hasWeeklyAvailabilitySlots();

        $recentCustomRequests = CustomRequest::query()
            ->with(['user'])
            ->where('artist_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $pendingCustomRequestsCount = CustomRequest::query()
            ->where('artist_id', Auth::id())
            ->where('status', 'pending')
            ->count();

        $dashboard = $this->dashboardService->buildForArtist((int) Auth::id());
        $userDetail = $user?->userDetail;
        $showCustomizePageNotice = $userDetail && ! $userDetail->customize_page_notice_dismissed;

        return view('artist.dashboard', [
            'needsWeeklyAvailabilitySetup' => $needsWeeklyAvailabilitySetup,
            'showCustomizePageNotice' => $showCustomizePageNotice,
            'recentCustomRequests' => $recentCustomRequests,
            'pendingCustomRequestsCount' => $pendingCustomRequestsCount,
            'dashboardStats' => $dashboard['stats'],
            'recentBookings' => $dashboard['recent_bookings'],
        ]);
    }

    public function paymentLink(Request $request): View
    {
        $schedulingType = $request->user()?->userDetail?->scheduling_type ?? '';
        $isAutoScheduling = $schedulingType === 'auto';
        $isManagedScheduling = $schedulingType === 'managed';

        return view('artist.payment-link', [
            'schedulingType' => $schedulingType,
            'isAutoScheduling' => $isAutoScheduling,
            'isManagedScheduling' => $isManagedScheduling,
        ]);
    }

    public function validatePaymentLink(Request $request): JsonResponse
    {
        $validator = $this->makePaymentLinkValidator($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'preview' => $this->paymentLinkPreview($request),
        ]);
    }

    public function storePaymentLink(Request $request): JsonResponse
    {
        $validator = $this->makePaymentLinkValidator($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $this->normalizedPaymentLinkData($request);
        $code = $this->generatePaymentLinkCode();
        $url = route('public.payment-link', ['code' => $code]);
        $displayUrl = preg_replace('#^https?://#', '', $url) ?? $url;
        $clientMessage = $this->buildClientMessage($payload, $url);

        $paymentLink = PaymentLink::query()->create([
            'artist_id' => Auth::id(),
            'status' => PaymentLink::STATUS_ACTIVE,
            'code' => $code,
            'url' => $url,
            'amount' => $payload['amount'],
            'payment_type' => $payload['payment_type'],
            'title' => $payload['title'],
            'date_time' => $payload['date_time'],
            'session_duration' => $payload['session_duration'],
            'total_price' => $payload['total_price'],
            'due_amount' => $payload['due_amount'],
            'expires' => $payload['expires'],
            'expires_at' => $payload['expires_at'],
            'client_message' => $clientMessage,
            'scheduling_type' => $payload['scheduling_type'],
        ]);

        $summaryParts = [$this->formatEuro((float) $payload['amount'])];
        if ($payload['date_time_formatted']) {
            $summaryParts[] = $payload['date_time_formatted'];
        }
        $summaryParts[] = $payload['session_duration_label'];

        return response()->json([
            'success' => true,
            'payment_link' => [
                'id' => $paymentLink->id,
                'title' => $payload['title'],
                'summary' => implode(' · ', $summaryParts),
                'url' => $url,
                'display_url' => $displayUrl,
                'client_message' => $clientMessage,
                'amount_formatted' => $this->formatEuro((float) $payload['amount']),
                'expires' => $payload['expires'],
            ],
        ]);
    }

    public function publicPaymentLink(Request $request, string $code): View
    {
        $paymentLink = PaymentLink::query()
            ->with(['artist.userDetail.user', 'booking.user'])
            ->where('code', $code)
            ->firstOrFail();

        if ($paymentLink->isExpired() && $paymentLink->status === PaymentLink::STATUS_ACTIVE) {
            $paymentLink->update(['status' => PaymentLink::STATUS_EXPIRED]);
        }

        $isExpired = $paymentLink->isExpired();
        $artist = $paymentLink->artist;
        $userDetail = $artist?->userDetail;
        $schedulingType = $paymentLink->scheduling_type ?: ($userDetail?->scheduling_type ?? 'managed');
        $isAutoScheduling = $schedulingType === 'auto';

        $durationMinutes = $this->paymentLinkDurationMinutes((string) $paymentLink->session_duration);
        $durationLabel = $this->paymentLinkDurationLabel((string) $paymentLink->session_duration);
        $isDeposit = $paymentLink->payment_type === 'deposit';
        $amountFormatted = $this->formatEuro((float) $paymentLink->amount);
        $dueFormatted = $isDeposit ? $this->formatEuro((float) $paymentLink->due_amount) : null;
        $totalFormatted = ($isDeposit && $paymentLink->total_price !== null)
            ? $this->formatEuro((float) $paymentLink->total_price)
            : null;

        $dateLine = null;
        $sessionLine = null;
        if ($paymentLink->date_time) {
            $start = $paymentLink->date_time->copy();
            $end = $start->copy()->addMinutes($durationMinutes);
            $dateLine = $start->format('D j M').' · '.$start->format('H:i').' – '.$end->format('H:i');
            $sessionLine = $start->format('D j M').' · '.$start->format('H:i');
        }

        $isPaid = $paymentLink->isPaid();
        $verifiedCheckout = (! $isExpired && ! $isPaid)
            ? $this->paymentLinkVerifiedCheckout($request, $code, $paymentLink)
            : null;

        if ($isPaid && $paymentLink->booking) {
            $booking = $paymentLink->booking;
            $tz = $booking->timezone ?: ($userDetail?->timezone ?: 'UTC');
            $startUtc = Carbon::parse(
                ($booking->booking_date instanceof Carbon ? $booking->booking_date->format('Y-m-d') : (string) $booking->booking_date)
                .' '.$booking->start_time_utc,
                'UTC'
            );
            $startLocal = $startUtc->timezone($tz);
            $dateLine = $startLocal->format('D j M').' · '.$startLocal->format('H:i').' – '.$startLocal->copy()->addMinutes($durationMinutes)->format('H:i');
            $sessionLine = $startLocal->format('D j M').' · '.$startLocal->format('H:i');
        } elseif ($verifiedCheckout && $paymentLink->slot_ymd && $paymentLink->slot_time) {
            try {
                $tz = $userDetail?->timezone ?: 'UTC';
                $startLocal = Carbon::createFromFormat('Y-m-d H:i', $paymentLink->slot_ymd.' '.$paymentLink->slot_time, $tz);
                if ($startLocal) {
                    $dateLine = $startLocal->format('D j M').' · '.$startLocal->format('H:i').' – '.$startLocal->copy()->addMinutes($durationMinutes)->format('H:i');
                    $sessionLine = $startLocal->format('D j M').' · '.$startLocal->format('H:i');
                }
            } catch (\Throwable) {
                // Keep managed date line when auto slot parsing fails.
            }
        }

        $clientFirstName = trim((string) strtok((string) ($paymentLink->payer_name ?? ''), ' '));
        if ($clientFirstName === '') {
            $clientFirstName = trim((string) ($paymentLink->booking?->user?->first_name ?? ''));
        }

        $artistHeader = $this->paymentLinkArtistHeader($userDetail);
        $policyCopy = ArtistPolicyCopy::for($userDetail);
        $artistFirst = trim((string) ($artist?->first_name ?? ''));
        $policyName = $artistFirst !== '' ? $artistFirst : ($artistHeader['name'] ?? 'Artist');
        $policyPossessive = str_ends_with(mb_strtolower($policyName), 's')
            ? $policyName."'"
            : $policyName."'s";

        $checkoutPhone = is_array($verifiedCheckout)
            ? ($verifiedCheckout['phone'] ?? $paymentLink->payer_phone)
            : $paymentLink->payer_phone;
        $showIrisTab = PaymentMethods::showIrisTab($userDetail, $checkoutPhone);
        $artistSupportsIris = PaymentMethods::isGreekArtist($userDetail);

        $checkoutStep = 'booking';
        if ($isPaid) {
            $checkoutStep = 'booked';
        } elseif (! $isExpired && $verifiedCheckout) {
            $checkoutStep = 'payment';
        } elseif (! $isExpired && $this->paymentLinkPendingOtp($request, $code)) {
            $checkoutStep = 'otp';
        }

        return view('public.payment-link', [
            'paymentLink' => $paymentLink,
            'isExpired' => $isExpired,
            'isPaid' => $isPaid,
            'isAutoScheduling' => $isAutoScheduling,
            'checkoutStep' => $checkoutStep,
            'verifiedCheckout' => $verifiedCheckout,
            'artistHeader' => $artistHeader,
            'userDetail' => $userDetail,
            'policyCopy' => $policyCopy,
            'policyPossessive' => $policyPossessive,
            'showIrisTab' => $showIrisTab,
            'artistSupportsIris' => $artistSupportsIris,
            'stripePublishableKey' => env('STRIPE_KEY', ''),
            'summary' => [
                'title' => $paymentLink->title,
                'amount' => $amountFormatted,
                'due' => $dueFormatted,
                'total' => $totalFormatted,
                'duration_label' => $durationLabel,
                'date_line' => $dateLine,
                'session_line' => $sessionLine,
                'is_deposit' => $isDeposit,
            ],
            'clientFirstName' => $clientFirstName,
            'autoDates' => (! $isExpired && ! $isPaid && $isAutoScheduling && $userDetail)
                ? $this->paymentLinkAutoDates($userDetail, $durationMinutes)
                : [],
            'pendingOtp' => ($isExpired || $isPaid || $verifiedCheckout) ? null : $this->paymentLinkPendingOtp($request, $code),
            'bookingReference' => $paymentLink->booking
                ? '#INK-'.str_pad((string) $paymentLink->booking->id, 6, '0', STR_PAD_LEFT)
                : null,
        ]);
    }

    public function sendPaymentLinkOtp(Request $request, string $code): JsonResponse
    {
        $paymentLink = PaymentLink::query()->where('code', $code)->firstOrFail();
        if ($paymentLink->isPaid()) {
            return response()->json([
                'sent' => false,
                'message' => 'This payment has already been completed.',
            ], 422);
        }
        if ($paymentLink->isExpired()) {
            return response()->json([
                'sent' => false,
                'message' => 'This payment link has expired.',
            ], 422);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $email = mb_strtolower(trim((string) $request->input('email')));
        $paymentLink->update([
            'payer_name' => trim((string) $request->input('name')) ?: $paymentLink->payer_name,
            'payer_email' => $email,
            'payer_phone' => trim((string) $request->input('phone')) ?: $paymentLink->payer_phone,
            'slot_ymd' => $request->input('slot_ymd') ?: $paymentLink->slot_ymd,
            'slot_time' => $request->input('slot_time') ?: $paymentLink->slot_time,
        ]);

        $otpResponse = app(InkJinController::class)->sendBookingOtp($request);
        if ($otpResponse->getStatusCode() !== 200) {
            return $otpResponse;
        }

        $email = mb_strtolower(trim((string) $request->input('email')));
        $request->session()->put($this->paymentLinkOtpSessionKey($code), [
            'name' => trim((string) $request->input('name')),
            'email' => $email,
            'phone' => trim((string) $request->input('phone')),
            'slot_ymd' => $request->input('slot_ymd'),
            'slot_time' => $request->input('slot_time'),
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return $otpResponse;
    }

    public function verifyPaymentLinkOtp(Request $request, string $code): JsonResponse
    {
        $paymentLink = PaymentLink::query()->where('code', $code)->firstOrFail();
        if ($paymentLink->isExpired()) {
            return response()->json([
                'verified' => false,
                'message' => 'This payment link has expired.',
            ], 422);
        }

        if ($paymentLink->isPaid()) {
            return response()->json([
                'verified' => true,
                'paid' => true,
            ]);
        }

        $pending = $request->session()->get($this->paymentLinkOtpSessionKey($code));
        $otpResponse = app(InkJinController::class)->verifyBookingOtp($request);
        if ($otpResponse->getStatusCode() !== 200) {
            return $otpResponse;
        }

        $email = mb_strtolower(trim((string) $request->input('email')));
        $name = trim((string) (is_array($pending) ? ($pending['name'] ?? '') : $request->input('name', '')));
        $phone = trim((string) (is_array($pending) ? ($pending['phone'] ?? '') : ''));
        $slotYmd = is_array($pending) ? ($pending['slot_ymd'] ?? null) : null;
        $slotTime = is_array($pending) ? ($pending['slot_time'] ?? null) : null;

        $paymentLink->update([
            'payer_name' => $name !== '' ? $name : $paymentLink->payer_name,
            'payer_email' => $email,
            'payer_phone' => $phone !== '' ? $phone : $paymentLink->payer_phone,
            'slot_ymd' => $slotYmd ?: $paymentLink->slot_ymd,
            'slot_time' => $slotTime ?: $paymentLink->slot_time,
        ]);

        $verified = $request->session()->get('booking_verified_emails', []);
        if (isset($verified[$email]) && is_array($verified[$email])) {
            $verified[$email]['verified_until'] = now()->addMinutes(45)->timestamp;
            $request->session()->put('booking_verified_emails', $verified);
        }

        $request->session()->put($this->paymentLinkCheckoutSessionKey($code), [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'user_id' => $verified[$email]['user_id'] ?? null,
            'expires_at' => now()->addMinutes(45)->timestamp,
        ]);
        $request->session()->forget($this->paymentLinkOtpSessionKey($code));

        return $otpResponse;
    }

    public function createPaymentLinkPaymentIntent(Request $request, string $code, PaymentLinkCheckoutService $checkout): JsonResponse
    {
        try {
            [$link, $client] = $this->paymentLinkCheckoutContext($request, $code);
            $cardholderName = trim((string) $request->input('cardholder_name', $link->payer_name ?: $client->first_name));
            if ($cardholderName === '') {
                $cardholderName = 'Cardholder';
            }

            return response()->json($checkout->createStripeIntent($link, $client, $cardholderName));
        } catch (GoogleCalendarEventRequiredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Unable to initialize payment.'], 422);
        }
    }

    public function confirmPaymentLinkPayment(Request $request, string $code, PaymentLinkCheckoutService $checkout): JsonResponse
    {
        $validated = $request->validate([
            'payment_intent_id' => ['required', 'string'],
            'payment_method' => ['nullable', 'string', 'max:32'],
        ]);

        try {
            [$link, $client] = $this->paymentLinkCheckoutContext($request, $code);
            $method = trim((string) ($validated['payment_method'] ?? 'card')) ?: 'card';
            $booking = $checkout->confirmStripePayment($link, $client, $validated['payment_intent_id'], $method);

            return response()->json($checkout->bookingResponse($booking, $client));
        } catch (GoogleCalendarEventRequiredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Unable to confirm payment.'], 422);
        }
    }

    public function createPaymentLinkVivaOrder(Request $request, string $code, PaymentLinkCheckoutService $checkout): JsonResponse
    {
        try {
            [$link, $client] = $this->paymentLinkCheckoutContext($request, $code);

            return response()->json($checkout->createVivaOrder($link, $client));
        } catch (GoogleCalendarEventRequiredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage() ?: 'Unable to start IRIS payment.'], 422);
        }
    }

    public function paymentLinkVivaStatus(Request $request, string $code, PaymentLinkCheckoutService $checkout): JsonResponse
    {
        $validated = $request->validate([
            'order_code' => ['required'],
        ]);

        try {
            [$link, $client] = $this->paymentLinkCheckoutContext($request, $code);
            $status = $checkout->vivaStatus($link, $client, (string) $validated['order_code']);
            $http = ($status['status'] ?? '') === 'not_found' ? 404 : 200;

            return response()->json($status, $http);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'not_found', 'message' => $e->getMessage()], 404);
        }
    }

    public function paymentLinkSessionDetails(Request $request, string $code, Booking $booking): View
    {
        $link = $this->paymentLinkBookingOrFail($code, $booking);
        $alreadySubmitted = $this->bookingHasSessionDetails($booking);

        if ($alreadySubmitted) {
            return view('public.payment-link-session-details', [
                'paymentLink' => $link,
                'booking' => $booking,
                'artistHeader' => $this->paymentLinkArtistHeader($link->artist?->userDetail),
                'questions' => [],
                'alreadySubmitted' => true,
                'linkExpired' => false,
                'hiddenStyleOptions' => [],
                'hiddenPlacementOptions' => [],
                'storeUrl' => '',
                'imageUrl' => '',
            ]);
        }

        if (! $link->sessionDetailsAreOpen()) {
            return view('public.payment-link-session-details', [
                'paymentLink' => $link,
                'booking' => $booking,
                'artistHeader' => $this->paymentLinkArtistHeader($link->artist?->userDetail),
                'questions' => [],
                'alreadySubmitted' => false,
                'linkExpired' => true,
                'hiddenStyleOptions' => [],
                'hiddenPlacementOptions' => [],
                'storeUrl' => '',
                'imageUrl' => '',
            ]);
        }

        $request->session()->put(
            $this->paymentLinkSessionDetailsKey($booking->id),
            $link->sessionDetailsExpiresAt()->timestamp
        );

        $userDetail = $link->artist?->userDetail;
        $questions = $userDetail
            ? QuestionSorting::activeQuestionsPayloadForArtist((int) $userDetail->user_id, 'default')
            : [];

        return view('public.payment-link-session-details', [
            'paymentLink' => $link,
            'booking' => $booking,
            'artistHeader' => $this->paymentLinkArtistHeader($userDetail),
            'questions' => $questions,
            'alreadySubmitted' => false,
            'linkExpired' => false,
            'hiddenStyleOptions' => Style::query()->active()->where('appear_on_question', false)->ordered()->pluck('name')->values()->all(),
            'hiddenPlacementOptions' => Placement::query()->active()->where('appear_on_question', false)->ordered()->pluck('name')->values()->all(),
            'storeUrl' => route('public.payment-link.session-details.store', ['code' => $code, 'booking' => $booking->id]),
            'imageUrl' => route('public.payment-link.session-details.image', ['code' => $code, 'booking' => $booking->id]),
        ]);
    }

    public function storePaymentLinkSessionDetails(Request $request, string $code, Booking $booking): JsonResponse
    {
        $this->assertPaymentLinkSessionDetailsAccess($request, $code, $booking);

        if ($this->bookingHasSessionDetails($booking)) {
            return response()->json([
                'saved' => true,
                'message' => 'Session details already submitted.',
            ]);
        }

        $link = $this->paymentLinkBookingOrFail($code, $booking);
        $userDetail = $link->artist?->userDetail;
        if (! $userDetail) {
            return response()->json(['message' => 'Artist not found.'], 404);
        }

        $questions = QuestionSorting::activeQuestionsPayloadForArtist((int) $userDetail->user_id, 'default');
        $incoming = $request->input('questions_answers', []);
        if (! is_array($incoming)) {
            $incoming = [];
        }

        $structured = [];
        foreach ($questions as $question) {
            $id = (string) ($question['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $raw = $incoming[$id] ?? $incoming[(int) $id] ?? null;
            $answer = is_array($raw) && array_key_exists('answer', $raw) ? $raw['answer'] : $raw;
            if (is_string($answer)) {
                $answer = trim($answer);
            }

            $isEmpty = $answer === null
                || $answer === ''
                || $answer === false
                || (is_array($answer) && $answer === []);

            if (! empty($question['is_required']) && $isEmpty) {
                return response()->json([
                    'message' => 'Please answer all required questions.',
                    'question_id' => $question['id'],
                ], 422);
            }

            if ($isEmpty) {
                continue;
            }

            $structured[$id] = [
                'id' => $question['id'],
                'question' => (string) ($question['question'] ?? ''),
                'type' => (string) ($question['type'] ?? 'input'),
                'answer' => $answer,
            ];
        }

        $noteParts = [];
        foreach ($structured as $item) {
            $label = strtolower((string) ($item['question'] ?? ''));
            $value = $item['answer'] ?? null;
            if (is_array($value) || ! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }
            if (str_contains($label, 'placement') || str_contains($label, 'body part') || str_contains($label, 'where')) {
                $noteParts[] = (string) $value;
            }
            if (str_contains($label, 'size') || str_contains($label, 'cm') || str_contains($label, 'inch')) {
                $noteParts[] = (string) $value;
            }
        }

        $existingNotes = trim((string) ($booking->notes ?? ''));
        $extraNotes = implode(' | ', array_unique($noteParts));
        $booking->update([
            'questions_answers' => $structured,
            'notes' => trim($existingNotes.($extraNotes !== '' ? "\n".$extraNotes : '')),
        ]);

        return response()->json(['saved' => true]);
    }

    public function uploadPaymentLinkSessionDetailsImage(Request $request, string $code, Booking $booking): JsonResponse
    {
        $this->assertPaymentLinkSessionDetailsAccess($request, $code, $booking);

        $validated = $request->validate([
            'question_id' => ['required'],
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        $file = $request->file('image');
        if (! $file) {
            return response()->json(['success' => false, 'message' => 'Image file is required.'], 422);
        }

        $folder = public_path('uploads/booking-questions');
        if (! is_dir($folder)) {
            @mkdir($folder, 0775, true);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = 'q_'.preg_replace('/[^A-Za-z0-9_-]/', '', (string) $validated['question_id'])
            .'_'.time().'_'.Str::random(8).'.'.$extension;

        $file->move($folder, $filename);
        $publicPath = '/uploads/booking-questions/'.$filename;

        return response()->json([
            'success' => true,
            'file_path' => $publicPath,
            'file_url' => asset(ltrim($publicPath, '/')),
        ]);
    }

    private function paymentLinkBookingOrFail(string $code, Booking $booking): PaymentLink
    {
        $link = PaymentLink::query()
            ->with(['artist.userDetail.user'])
            ->where('code', $code)
            ->firstOrFail();

        if (! $link->isPaid() || (int) $link->booking_id !== (int) $booking->id) {
            abort(404);
        }

        if ((int) $booking->artist_user_id !== (int) $link->artist_id) {
            abort(404);
        }

        return $link;
    }

    private function paymentLinkSessionDetailsKey(int $bookingId): string
    {
        return 'payment_link_session_details.'.$bookingId;
    }

    private function assertPaymentLinkSessionDetailsAccess(Request $request, string $code, Booking $booking): void
    {
        $link = $this->paymentLinkBookingOrFail($code, $booking);
        if (! $link->sessionDetailsAreOpen()) {
            abort(403, 'This payment link has expired.');
        }

        $until = (int) $request->session()->get($this->paymentLinkSessionDetailsKey($booking->id), 0);
        if ($until < now()->timestamp) {
            abort(403, 'Please open the link from your email again.');
        }
    }

    private function bookingHasSessionDetails(Booking $booking): bool
    {
        $answers = $booking->questions_answers;

        return is_array($answers) && $answers !== [];
    }

    private function paymentLinkOtpSessionKey(string $code): string
    {
        return 'payment_link_otp.'.$code;
    }

    private function paymentLinkCheckoutSessionKey(string $code): string
    {
        return 'payment_link_checkout.'.$code;
    }

    /**
     * @return array{name: string, email: string, phone: string, user_id: mixed}|null
     */
    private function paymentLinkVerifiedCheckout(Request $request, string $code, PaymentLink $link): ?array
    {
        $checkout = $request->session()->get($this->paymentLinkCheckoutSessionKey($code));
        $email = '';
        $name = '';
        $phone = '';

        if (is_array($checkout) && ! empty($checkout['email'])) {
            if (empty($checkout['expires_at']) || now()->timestamp > (int) $checkout['expires_at']) {
                $request->session()->forget($this->paymentLinkCheckoutSessionKey($code));
            } else {
                $email = mb_strtolower(trim((string) $checkout['email']));
                $name = (string) ($checkout['name'] ?? '');
                $phone = (string) ($checkout['phone'] ?? '');
            }
        }

        if ($email === '') {
            $email = mb_strtolower(trim((string) $link->payer_email));
            $name = (string) ($link->payer_name ?? '');
            $phone = (string) ($link->payer_phone ?? '');
        }

        if ($email === '') {
            return null;
        }

        $verified = $request->session()->get('booking_verified_emails', []);
        $entry = is_array($verified[$email] ?? null) ? $verified[$email] : null;
        if (
            ! $entry
            || empty($entry['user_id'])
            || now()->timestamp > (int) ($entry['verified_until'] ?? 0)
        ) {
            return null;
        }

        return [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'user_id' => $entry['user_id'],
        ];
    }

    /**
     * @return array{0: PaymentLink, 1: User}
     */
    private function paymentLinkCheckoutContext(Request $request, string $code): array
    {
        $link = PaymentLink::query()->with(['artist.userDetail'])->where('code', $code)->firstOrFail();
        $checkout = $this->paymentLinkVerifiedCheckout($request, $code, $link);
        if (! $checkout) {
            throw new \RuntimeException('Please verify your email before paying.');
        }

        $client = User::query()->find($checkout['user_id']);
        if (! $client) {
            throw new \RuntimeException('Booking user not found. Please verify email again.');
        }

        return [$link, $client];
    }

    private function paymentLinkPendingOtp(Request $request, string $code): ?array
    {
        $pending = $request->session()->get($this->paymentLinkOtpSessionKey($code));
        if (! is_array($pending) || empty($pending['email'])) {
            return null;
        }

        $email = mb_strtolower(trim((string) $pending['email']));
        $otpPayload = $request->session()->get('booking_otp.'.$email);
        $otpExpired = ! is_array($otpPayload)
            || empty($otpPayload['expires_at'])
            || now()->timestamp > (int) $otpPayload['expires_at'];
        $pendingExpired = empty($pending['expires_at']) || now()->timestamp > (int) $pending['expires_at'];

        if ($otpExpired || $pendingExpired) {
            $request->session()->forget($this->paymentLinkOtpSessionKey($code));

            return null;
        }

        $cooldownUntil = (int) $request->session()->get('booking_otp_cooldown.'.$email, 0);

        return [
            'name' => (string) ($pending['name'] ?? ''),
            'email' => $email,
            'phone' => (string) ($pending['phone'] ?? ''),
            'slot_ymd' => $pending['slot_ymd'] ?? null,
            'slot_time' => $pending['slot_time'] ?? null,
            'resend_available_in_seconds' => max(0, $cooldownUntil - now()->timestamp),
        ];
    }

    private function paymentLinkArtistHeader(?UserDetail $userDetail): array
    {
        if (! $userDetail) {
            return [
                'name' => 'Artist',
                'initials' => 'AR',
                'avatar_url' => null,
                'username' => '',
                'profile_url' => null,
                'studio_line' => '',
            ];
        }

        $userDetail->loadMissing('user');
        $user = $userDetail->user;
        $first = trim((string) ($user?->first_name ?? ''));
        $last = trim((string) ($user?->last_name ?? ''));
        $name = ($first !== '' && $last !== '')
            ? $first.' '.mb_strtoupper(mb_substr($last, 0, 1)).'.'
            : $userDetail->publicDisplayName();

        $username = trim((string) ($userDetail->user_name ?? ''));
        $studioParts = array_filter([
            trim((string) ($userDetail->studio_name ?? '')),
            trim((string) ($userDetail->city ?? '')),
        ]);

        $avatar = trim((string) ($userDetail->avatar ?? ''));

        return [
            'name' => $name,
            'initials' => $userDetail->publicDisplayInitials(),
            'avatar_url' => $avatar !== '' ? asset($avatar) : null,
            'username' => $username,
            'profile_url' => $username !== '' ? route('public.artist', ['username' => $username]) : null,
            'studio_line' => implode(' · ', $studioParts),
        ];
    }

    private function paymentLinkDurationMinutes(string $duration): int
    {
        return match ($duration) {
            '2h' => 120,
            '3h' => 180,
            '4h' => 240,
            'half-day' => 240,
            default => 180,
        };
    }

    private function paymentLinkDurationLabel(string $duration): string
    {
        return match ($duration) {
            'half-day' => 'Half day',
            default => $duration,
        };
    }

    private function paymentLinkAutoDates(UserDetail $userDetail, int $durationMinutes): array
    {
        $timezone = $userDetail->timezone ?: 'UTC';
        $now = Carbon::now($timezone);
        $schedule = Availability::query()
            ->where('user_id', $userDetail->user_id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week')
            ->map(function ($rows) use ($timezone) {
                return $rows->map(function ($availability) use ($timezone) {
                    $startLocal = Carbon::createFromFormat('Y-m-d H:i:s', now('UTC')->format('Y-m-d').' '.$availability->start_time, 'UTC')
                        ->setTimezone($timezone)
                        ->format('H:i');
                    $endLocal = Carbon::createFromFormat('Y-m-d H:i:s', now('UTC')->format('Y-m-d').' '.$availability->end_time, 'UTC')
                        ->setTimezone($timezone)
                        ->format('H:i');

                    return [
                        'start' => $startLocal,
                        'end' => $endLocal,
                    ];
                })->values()->all();
            })
            ->toArray();

        $blocked = AvailabilityOverride::query()
            ->where('user_id', $userDetail->user_id)
            ->get()
            ->map(fn (AvailabilityOverride $override) => [
                'start' => $override->start_date->format('Y-m-d'),
                'end' => $override->end_date->format('Y-m-d'),
            ])
            ->all();

        $busy = $this->paymentLinkBusyMap($userDetail, $timezone, $now->copy()->startOfDay(), $now->copy()->addDays(21)->endOfDay());
        $dates = [];

        for ($i = 0; $i < 21; $i++) {
            $day = $now->copy()->startOfDay()->addDays($i);
            $ymd = $day->format('Y-m-d');
            if ($this->paymentLinkDateIsBlocked($ymd, $blocked)) {
                continue;
            }

            $weekday = strtolower($day->format('l'));
            $ranges = $schedule[$weekday] ?? [];
            if ($ranges === []) {
                continue;
            }

            $times = [];
            foreach ($ranges as $range) {
                $startParts = explode(':', (string) ($range['start'] ?? '0:0'));
                $endParts = explode(':', (string) ($range['end'] ?? '0:0'));
                $startMinutes = ((int) ($startParts[0] ?? 0) * 60) + (int) ($startParts[1] ?? 0);
                $endMinutes = ((int) ($endParts[0] ?? 0) * 60) + (int) ($endParts[1] ?? 0);
                if ($endMinutes <= $startMinutes) {
                    continue;
                }

                for ($minute = $startMinutes; $minute + $durationMinutes <= $endMinutes; $minute += 30) {
                    if ($i === 0 && $minute <= (($now->hour * 60) + $now->minute)) {
                        continue;
                    }
                    if ($this->paymentLinkSlotIsBusy($busy[$ymd] ?? [], $minute, $durationMinutes)) {
                        continue;
                    }
                    $times[] = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
                }
            }

            $times = array_values(array_unique($times));
            if ($times === []) {
                continue;
            }

            $dates[] = [
                'ymd' => $ymd,
                'label' => $day->format('D j'),
                'book_label' => $day->format('D j M'),
                'times' => $times,
            ];
        }

        return $dates;
    }

    private function paymentLinkDateIsBlocked(string $ymd, array $blocked): bool
    {
        foreach ($blocked as $period) {
            if ($ymd >= $period['start'] && $ymd <= $period['end']) {
                return true;
            }
        }

        return false;
    }

    private function paymentLinkSlotIsBusy(array $intervals, int $startMinutes, int $durationMinutes): bool
    {
        $endMinutes = $startMinutes + $durationMinutes;
        foreach ($intervals as $interval) {
            $busyStart = (int) ($interval['start'] ?? 0);
            $busyEnd = (int) ($interval['end'] ?? 0);
            if ($startMinutes < $busyEnd && $endMinutes > $busyStart) {
                return true;
            }
        }

        return false;
    }

    private function paymentLinkBusyMap(UserDetail $userDetail, string $timezone, Carbon $from, Carbon $to): array
    {
        $map = [];
        $buffer = max(0, (int) ($userDetail->session_buffer_period ?? 0));

        $bookings = Booking::query()
            ->where('artist_user_id', $userDetail->user_id)
            ->where('status', 'confirmed')
            ->whereDate('booking_date', '>=', $from->toDateString())
            ->whereDate('booking_date', '<=', $to->toDateString())
            ->get();

        foreach ($bookings as $booking) {
            $ymd = $booking->booking_date instanceof Carbon
                ? $booking->booking_date->format('Y-m-d')
                : (string) $booking->booking_date;
            try {
                $startAt = Carbon::parse($ymd.' '.$booking->start_time_utc, 'UTC')->timezone($timezone);
                $endAt = Carbon::parse($ymd.' '.$booking->end_time_utc, 'UTC')->timezone($timezone);
                if ($buffer > 0) {
                    $endAt->addMinutes($buffer);
                }
                $key = $startAt->format('Y-m-d');
                $map[$key][] = [
                    'start' => ($startAt->hour * 60) + $startAt->minute,
                    'end' => ($endAt->hour * 60) + $endAt->minute,
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        if (! empty($userDetail->google_calendar_token)) {
            try {
                $blocks = GoogleCalendarController::getBusyBlocksForDateRange(
                    $userDetail,
                    $from->toDateString(),
                    $to->toDateString(),
                    $timezone
                );
                foreach ($blocks as $block) {
                    $startUtc = $block['start_datetime_utc'] ?? null;
                    $endUtc = $block['end_datetime_utc'] ?? null;
                    if (! $startUtc || ! $endUtc) {
                        continue;
                    }
                    $startAt = ($startUtc instanceof Carbon ? $startUtc->copy() : Carbon::parse((string) $startUtc, 'UTC'))->timezone($timezone);
                    $endAt = ($endUtc instanceof Carbon ? $endUtc->copy() : Carbon::parse((string) $endUtc, 'UTC'))->timezone($timezone);
                    if ($buffer > 0) {
                        $endAt->addMinutes($buffer);
                    }
                    $key = $startAt->format('Y-m-d');
                    $map[$key][] = [
                        'start' => ($startAt->hour * 60) + $startAt->minute,
                        'end' => ($endAt->hour * 60) + $endAt->minute,
                    ];
                }
            } catch (\Throwable) {
                // Availability still works without Google Calendar busy times.
            }
        }

        return $map;
    }

    private function makePaymentLinkValidator(Request $request)
    {
        $isManagedScheduling = ($request->user()?->userDetail?->scheduling_type ?? '') === 'managed';
        $isDeposit = $request->input('payment_type') === 'deposit';

        $rules = [
            'amount' => ['required', 'string'],
            'payment_type' => ['required', 'in:deposit,full'],
            'title' => ['required', 'string', 'max:255'],
            'session_duration' => ['required', 'in:2h,3h,4h,half-day'],
            'expires' => ['required', 'string', 'max:50'],
            'total_price' => [Rule::requiredIf($isDeposit), 'nullable', 'string'],
        ];

        if ($isManagedScheduling) {
            $rules['date_time'] = ['required', 'date_format:Y-m-d H:i', 'after:now'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'amount.required' => 'Please enter an amount.',
            'payment_type.required' => 'Please select a payment type.',
            'payment_type.in' => 'Please select a valid payment type.',
            'title.required' => 'Please enter a title.',
            'date_time.required' => 'Please select a date and time.',
            'date_time.date_format' => 'Please select a valid date and time.',
            'date_time.after' => 'Please select a future date and time.',
            'session_duration.required' => 'Please select a session duration.',
            'session_duration.in' => 'Please select a valid session duration.',
            'total_price.required' => 'Please enter a total price.',
            'expires.required' => 'Please enter when this link expires.',
        ]);

        $validator->after(function ($validator) use ($request, $isDeposit) {
            $amountRaw = trim((string) $request->input('amount', ''));
            $amount = $this->parseMoney($amountRaw);

            if ($amountRaw !== '' && $amount === null) {
                $validator->errors()->add('amount', 'Please enter a valid amount.');
            } elseif ($amount !== null && $amount <= 0) {
                $validator->errors()->add('amount', 'Amount must be greater than 0.');
            }

            if (! $isDeposit) {
                return;
            }

            $totalRaw = trim((string) $request->input('total_price', ''));
            if ($totalRaw === '') {
                return;
            }

            $total = $this->parseMoney($totalRaw);
            if ($total === null) {
                $validator->errors()->add('total_price', 'Please enter a valid total price.');

                return;
            }

            if ($amountRaw === '' || $amount === null) {
                $validator->errors()->add('total_price', 'Please enter the amount first.');

                return;
            }

            if ($total <= $amount) {
                $validator->errors()->add('total_price', 'Total price must be greater than the amount.');
            }
        });

        return $validator;
    }

    private function normalizedPaymentLinkData(Request $request): array
    {
        $schedulingType = $request->user()?->userDetail?->scheduling_type ?? '';
        $isDeposit = $request->input('payment_type') === 'deposit';
        $amount = (float) $this->parseMoney((string) $request->input('amount'));
        $total = $isDeposit ? $this->parseMoney((string) $request->input('total_price')) : null;
        $due = ($isDeposit && $total !== null) ? round($total - $amount, 2) : 0.0;
        $expires = trim((string) $request->input('expires'));
        $sessionDuration = (string) $request->input('session_duration');
        $dateTime = null;
        $dateTimeFormatted = null;

        if ($schedulingType === 'managed' && $request->filled('date_time')) {
            $dateTime = Carbon::createFromFormat('Y-m-d H:i', (string) $request->input('date_time'));
            $dateTimeFormatted = $dateTime?->format('D j M, H:i');
        }

        return [
            'amount' => $amount,
            'payment_type' => (string) $request->input('payment_type'),
            'title' => trim((string) $request->input('title')),
            'date_time' => $dateTime,
            'date_time_formatted' => $dateTimeFormatted,
            'session_duration' => $sessionDuration,
            'session_duration_label' => $sessionDuration === 'half-day' ? 'Half day' : $sessionDuration,
            'total_price' => $isDeposit ? $total : null,
            'due_amount' => $due,
            'expires' => $expires,
            'expires_at' => $this->parseExpiresAt($expires),
            'scheduling_type' => $schedulingType,
        ];
    }

    private function paymentLinkPreview(Request $request): array
    {
        $payload = $this->normalizedPaymentLinkData($request);
        $isDeposit = $payload['payment_type'] === 'deposit';

        return [
            'title' => $payload['title'],
            'payment_type' => $payload['payment_type'],
            'amount_formatted' => $this->formatEuro($payload['amount']),
            'due_formatted' => $isDeposit ? $this->formatEuro((float) $payload['due_amount']) : null,
            'expires' => $payload['expires'],
            'session_duration_label' => $payload['session_duration_label'],
            'date_time_formatted' => $payload['date_time_formatted'],
        ];
    }

    private function buildClientMessage(array $payload, string $displayUrl): string
    {
        $kind = $payload['payment_type'] === 'deposit' ? 'deposit' : 'payment';

        return sprintf(
            "Here's your secure %s link for %s — %s, valid %s: %s",
            $kind,
            $payload['title'],
            $this->formatEuro((float) $payload['amount']),
            $payload['expires'],
            $displayUrl
        );
    }

    private function generatePaymentLinkCode(): string
    {
        $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (PaymentLink::query()->where('code', $code)->exists());

        return $code;
    }

    private function parseExpiresAt(string $expires): Carbon
    {
        if (preg_match('/(\d+)\s*(week|weeks|w)\b/i', $expires, $match)) {
            return now()->addWeeks((int) $match[1]);
        }

        if (preg_match('/(\d+)\s*(day|days|d)\b/i', $expires, $match)) {
            return now()->addDays((int) $match[1]);
        }

        if (preg_match('/(\d+)\s*(hour|hours|h)\b/i', $expires, $match)) {
            return now()->addHours((int) $match[1]);
        }

        return now()->addDays(7);
    }

    private function formatEuro(float $value): string
    {
        $rounded = round($value, 2);
        if (fmod($rounded, 1.0) === 0.0) {
            return '€'.(string) (int) $rounded;
        }

        return '€'.number_format($rounded, 2, '.', '');
    }

    private function parseMoney(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/[^\d.,-]/', '', $value) ?? '';
        $cleaned = str_replace(',', '.', $cleaned);

        if ($cleaned === '' || $cleaned === '.' || $cleaned === '-') {
            return null;
        }

        if (! is_numeric($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }
}
