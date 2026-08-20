<?php

namespace App\Services;

use App\Models\BalanceCollection;
use App\Models\Booking;
use App\Models\PendingVivaPayment;
use App\Models\User;
use App\Models\UserDetail;
use App\Support\PaymentMethods;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class BalanceCollectionCheckoutService
{
    public function __construct(
        private readonly VivaPaymentsService $viva,
    ) {}

    public function amountCents(BalanceCollection $collection): int
    {
        return (int) round(((float) $collection->amount) * 100);
    }

    public function assertPayable(BalanceCollection $collection): void
    {
        if ($collection->isPaid()) {
            throw new RuntimeException('This payment has already been completed.');
        }

        if ($collection->collection_type !== BalanceCollection::TYPE_PAYMENT_LINK
            || ! $collection->payment_link_code) {
            throw new RuntimeException('This payment link is no longer available.');
        }
    }

    /**
     * @return array{client_secret: string, payment_intent_id: string, amount_cents: int, currency: string}
     */
    public function createStripeIntent(BalanceCollection $collection, string $cardholderName): array
    {
        $this->assertPayable($collection);
        $this->artistDetail($collection);

        $amountCents = $this->amountCents($collection);
        if ($amountCents < 30) {
            throw new RuntimeException('Payment amount is too small.');
        }

        $stripeSecret = env('STRIPE_SECRET');
        if (! $stripeSecret) {
            throw new RuntimeException('Stripe is not configured.');
        }

        Stripe::setApiKey($stripeSecret);
        $client = $this->client($collection);

        try {
            $intent = PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => strtolower((string) ($collection->currency ?: 'eur')) ?: 'eur',
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'always',
                ],
                'metadata' => [
                    'flow' => 'balance_collection',
                    'balance_collection_id' => (string) $collection->id,
                    'payment_link_code' => (string) $collection->payment_link_code,
                    'booking_id' => (string) $collection->booking_id,
                    'artist_user_id' => (string) $collection->artist_user_id,
                    'client_user_id' => (string) $client->id,
                    'cardholder_name' => $cardholderName,
                    'stripe_settlement' => 'platform',
                ],
            ]);
        } catch (ApiErrorException $e) {
            throw new RuntimeException($e->getMessage() ?: 'Unable to initialize payment.');
        }

        $collection->update(['payment_intent_id' => $intent->id]);

        return [
            'client_secret' => (string) $intent->client_secret,
            'payment_intent_id' => (string) $intent->id,
            'amount_cents' => $amountCents,
            'currency' => strtolower((string) ($intent->currency ?: 'eur')),
        ];
    }

    public function confirmStripePayment(BalanceCollection $collection, string $paymentIntentId, string $paymentMethod = 'card'): Booking
    {
        $stripeSecret = env('STRIPE_SECRET');
        if (! $stripeSecret) {
            throw new RuntimeException('Stripe is not configured.');
        }

        Stripe::setApiKey($stripeSecret);
        $intent = PaymentIntent::retrieve($paymentIntentId);
        if (! $intent || $intent->status !== 'succeeded') {
            throw new RuntimeException('Payment is not completed.');
        }

        if ((int) $intent->amount !== $this->amountCents($collection)) {
            throw new RuntimeException('Payment amount does not match this link.');
        }

        $metadata = $intent->metadata;
        $metaFlow = is_object($metadata) ? (string) ($metadata['flow'] ?? '') : (string) ($metadata['flow'] ?? '');
        $metaId = is_object($metadata) ? (string) ($metadata['balance_collection_id'] ?? '') : (string) ($metadata['balance_collection_id'] ?? '');
        $matchesMeta = $metaFlow === 'balance_collection' && $metaId === (string) $collection->id;
        $matchesStoredIntent = $collection->payment_intent_id
            && hash_equals((string) $collection->payment_intent_id, (string) $intent->id);

        if (! $matchesMeta && ! $matchesStoredIntent) {
            throw new RuntimeException('Payment does not match this balance link.');
        }

        return $this->markPaid(
            $collection,
            paymentIntentId: (string) $intent->id,
            vivaOrderCode: null,
            vivaTransactionId: null,
            paymentMethod: $paymentMethod,
            paymentProvider: 'stripe',
        );
    }

    /**
     * @return array{order_code: string, checkout_url: string, amount_cents: int, currency: string, expires_at: string}
     */
    public function createVivaOrder(BalanceCollection $collection): array
    {
        $this->assertPayable($collection);
        $userDetail = $this->artistDetail($collection);
        $client = $this->client($collection);
        $clientPhone = PaymentMethods::checkoutPhoneForIris(null, $client->phone_number);
        if (! PaymentMethods::showIrisTab($userDetail, $clientPhone)) {
            throw new RuntimeException('IRIS payment is not available for this checkout.');
        }

        $amountCents = $this->amountCents($collection);
        if ($amountCents < 30) {
            throw new RuntimeException('Payment amount is too small.');
        }

        if (! $this->viva->isConfigured()) {
            throw new RuntimeException('Viva Wallet is not configured.');
        }

        $existing = PendingVivaPayment::query()
            ->where('flow', PendingVivaPayment::FLOW_BALANCE_COLLECTION)
            ->where('reference_id', $collection->id)
            ->where('status', PendingVivaPayment::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->where('amount_cents', $amountCents)
            ->latest('id')
            ->first();

        if ($existing) {
            $existing->update(['status' => PendingVivaPayment::STATUS_CANCELLED]);
        }

        $booking = $this->booking($collection);
        $merchantTrns = 'inkjin:balance_collection:'.$collection->id.':'.Str::uuid();
        $order = $this->viva->createPaymentOrder(
            amountCents: $amountCents,
            merchantTrns: $merchantTrns,
            customerTrns: 'Bookpay remaining '.$booking->displayTitle(),
            customer: [
                'email' => (string) $client->email,
                'fullName' => trim($client->first_name.' '.$client->last_name) ?: (string) $client->email,
                'phone' => $this->viva->formatCustomerPhone(
                    PaymentMethods::normalizePhone((string) $clientPhone),
                    'GR'
                ),
                'countryCode' => 'GR',
            ],
            preselectIris: true,
            tags: ['inkjin', 'balance_collection', 'artist:'.$collection->artist_user_id],
        );

        $timeout = (int) config('services.viva.order_timeout_seconds', 300);
        $pending = PendingVivaPayment::create([
            'viva_order_code' => $order['order_code'],
            'flow' => PendingVivaPayment::FLOW_BALANCE_COLLECTION,
            'reference_id' => $collection->id,
            'artist_user_id' => $collection->artist_user_id,
            'client_user_id' => $client->id,
            'amount_cents' => $amountCents,
            'currency' => strtoupper((string) ($collection->currency ?: 'EUR')) ?: 'EUR',
            'merchant_trns' => $merchantTrns,
            'status' => PendingVivaPayment::STATUS_PENDING,
            'metadata' => [
                'payment_link_code' => $collection->payment_link_code,
                'booking_id' => $collection->booking_id,
            ],
            'expires_at' => now()->addSeconds($timeout),
        ]);

        $collection->update(['viva_order_code' => $pending->viva_order_code]);

        return [
            'order_code' => (string) $pending->viva_order_code,
            'checkout_url' => $this->viva->buildIrisCheckoutUrl($pending->viva_order_code),
            'amount_cents' => (int) $pending->amount_cents,
            'currency' => strtolower((string) $pending->currency),
            'expires_at' => $pending->expires_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function vivaStatus(BalanceCollection $collection, string $orderCode): array
    {
        $pending = PendingVivaPayment::query()
            ->where('viva_order_code', $orderCode)
            ->where('flow', PendingVivaPayment::FLOW_BALANCE_COLLECTION)
            ->where('reference_id', $collection->id)
            ->first();

        if (! $pending) {
            return ['status' => 'not_found'];
        }

        $redirectUrl = route('public.payment-link', ['code' => $collection->payment_link_code]);

        if ($pending->isPaid() || $collection->fresh()?->isPaid()) {
            return [
                'status' => 'paid',
                'redirect_url' => $redirectUrl,
            ];
        }

        if ($pending->status === PendingVivaPayment::STATUS_PENDING && $pending->expires_at->isPast()) {
            $pending->update(['status' => PendingVivaPayment::STATUS_EXPIRED]);

            return ['status' => 'expired'];
        }

        if (in_array($pending->status, [
            PendingVivaPayment::STATUS_FAILED,
            PendingVivaPayment::STATUS_CANCELLED,
            PendingVivaPayment::STATUS_EXPIRED,
        ], true)) {
            return ['status' => $pending->status];
        }

        return ['status' => 'pending'];
    }

    public function createFromVivaPending(PendingVivaPayment $pending, string $transactionId): Booking
    {
        $collection = BalanceCollection::query()->find($pending->reference_id);
        if (! $collection) {
            throw new RuntimeException('Balance collection not found.');
        }

        if ($collection->isPaid()) {
            $booking = $this->booking($collection);
            $this->completePaidBooking($booking, 0);

            return $booking;
        }

        return $this->markPaid(
            $collection,
            paymentIntentId: null,
            vivaOrderCode: (string) $pending->viva_order_code,
            vivaTransactionId: $transactionId,
            paymentMethod: 'iris',
            paymentProvider: 'viva_iris',
        );
    }

    /**
     * @return array{saved: bool, booking_id: int}
     */
    public function paymentResponse(Booking $booking): array
    {
        return [
            'saved' => true,
            'booking_id' => $booking->id,
        ];
    }

    private function markPaid(
        BalanceCollection $collection,
        ?string $paymentIntentId,
        ?string $vivaOrderCode,
        ?string $vivaTransactionId,
        string $paymentMethod,
        string $paymentProvider,
    ): Booking {
        return DB::transaction(function () use (
            $collection,
            $paymentIntentId,
            $vivaOrderCode,
            $vivaTransactionId,
            $paymentMethod,
            $paymentProvider,
        ) {
            /** @var BalanceCollection $locked */
            $locked = BalanceCollection::query()
                ->whereKey($collection->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isPaid()) {
                $booking = $this->booking($locked);
                $this->completePaidBooking($booking, 0);

                return $booking->fresh() ?? $booking;
            }

            $locked->update([
                'payment_provider' => $paymentProvider,
                'payment_method' => $paymentMethod,
                'payment_intent_id' => $paymentIntentId ?: $locked->payment_intent_id,
                'viva_order_code' => $vivaOrderCode ? (int) $vivaOrderCode : $locked->viva_order_code,
                'viva_transaction_id' => $vivaTransactionId ?: $locked->viva_transaction_id,
                'payment_status' => BalanceCollection::PAYMENT_STATUS_PAID,
                'paid_at' => now(),
                'status' => BalanceCollection::STATUS_PAID,
            ]);

            $booking = $this->booking($locked);
            $this->completePaidBooking($booking, (float) $locked->amount);

            return $booking->fresh() ?? $booking;
        });
    }

    private function completePaidBooking(Booking $booking, float $amount): void
    {
        $alreadyCompleted = (string) $booking->status === 'completed';
        $history = $booking->action_history ?? [];
        if (! $alreadyCompleted) {
            $history[] = [
                'action' => 'completed',
                'user_type' => 'client',
                'timestamp' => now()->toDateTimeString(),
                'source' => 'balance_collection_payment',
            ];
        }

        $payload = [
            'status' => 'completed',
            'completed_at' => $booking->completed_at ?: now(),
            'action_history' => $history,
            'remaining_amount_released' => true,
            'remaining_amount_released_at' => $booking->remaining_amount_released_at ?: now(),
            'full_amount_paid' => true,
            'completion_code_entered_at' => $booking->completion_code_entered_at ?: now(),
        ];

        if (! $alreadyCompleted && $amount > 0) {
            $alreadyPaid = (float) ($booking->total_amount_paid ?? $booking->deposit_amount ?? 0);
            $payload['total_amount_paid'] = round($alreadyPaid + $amount, 2);
        }

        $booking->update($payload);
    }

    private function booking(BalanceCollection $collection): Booking
    {
        $booking = $collection->relationLoaded('booking')
            ? $collection->booking
            : Booking::query()->with(['user', 'tattoo'])->find($collection->booking_id);

        if (! $booking) {
            throw new RuntimeException('Booking not found for this payment.');
        }

        return $booking;
    }

    private function client(BalanceCollection $collection): User
    {
        $client = $collection->relationLoaded('client')
            ? $collection->client
            : User::query()->find($collection->client_user_id);

        if (! $client && $collection->booking?->user) {
            $client = $collection->booking->user;
        }

        if (! $client) {
            throw new RuntimeException('Client not found for this payment.');
        }

        return $client;
    }

    private function artistDetail(BalanceCollection $collection): UserDetail
    {
        $artist = $collection->relationLoaded('artist')
            ? $collection->artist
            : User::query()->with('userDetail')->find($collection->artist_user_id);

        $detail = $artist?->userDetail;
        if (! $detail) {
            Log::warning('Balance collection artist details missing', [
                'collection_id' => $collection->id,
                'artist_user_id' => $collection->artist_user_id,
            ]);
            throw new RuntimeException('Artist payout details are not available.');
        }

        return $detail;
    }
}
