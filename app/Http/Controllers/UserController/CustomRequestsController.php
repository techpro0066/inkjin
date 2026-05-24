<?php

namespace App\Http\Controllers\UserController;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomRequestAutoSlotRequest;
use App\Http\Requests\StoreCustomRequestClientSlotsRequest;
use App\Models\Booking;
use App\Models\CustomRequest;
use App\Services\BookingCalendarAvailabilityService;
use App\Services\BookingCheckoutPricingService;
use App\Services\CustomRequestBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class CustomRequestsController extends Controller
{
    public function __construct(
        private readonly BookingCheckoutPricingService $pricing,
        private readonly CustomRequestBookingService $bookingService,
        private readonly BookingCalendarAvailabilityService $calendar,
    ) {}

    public function confirmTimes(Request $request, CustomRequest $customRequest): View|RedirectResponse
    {
        $this->authorizeCustomRequest($customRequest);

        if ($customRequest->isBooked()) {
            return redirect()->route('user.bookings.index')
                ->with('success', 'This custom request is already booked.');
        }

        if (!$customRequest->canAccessConfirmTimesPage()) {
            return redirect()->route('user.requests.index', ['tab' => 'custom'])
                ->with('error', 'This request is not ready for scheduling yet.');
        }

        if ($request->boolean('fresh') && $customRequest->clientHasSelectedTimes()) {
            $customRequest->update([
                'client_session_slots' => null,
                'client_consultation_slots' => null,
            ]);
            $customRequest->refresh();
        }

        $customRequest->load(['artist.userDetail']);

        if ($customRequest->usesArtistOfferedSlotsPicker()) {
            return view('user.custom-requests.confirm-times-managed', $this->managedPickerData($customRequest));
        }

        return view('user.custom-requests.confirm-times-auto', $this->autoPickerData($customRequest));
    }

    public function storeConfirmedTimes(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $this->authorizeCustomRequest($customRequest);

        if (!$customRequest->canAccessConfirmTimesPage()) {
            return redirect()->route('user.requests.index', ['tab' => 'custom'])
                ->with('error', 'This request is not ready for scheduling yet.');
        }

        $payload = $customRequest->usesArtistOfferedSlotsPicker()
            ? app()->call(fn (StoreCustomRequestClientSlotsRequest $formRequest) => $formRequest->normalizedPayload())
            : app()->call(fn (StoreCustomRequestAutoSlotRequest $formRequest) => $formRequest->normalizedPayload());

        $customRequest->update($payload);

        return redirect()
            ->route('user.custom-requests.payment', $customRequest)
            ->with('success', 'Times saved. Complete payment to confirm your booking.');
    }

    public function calendarData(CustomRequest $customRequest): JsonResponse
    {
        $this->authorizeCustomRequest($customRequest);

        if ($customRequest->usesArtistOfferedSlotsPicker()) {
            return response()->json(['message' => 'Calendar is not used when the artist offered session times.'], 422);
        }

        if (!$customRequest->canAccessConfirmTimesPage()) {
            return response()->json(['message' => 'This request is not ready for scheduling.'], 422);
        }

        return response()->json($this->calendar->calendarPayloadForCustomRequest($customRequest));
    }

    public function payment(CustomRequest $customRequest): View|RedirectResponse
    {
        $this->authorizeCustomRequest($customRequest);

        if ($customRequest->isBooked()) {
            return redirect()->route('user.bookings.index')
                ->with('success', 'Your booking is already confirmed.');
        }

        if (!$customRequest->canPay()) {
            if ($customRequest->clientHasSelectedTimes()) {
                return redirect()->route('user.custom-requests.confirm-times', [
                    'customRequest' => $customRequest,
                    'from_payment' => 1,
                ])->with('error', 'Your saved times could not be used for payment. Please review and continue again.');
            }

            if ($customRequest->canSelectTimes()) {
                return redirect()->route('user.custom-requests.confirm-times', [
                    'customRequest' => $customRequest,
                    'fresh' => 1,
                ]);
            }

            return redirect()->route('user.requests.index', ['tab' => 'custom'])
                ->with('error', 'Please choose your appointment time before paying.');
        }

        $customRequest->load(['artist.userDetail']);
        $userDetail = $customRequest->artist?->userDetail;
        if (!$userDetail) {
            abort(404);
        }

        $quotePrice = $customRequest->checkoutPriceAmount();
        $totals = $this->pricing->checkoutTotals($userDetail, $quotePrice);
        $deposit = (float) $totals['deposit'];
        $balance = max(0, $quotePrice - $deposit);

        return view('user.custom-requests.payment', [
            'customRequest' => $customRequest,
            'userDetail' => $userDetail,
            'artistName' => $customRequest->artistDisplayName(),
            'totals' => $totals,
            'stripePublishableKey' => env('STRIPE_KEY', ''),
            'showConsultRow' => $customRequest->autoRequiresConsultation(),
            'sessionDateTimeLabel' => $customRequest->autoRequiresConsultation() ? 'Tattoo session' : 'Session',
            'sessionDateTime' => $customRequest->clientSlotSummary() ?? '—',
            'consultDateTime' => $customRequest->autoRequiresConsultation()
                ? ($customRequest->clientConsultSlotSummary() ?? '—')
                : null,
            'durationLabel' => $customRequest->checkoutDurationLabel(),
            'sessionsLabel' => trim((string) ($customRequest->number_of_sessions ?? '')) ?: '—',
            'priceEstimateLabel' => $customRequest->estimatedPriceLabel(),
            'depositLabel' => $customRequest->checkoutDepositLabel($totals['deposit_meta']),
            'balanceLabel' => '€'.number_format($balance, 2),
        ]);
    }

    public function createPaymentIntent(Request $request, CustomRequest $customRequest): JsonResponse
    {
        $this->authorizeCustomRequest($customRequest);

        if (!$customRequest->canPay()) {
            return response()->json(['message' => 'This request is not ready for payment.'], 422);
        }

        $request->validate([
            'cardholder_name' => ['required', 'string', 'max:255'],
        ]);

        $customRequest->load(['artist.userDetail']);
        $userDetail = $customRequest->artist?->userDetail;
        if (!$userDetail) {
            return response()->json(['message' => 'Artist not found.'], 404);
        }

        $stripeSecret = env('STRIPE_SECRET');
        if (!$stripeSecret) {
            return response()->json(['message' => 'Stripe is not configured.'], 500);
        }

        $totals = $this->pricing->checkoutTotals($userDetail, $customRequest->checkoutPriceAmount());
        $amountCents = (int) round($totals['total_due'] * 100);

        if ($amountCents < 50) {
            return response()->json(['message' => 'Payment amount is too small.'], 422);
        }

        try {
            Stripe::setApiKey($stripeSecret);

            $intent = PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => 'eur',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'custom_request_id' => (string) $customRequest->id,
                    'user_id' => (string) $customRequest->user_id,
                    'artist_user_id' => (string) $customRequest->artist_id,
                    'flow' => 'custom_request',
                    'cardholder_name' => $request->input('cardholder_name'),
                ],
            ]);

            return response()->json([
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'amount_cents' => $amountCents,
                'currency' => 'eur',
            ]);
        } catch (ApiErrorException $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Unable to initialize payment.',
            ], 422);
        }
    }

    public function confirmPayment(Request $request, CustomRequest $customRequest): JsonResponse
    {
        $this->authorizeCustomRequest($customRequest);

        $validated = $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        if ($customRequest->isBooked() && $customRequest->booking_id) {
            $booking = Booking::query()->find($customRequest->booking_id);

            return response()->json([
                'saved' => true,
                'booking_id' => $booking?->id,
                'booking_reference' => $booking
                    ? '#INK-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT)
                    : null,
                'redirect_url' => route('user.bookings.index'),
            ]);
        }

        $stripeSecret = env('STRIPE_SECRET');
        if (!$stripeSecret) {
            return response()->json(['message' => 'Stripe is not configured.'], 500);
        }

        try {
            Stripe::setApiKey($stripeSecret);
            $intent = PaymentIntent::retrieve($validated['payment_intent_id']);

            if (!$intent || $intent->status !== 'succeeded') {
                return response()->json(['message' => 'Payment is not completed.'], 422);
            }

            if ((int) ($intent->metadata['custom_request_id'] ?? 0) !== (int) $customRequest->id) {
                return response()->json(['message' => 'Payment does not match this request.'], 422);
            }

            $booking = $this->bookingService->createBookingFromCustomRequest($customRequest, $intent);

            return response()->json([
                'saved' => true,
                'booking_id' => $booking->id,
                'booking_reference' => '#INK-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
                'redirect_url' => route('user.bookings.index'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Custom request payment confirm failed', [
                'custom_request_id' => $customRequest->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Unable to complete booking.',
            ], 422);
        }
    }

    private function authorizeCustomRequest(CustomRequest $customRequest): void
    {
        if ((int) $customRequest->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function managedPickerData(CustomRequest $customRequest): array
    {
        $offeredSession = $customRequest->offeredSlotsMapForPicker();

        return [
            'customRequest' => $customRequest,
            'offeredSession' => $offeredSession,
            'initialPicker' => $customRequest->initialPickerMonthFromOfferedSlots(),
            'hasSessionSlots' => $offeredSession !== [],
            'savedSelection' => $customRequest->clientPickerSavedSelection(),
            'fromPayment' => request()->boolean('from_payment'),
            'artistName' => $customRequest->artistDisplayName(),
            'artistNotes' => trim((string) ($customRequest->message_for_client ?? '')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function autoPickerData(CustomRequest $customRequest): array
    {
        $calendarPayload = $this->calendar->calendarPayloadForCustomRequest($customRequest);

        $userDetail = $customRequest->artist?->userDetail;

        return [
            'customRequest' => $customRequest,
            'calendarPayload' => $calendarPayload,
            'savedSelection' => $this->pickerSavedSelectionFromOldOrModel($customRequest, 'client_session_slots')
                ?? $customRequest->clientPickerSavedSelection(),
            'savedConsultSelection' => $this->pickerSavedSelectionFromOldOrModel($customRequest, 'client_consultation_slots')
                ?? $customRequest->clientPickerSavedConsultSelection(),
            'savedSessionSummary' => $customRequest->clientSlotSummary(),
            'savedConsultSummary' => $customRequest->clientConsultSlotSummary(),
            'fromPayment' => request()->boolean('from_payment'),
            'artistName' => $customRequest->artistDisplayName(),
            'artistTimezone' => $calendarPayload['artistTimezone'] ?? null,
            'durationMinutes' => $customRequest->sessionDurationMinutes(),
            'consultationRequired' => $customRequest->autoRequiresConsultation(),
            'studioName' => trim((string) ($userDetail?->studio_name ?? '')),
            'studioAddress' => trim((string) ($userDetail?->studio_address ?? '')),
            'consultDurationMinutes' => (int) ($calendarPayload['artistConsultationSettings']['session_duration_minutes'] ?? 30),
        ];
    }

    /**
     * @return array{date: string, from: string, to: string}|null
     */
    private function pickerSavedSelectionFromOldOrModel(CustomRequest $customRequest, string $key): ?array
    {
        $date = trim((string) old($key.'.0.date', ''));
        $from = substr(trim((string) old($key.'.0.ranges.0.from', '')), 0, 5);
        $to = substr(trim((string) old($key.'.0.ranges.0.to', '')), 0, 5);

        if ($date === '' || $from === '') {
            return null;
        }

        return ['date' => $date, 'from' => $from, 'to' => $to !== '' ? $to : $from];
    }
}
