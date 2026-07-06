<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\CustomRequest;
use App\Models\PendingVivaPayment;
use App\Models\User;
use App\Models\UserDetail;
use App\Support\PaymentMethods;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

class VivaCheckoutService
{
    public function __construct(
        private readonly VivaPaymentsService $viva,
        private readonly BookingCheckoutPricingService $pricing,
        private readonly VivaBookingConfirmationService $confirmation,
    ) {}

    public function ensureConfigured(): void
    {
        if (! $this->viva->isConfigured()) {
            throw new RuntimeException('Viva Wallet is not configured.');
        }
    }

    /**
     * @return array{order_code: int, checkout_url: string, amount_cents: int, currency: string, expires_at: string}
     */
    public function createOrReuseOrderForBookingRequest(BookingRequest $bookingRequest, User $client): array
    {
        $this->ensureConfigured();
        $bookingRequest->load(['tattoo', 'artist.userDetail']);
        $userDetail = $bookingRequest->artist?->userDetail;
        $tattoo = $bookingRequest->tattoo;

        if (! $userDetail || ! $tattoo) {
            throw new RuntimeException('Artist or design not found.');
        }

        if (! PaymentMethods::showIrisTab($userDetail, $client->phone_number)) {
            throw new RuntimeException('IRIS payment is not available for this checkout.');
        }

        $totals = $this->pricing->checkoutTotals($userDetail, (float) $tattoo->min_price);
        $amountCents = (int) round($totals['total_due'] * 100);

        if ($amountCents < 30) {
            throw new RuntimeException('Payment amount is too small.');
        }

        $existing = PendingVivaPayment::query()
            ->where('flow', PendingVivaPayment::FLOW_MANAGED_REQUEST)
            ->where('reference_id', $bookingRequest->id)
            ->where('status', PendingVivaPayment::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->where('amount_cents', $amountCents)
            ->latest('id')
            ->first();

        if ($existing) {
            $existing->update(['status' => PendingVivaPayment::STATUS_CANCELLED]);
        }

        $merchantTrns = 'inkjin:managed_request:'.$bookingRequest->id.':'.Str::uuid();
        $order = $this->viva->createPaymentOrder(
            amountCents: $amountCents,
            merchantTrns: $merchantTrns,
            customerTrns: 'InkJin booking '.$bookingRequest->referenceLabel(),
            customer: $this->customerPayload($client),
            preselectIris: true,
            tags: ['inkjin', 'managed_request', 'artist:'.$bookingRequest->artist_id],
        );

        $timeout = (int) config('services.viva.order_timeout_seconds', 300);
        $pending = PendingVivaPayment::create([
            'viva_order_code' => $order['order_code'],
            'flow' => PendingVivaPayment::FLOW_MANAGED_REQUEST,
            'reference_id' => $bookingRequest->id,
            'artist_user_id' => $bookingRequest->artist_id,
            'client_user_id' => $client->id,
            'amount_cents' => $amountCents,
            'currency' => 'EUR',
            'merchant_trns' => $merchantTrns,
            'status' => PendingVivaPayment::STATUS_PENDING,
            'expires_at' => now()->addSeconds($timeout),
        ]);

        return $this->orderResponse($pending);
    }

    /**
     * @return array{order_code: int, checkout_url: string, amount_cents: int, currency: string, expires_at: string}
     */
    public function createOrReuseOrderForCustomRequest(CustomRequest $customRequest, User $client): array
    {
        $this->ensureConfigured();
        $customRequest->load(['artist.userDetail']);
        $userDetail = $customRequest->artist?->userDetail;

        if (! $userDetail) {
            throw new RuntimeException('Artist not found.');
        }

        if (! PaymentMethods::showIrisTab($userDetail, $client->phone_number)) {
            throw new RuntimeException('IRIS payment is not available for this checkout.');
        }

        $totals = $this->pricing->checkoutTotals($userDetail, $customRequest->checkoutPriceAmount());
        $amountCents = (int) round($totals['total_due'] * 100);

        if ($amountCents < 30) {
            throw new RuntimeException('Payment amount is too small.');
        }

        $existing = PendingVivaPayment::query()
            ->where('flow', PendingVivaPayment::FLOW_CUSTOM_REQUEST)
            ->where('reference_id', $customRequest->id)
            ->where('status', PendingVivaPayment::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->where('amount_cents', $amountCents)
            ->latest('id')
            ->first();

        if ($existing) {
            $existing->update(['status' => PendingVivaPayment::STATUS_CANCELLED]);
        }

        $merchantTrns = 'inkjin:custom_request:'.$customRequest->id.':'.Str::uuid();
        $order = $this->viva->createPaymentOrder(
            amountCents: $amountCents,
            merchantTrns: $merchantTrns,
            customerTrns: 'InkJin custom request #'.$customRequest->id,
            customer: $this->customerPayload($client),
            preselectIris: true,
            tags: ['inkjin', 'custom_request', 'artist:'.$customRequest->artist_id],
        );

        $timeout = (int) config('services.viva.order_timeout_seconds', 300);
        $pending = PendingVivaPayment::create([
            'viva_order_code' => $order['order_code'],
            'flow' => PendingVivaPayment::FLOW_CUSTOM_REQUEST,
            'reference_id' => $customRequest->id,
            'artist_user_id' => $customRequest->artist_id,
            'client_user_id' => $client->id,
            'amount_cents' => $amountCents,
            'currency' => 'EUR',
            'merchant_trns' => $merchantTrns,
            'status' => PendingVivaPayment::STATUS_PENDING,
            'expires_at' => now()->addSeconds($timeout),
        ]);

        return $this->orderResponse($pending);
    }

    /**
     * @param  array<string, mixed>  $bookingPayload
     * @return array{order_code: int, checkout_url: string, amount_cents: int, currency: string, expires_at: string}
     */
    public function createOrReuseOrderForPublicBooking(
        UserDetail $userDetail,
        User $client,
        string $artistUsername,
        string $tattooSlug,
        array $bookingPayload,
        int $amountCents,
    ): array {
        $this->ensureConfigured();

        $clientPhone = PaymentMethods::checkoutPhoneForIris(
            $bookingPayload['phone'] ?? null,
            $client->phone_number
        );

        if (! PaymentMethods::showIrisTab($userDetail, $clientPhone)) {
            throw new RuntimeException('IRIS payment is not available for this checkout.');
        }

        if ($amountCents < 30) {
            throw new RuntimeException('Payment amount is too small.');
        }

        $metadata = [
            'artist_username' => $artistUsername,
            'tattoo_slug' => $tattooSlug,
            'booking_payload' => $bookingPayload,
        ];

        $existing = PendingVivaPayment::query()
            ->where('flow', PendingVivaPayment::FLOW_PUBLIC_BOOKING)
            ->where('client_user_id', $client->id)
            ->where('artist_user_id', $userDetail->user_id)
            ->where('status', PendingVivaPayment::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->where('amount_cents', $amountCents)
            ->whereJsonContains('metadata->tattoo_slug', $tattooSlug)
            ->latest('id')
            ->first();

        if ($existing) {
            $existing->update(['status' => PendingVivaPayment::STATUS_CANCELLED]);
        }

        $merchantTrns = 'inkjin:public_booking:'.$userDetail->user_id.':'.Str::uuid();
        $order = $this->viva->createPaymentOrder(
            amountCents: $amountCents,
            merchantTrns: $merchantTrns,
            customerTrns: 'InkJin tattoo booking',
            customer: $this->customerPayload($client, $bookingPayload['phone'] ?? null),
            preselectIris: true,
            tags: ['inkjin', 'public_booking', 'artist:'.$userDetail->user_id],
        );

        $timeout = (int) config('services.viva.order_timeout_seconds', 300);
        $pending = PendingVivaPayment::create([
            'viva_order_code' => $order['order_code'],
            'flow' => PendingVivaPayment::FLOW_PUBLIC_BOOKING,
            'reference_id' => $client->id,
            'artist_user_id' => $userDetail->user_id,
            'client_user_id' => $client->id,
            'amount_cents' => $amountCents,
            'currency' => 'EUR',
            'merchant_trns' => $merchantTrns,
            'status' => PendingVivaPayment::STATUS_PENDING,
            'metadata' => $metadata,
            'expires_at' => now()->addSeconds($timeout),
        ]);

        return $this->orderResponse($pending);
    }

    /**
     * @return array{status: string, redirect_url?: string, booking_reference?: string}
     */
    public function statusPayload(PendingVivaPayment $pending, string $redirectUrl): array
    {
        if ($pending->isPaid()) {
            $booking = $this->confirmation->findBookingForPending($pending);

            return [
                'status' => 'paid',
                'redirect_url' => $redirectUrl,
                'booking_reference' => $booking
                    ? '#INK-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT)
                    : null,
            ];
        }

        if ($pending->status === PendingVivaPayment::STATUS_PENDING && $pending->expires_at->isPast()) {
            $pending->update(['status' => PendingVivaPayment::STATUS_EXPIRED]);

            return ['status' => 'expired'];
        }

        if (in_array($pending->status, [PendingVivaPayment::STATUS_FAILED, PendingVivaPayment::STATUS_CANCELLED, PendingVivaPayment::STATUS_EXPIRED], true)) {
            return ['status' => $pending->status];
        }

        return ['status' => 'pending'];
    }

    public function findPendingByOrderCode(int|string $orderCode): ?PendingVivaPayment
    {
        return PendingVivaPayment::query()
            ->where('viva_order_code', $orderCode)
            ->first();
    }

    /**
     * @return array{booking_payload: array<string, mixed>, order_code: int|string}|null
     */
    public function publicBookingRestorePayload(
        int|string $orderCode,
        string $artistUsername,
        string $tattooSlug,
    ): ?array {
        $pending = $this->findPendingByOrderCode($orderCode);
        if (! $pending || $pending->flow !== PendingVivaPayment::FLOW_PUBLIC_BOOKING) {
            return null;
        }

        $metadata = is_array($pending->metadata) ? $pending->metadata : [];
        if ((string) ($metadata['artist_username'] ?? '') !== $artistUsername) {
            return null;
        }
        if ((string) ($metadata['tattoo_slug'] ?? '') !== $tattooSlug) {
            return null;
        }

        $bookingPayload = $metadata['booking_payload'] ?? null;
        if (! is_array($bookingPayload)) {
            return null;
        }

        return [
            'booking_payload' => $bookingPayload,
            'order_code' => $pending->viva_order_code,
        ];
    }

    public function successRedirectUrl(PendingVivaPayment $pending): string
    {
        if ($pending->flow === PendingVivaPayment::FLOW_PUBLIC_BOOKING) {
            $booking = Booking::query()
                ->where('viva_order_code', $pending->viva_order_code)
                ->first();
            $client = $pending->client;
            if ($booking && $client) {
                return URL::temporarySignedRoute(
                    'user.post-booking.access',
                    now()->addDays(14),
                    ['user' => $client->id, 'booking' => $booking->id]
                );
            }

            return $this->failureRedirectUrl($pending);
        }

        if ($pending->flow === PendingVivaPayment::FLOW_MANAGED_REQUEST) {
            return route('user.bookings.index');
        }

        if ($pending->flow === PendingVivaPayment::FLOW_CUSTOM_REQUEST) {
            return route('user.bookings.index');
        }

        return route('login');
    }

    public function failureRedirectUrl(PendingVivaPayment $pending): string
    {
        if ($pending->flow === PendingVivaPayment::FLOW_PUBLIC_BOOKING) {
            $metadata = is_array($pending->metadata) ? $pending->metadata : [];
            $artistUsername = (string) ($metadata['artist_username'] ?? '');
            $tattooSlug = (string) ($metadata['tattoo_slug'] ?? '');

            if ($artistUsername !== '' && $tattooSlug !== '') {
                return route('public.tattoo.book', [
                    'user_name' => $artistUsername,
                    'tattoo_slug' => $tattooSlug,
                ]).'?viva=fail&s='.urlencode((string) $pending->viva_order_code);
            }
        }

        if ($pending->flow === PendingVivaPayment::FLOW_MANAGED_REQUEST) {
            $request = BookingRequest::query()->find($pending->reference_id);
            if ($request) {
                return route('user.requests.payment', $request)
                    .'?viva=fail&s='.urlencode((string) $pending->viva_order_code);
            }
        }

        if ($pending->flow === PendingVivaPayment::FLOW_CUSTOM_REQUEST) {
            $request = CustomRequest::query()->find($pending->reference_id);
            if ($request) {
                return route('user.custom-requests.payment', $request)
                    .'?viva=fail&s='.urlencode((string) $pending->viva_order_code);
            }
        }

        return route('login').'?viva=fail';
    }

    /**
     * @return array{order_code: int, checkout_url: string, amount_cents: int, currency: string, expires_at: string}
     */
    private function orderResponse(PendingVivaPayment $pending): array
    {
        return [
            'order_code' => (int) $pending->viva_order_code,
            'checkout_url' => $this->viva->buildIrisCheckoutUrl($pending->viva_order_code),
            'amount_cents' => (int) $pending->amount_cents,
            'currency' => strtolower((string) $pending->currency),
            'expires_at' => $pending->expires_at->toIso8601String(),
        ];
    }

    /**
     * @return array{email: string, fullName: string, phone: string, countryCode: string}
     */
    private function customerPayload(User $client, ?string $phoneOverride = null): array
    {
        $phone = trim((string) ($phoneOverride ?: $client->phone_number ?: ''));
        if ($phone === '') {
            throw new RuntimeException('A phone number is required for IRIS payment.');
        }

        $normalized = PaymentMethods::normalizePhone($phone);
        $countryCode = PaymentMethods::isGreekClientPhone($normalized) ? 'GR' : 'GB';

        return [
            'email' => (string) $client->email,
            'fullName' => trim(implode(' ', array_filter([
                (string) ($client->first_name ?? ''),
                (string) ($client->last_name ?? ''),
            ]))) ?: (string) $client->email,
            'phone' => $this->viva->formatCustomerPhone($normalized, $countryCode),
            'countryCode' => $countryCode,
        ];
    }
}
