<?php

namespace App\Http\Controllers\UserController;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientSelectedSlotsRequest;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Services\BookingCheckoutPricingService;
use App\Services\ManagedRequestBookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class RequestsController extends Controller
{
    public function __construct(
        private readonly BookingCheckoutPricingService $pricing,
        private readonly ManagedRequestBookingService $bookingService,
    ) {}

    public function index()
    {
        $requests = BookingRequest::query()
            ->with(['tattoo', 'artist.userDetail'])
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        $requestsPayload = $requests
            ->map(fn (BookingRequest $request) => $request->toUserPanelArray())
            ->values()
            ->all();

        return view('user.requests.index', [
            'requests' => $requests,
            'requestsPayload' => $requestsPayload,
        ]);
    }

    public function confirmTimes(BookingRequest $bookingRequest): View|RedirectResponse
    {
        $this->authorizeRequest($bookingRequest);

        if ($bookingRequest->isBooked()) {
            return redirect()->route('user.bookings.index')
                ->with('success', 'This request is already booked.');
        }

        if (!$bookingRequest->canAccessConfirmTimesPage()) {
            return redirect()->route('user.requests.index')
                ->with('error', 'This request is not ready for scheduling yet.');
        }

        return view('user.requests.confirm-times', $this->pickerViewData($bookingRequest));
    }

    public function storeConfirmedTimes(StoreClientSelectedSlotsRequest $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $bookingRequest->update($request->normalizedPayload($bookingRequest));

        return redirect()
            ->route('user.requests.payment', $bookingRequest)
            ->with('success', 'Times saved. Complete payment to confirm your booking.');
    }

    public function payment(BookingRequest $bookingRequest): View|RedirectResponse
    {
        $this->authorizeRequest($bookingRequest);

        if ($bookingRequest->isBooked()) {
            return redirect()->route('user.bookings.index')
                ->with('success', 'Your booking is already confirmed.');
        }

        if (!$bookingRequest->canPay()) {
            if ($bookingRequest->canSelectTimes()) {
                return redirect()->route('user.requests.confirm-times', $bookingRequest);
            }

            return redirect()->route('user.requests.index')
                ->with('error', 'Please choose your appointment times before paying.');
        }

        $bookingRequest->load(['tattoo', 'artist.userDetail']);
        $userDetail = $bookingRequest->artist?->userDetail;
        $tattoo = $bookingRequest->tattoo;

        if (!$userDetail || !$tattoo) {
            abort(404);
        }

        $minPrice = (float) ($tattoo->min_price ?? 0);
        $maxPrice = (float) ($tattoo->max_price ?? 0);
        $totals = $this->pricing->checkoutTotals($userDetail, $minPrice);
        $deposit = (float) $totals['deposit'];
        $minBalance = max(0, $minPrice - $deposit);
        $maxBalance = max(0, $maxPrice - $deposit);

        $showConsultRow = $bookingRequest->requiresConsultationPick();
        $sessionDateTime = $bookingRequest->clientSlotSummary('session') ?? '—';
        $consultDateTime = $showConsultRow
            ? ($bookingRequest->clientSlotSummary('consult') ?? '—')
            : null;

        return view('user.requests.payment', [
            'bookingRequest' => $bookingRequest,
            'userDetail' => $userDetail,
            'artistName' => $bookingRequest->artistDisplayName(),
            'designTitle' => (string) ($tattoo->title ?? 'Design'),
            'totals' => $totals,
            'stripePublishableKey' => env('STRIPE_KEY', ''),
            'showConsultRow' => $showConsultRow,
            'sessionDateTimeLabel' => $showConsultRow ? 'Tattoo Date & Time' : 'Date & Time',
            'sessionDateTime' => $sessionDateTime,
            'consultDateTime' => $consultDateTime,
            'durationLabel' => $bookingRequest->checkoutDurationLabel($userDetail),
            'sizeLabel' => $bookingRequest->checkoutSizeLabel(),
            'locationLabel' => $bookingRequest->checkoutStudioLocation($userDetail),
            'priceEstimateLabel' => $bookingRequest->priceLabel(),
            'depositLabel' => $bookingRequest->checkoutDepositLabel($totals['deposit_meta']),
            'balanceLabel' => '€'.number_format($minBalance, 2).' - €'.number_format($maxBalance, 2),
        ]);
    }

    public function createPaymentIntent(Request $request, BookingRequest $bookingRequest): JsonResponse
    {
        $this->authorizeRequest($bookingRequest);

        if (!$bookingRequest->canPay()) {
            return response()->json(['message' => 'This request is not ready for payment.'], 422);
        }

        $request->validate([
            'cardholder_name' => ['required', 'string', 'max:255'],
        ]);

        $bookingRequest->load(['tattoo', 'artist.userDetail']);
        $userDetail = $bookingRequest->artist?->userDetail;
        $tattoo = $bookingRequest->tattoo;

        if (!$userDetail || !$tattoo) {
            return response()->json(['message' => 'Artist or design not found.'], 404);
        }

        $stripeSecret = env('STRIPE_SECRET');
        if (!$stripeSecret) {
            return response()->json(['message' => 'Stripe is not configured.'], 500);
        }

        $totals = $this->pricing->checkoutTotals($userDetail, (float) $tattoo->min_price);
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
                    'booking_request_id' => (string) $bookingRequest->id,
                    'user_id' => (string) $bookingRequest->user_id,
                    'artist_user_id' => (string) $bookingRequest->artist_id,
                    'tattoo_design_id' => (string) $tattoo->id,
                    'flow' => 'managed_request',
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

    public function confirmPayment(Request $request, BookingRequest $bookingRequest): JsonResponse
    {
        $this->authorizeRequest($bookingRequest);

        $validated = $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        if ($bookingRequest->isBooked() && $bookingRequest->booking_id) {
            $booking = Booking::query()->find($bookingRequest->booking_id);

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

            if ((int) ($intent->metadata['booking_request_id'] ?? 0) !== (int) $bookingRequest->id) {
                return response()->json(['message' => 'Payment does not match this request.'], 422);
            }

            $booking = $this->bookingService->createBookingFromRequest($bookingRequest, $intent);

            return response()->json([
                'saved' => true,
                'booking_id' => $booking->id,
                'booking_reference' => '#INK-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
                'redirect_url' => route('user.bookings.index'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Managed request payment confirm failed', [
                'booking_request_id' => $bookingRequest->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Unable to complete booking.',
            ], 422);
        }
    }

    private function authorizeRequest(BookingRequest $bookingRequest): void
    {
        if ((int) $bookingRequest->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function pickerViewData(BookingRequest $bookingRequest): array
    {
        $bookingRequest->load(['tattoo', 'artist.userDetail']);
        $offeredConsult = $bookingRequest->offeredSlotsMapForPicker('consult');
        $offeredSession = $bookingRequest->offeredSlotsMapForPicker('session');

        return [
            'bookingRequest' => $bookingRequest,
            'hasConsult' => $bookingRequest->requiresConsultationPick(),
            'offeredConsult' => $offeredConsult,
            'offeredSession' => $offeredSession,
            'initialPicker' => $bookingRequest->initialPickerMonthFromSlots($offeredConsult, $offeredSession),
            'todayYmd' => now()->format('Y-m-d'),
            'canPay' => $bookingRequest->canPay(),
            'savedSelections' => [
                'consult' => $bookingRequest->clientPickerSavedSelection('consult'),
                'session' => $bookingRequest->clientPickerSavedSelection('session'),
            ],
        ];
    }

    private function slotSummaryLine(BookingRequest $bookingRequest, string $kind): ?string
    {
        $range = $this->bookingService->firstClientRange($bookingRequest, $kind);
        if (!$range) {
            return null;
        }

        [$from, $to, $date] = $range;

        return $bookingRequest->formatTimeRangeLabel($from, $to).' · '.
            Carbon::createFromFormat('Y-m-d', $date)->format('l, M j, Y');
    }
}
