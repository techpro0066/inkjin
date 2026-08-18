<?php

namespace App\Services;

use App\Exceptions\GoogleCalendarEventRequiredException;
use App\Mail\PaymentLinkArtistBookedMail;
use App\Mail\PaymentLinkClientBookedMail;
use App\Mail\PaymentLinkSessionDetailsMail;
use App\Mail\UserWelcomeMail;
use App\Models\Booking;
use App\Models\PaymentLink;
use App\Models\PendingVivaPayment;
use App\Models\User;
use App\Models\UserDetail;
use App\Support\PaymentMethods;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentLinkCheckoutService
{
    public function __construct(
        private readonly GoogleCalendarBookingSyncService $calendarSync,
        private readonly VivaPaymentsService $viva,
        private readonly BookingCheckoutPricingService $pricing,
    ) {}

    /**
     * @return array{
     *     base_amount: float,
     *     deposit: float,
     *     platform_fee: float,
     *     subtotal: float,
     *     tax_amount: float,
     *     tax_rate: float|null,
     *     tax_country: string|null,
     *     tax_label: string|null,
     *     total_due: float,
     *     booking_fee: array
     * }
     */
    public function checkoutTotals(PaymentLink $link, ?User $client = null, ?string $phone = null): array
    {
        $userDetail = $this->artistDetail($link);
        $resolvedPhone = trim((string) ($phone ?: $link->payer_phone ?: $client?->phone_number ?: ''));

        return $this->pricing->totalsForAmount(
            $userDetail,
            (float) $link->amount,
            $resolvedPhone !== '' ? $resolvedPhone : null
        );
    }

    public function amountCents(PaymentLink $link, ?User $client = null, ?string $phone = null): int
    {
        return (int) round(((float) $this->checkoutTotals($link, $client, $phone)['total_due']) * 100);
    }

    /**
     * @return array{client_secret: string, payment_intent_id: string, amount_cents: int, currency: string}
     */
    public function createStripeIntent(PaymentLink $link, User $client, string $cardholderName): array
    {
        $this->assertPayable($link);
        $userDetail = $this->artistDetail($link);
        $this->calendarSync->assertReadyForPayment($userDetail);

        $amountCents = $this->amountCents($link, $client);
        if ($amountCents < 30) {
            throw new RuntimeException('Payment amount is too small.');
        }

        $stripeSecret = env('STRIPE_SECRET');
        if (! $stripeSecret) {
            throw new RuntimeException('Stripe is not configured.');
        }

        Stripe::setApiKey($stripeSecret);
        $totals = $this->checkoutTotals($link, $client);

        try {
            $intent = PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => 'eur',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'flow' => 'payment_link',
                    'payment_link_id' => (string) $link->id,
                    'payment_link_code' => (string) $link->code,
                    'artist_user_id' => (string) $link->artist_id,
                    'client_user_id' => (string) $client->id,
                    'cardholder_name' => $cardholderName,
                    'stripe_settlement' => 'platform',
                    'base_amount' => (string) $totals['base_amount'],
                    'platform_fee' => (string) $totals['platform_fee'],
                    'tax_amount' => (string) $totals['tax_amount'],
                    'total_due' => (string) $totals['total_due'],
                ],
            ]);
        } catch (ApiErrorException $e) {
            throw new RuntimeException($e->getMessage() ?: 'Unable to initialize payment.');
        }

        $link->update(['payment_intent_id' => $intent->id]);

        return [
            'client_secret' => (string) $intent->client_secret,
            'payment_intent_id' => (string) $intent->id,
            'amount_cents' => $amountCents,
            'currency' => 'eur',
        ];
    }

    public function confirmStripePayment(PaymentLink $link, User $client, string $paymentIntentId, string $paymentMethod = 'card'): Booking
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

        $existing = Booking::query()->where('payment_intent_id', $intent->id)->first();
        if ($existing) {
            $this->markLinkPaid($link, $existing, (string) $intent->id, null, $paymentMethod);

            return $existing;
        }

        $this->assertPayable($link);

        $expectedCents = $this->amountCents($link, $client);
        if ((int) $intent->amount !== $expectedCents) {
            throw new RuntimeException('Payment amount does not match this link.');
        }

        return $this->createConfirmedBooking(
            $link,
            $client,
            paymentIntentId: (string) $intent->id,
            vivaOrderCode: null,
            vivaTransactionId: null,
            paymentMethod: $paymentMethod,
            currency: strtoupper((string) ($intent->currency ?: 'eur')),
        );
    }

    /**
     * @return array{order_code: string, checkout_url: string, amount_cents: int, currency: string, expires_at: string}
     */
    public function createVivaOrder(PaymentLink $link, User $client): array
    {
        $this->assertPayable($link);
        $userDetail = $this->artistDetail($link);
        $this->calendarSync->assertReadyForPayment($userDetail);

        $clientPhone = PaymentMethods::checkoutPhoneForIris($link->payer_phone, $client->phone_number);
        if (! PaymentMethods::showIrisTab($userDetail, $clientPhone)) {
            throw new RuntimeException('IRIS payment is not available for this checkout.');
        }

        $amountCents = $this->amountCents($link, $client);
        if ($amountCents < 30) {
            throw new RuntimeException('Payment amount is too small.');
        }

        if (! $this->viva->isConfigured()) {
            throw new RuntimeException('Viva Wallet is not configured.');
        }

        $existing = PendingVivaPayment::query()
            ->where('flow', PendingVivaPayment::FLOW_PAYMENT_LINK)
            ->where('reference_id', $link->id)
            ->where('status', PendingVivaPayment::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->where('amount_cents', $amountCents)
            ->latest('id')
            ->first();

        if ($existing) {
            $existing->update(['status' => PendingVivaPayment::STATUS_CANCELLED]);
        }

        $merchantTrns = 'inkjin:payment_link:'.$link->id.':'.Str::uuid();
        $order = $this->viva->createPaymentOrder(
            amountCents: $amountCents,
            merchantTrns: $merchantTrns,
            customerTrns: 'Bookpay '.$link->title,
            customer: [
                'email' => (string) $client->email,
                'fullName' => trim((string) ($link->payer_name ?: trim($client->first_name.' '.$client->last_name))) ?: (string) $client->email,
                'phone' => $this->viva->formatCustomerPhone(
                    PaymentMethods::normalizePhone((string) $clientPhone),
                    'GR'
                ),
                'countryCode' => 'GR',
            ],
            preselectIris: true,
            tags: ['inkjin', 'payment_link', 'artist:'.$link->artist_id],
        );

        $timeout = (int) config('services.viva.order_timeout_seconds', 300);
        $pending = PendingVivaPayment::create([
            'viva_order_code' => $order['order_code'],
            'flow' => PendingVivaPayment::FLOW_PAYMENT_LINK,
            'reference_id' => $link->id,
            'artist_user_id' => $link->artist_id,
            'client_user_id' => $client->id,
            'amount_cents' => $amountCents,
            'currency' => 'EUR',
            'merchant_trns' => $merchantTrns,
            'status' => PendingVivaPayment::STATUS_PENDING,
            'metadata' => [
                'payment_link_code' => $link->code,
                'payer_email' => $link->payer_email,
            ],
            'expires_at' => now()->addSeconds($timeout),
        ]);

        $link->update(['viva_order_code' => (string) $pending->viva_order_code]);

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
    public function vivaStatus(PaymentLink $link, User $client, string $orderCode): array
    {
        $pending = PendingVivaPayment::query()
            ->where('viva_order_code', $orderCode)
            ->where('client_user_id', $client->id)
            ->where('flow', PendingVivaPayment::FLOW_PAYMENT_LINK)
            ->where('reference_id', $link->id)
            ->first();

        if (! $pending) {
            return ['status' => 'not_found'];
        }

        $redirectUrl = route('public.payment-link', ['code' => $link->code]);

        if ($pending->isPaid() || $link->fresh()?->isPaid()) {
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
        $link = PaymentLink::query()->find($pending->reference_id);
        if (! $link) {
            throw new RuntimeException('Payment link not found.');
        }

        $existing = Booking::query()->where('viva_order_code', $pending->viva_order_code)->first();
        if ($existing) {
            $this->markLinkPaid($link, $existing, null, (string) $pending->viva_order_code, 'iris');

            return $existing;
        }

        if ($link->isPaid() && $link->booking_id) {
            $booking = Booking::query()->find($link->booking_id);
            if ($booking) {
                return $booking;
            }
        }

        $client = User::query()->find($pending->client_user_id);
        if (! $client) {
            throw new RuntimeException('Booking user not found.');
        }

        return $this->createConfirmedBooking(
            $link,
            $client,
            paymentIntentId: null,
            vivaOrderCode: (string) $pending->viva_order_code,
            vivaTransactionId: $transactionId,
            paymentMethod: 'iris',
            currency: 'EUR',
        );
    }

    public function createConfirmedBooking(
        PaymentLink $link,
        User $client,
        ?string $paymentIntentId,
        ?string $vivaOrderCode,
        ?string $vivaTransactionId,
        string $paymentMethod,
        string $currency = 'EUR',
    ): Booking {
        $userDetail = $this->artistDetail($link);
        $this->calendarSync->assertReadyForPayment($userDetail);

        $client->syncPhoneNumber($link->payer_phone);

        return DB::transaction(function () use (
            $link,
            $client,
            $userDetail,
            $paymentIntentId,
            $vivaOrderCode,
            $vivaTransactionId,
            $paymentMethod,
            $currency
        ) {
            /** @var PaymentLink $locked */
            $locked = PaymentLink::query()->whereKey($link->id)->lockForUpdate()->firstOrFail();
            if ($locked->isPaid() && $locked->booking_id) {
                $existing = Booking::query()->find($locked->booking_id);
                if ($existing) {
                    return $existing;
                }
            }

            if ($paymentIntentId) {
                $byIntent = Booking::query()->where('payment_intent_id', $paymentIntentId)->first();
                if ($byIntent) {
                    $this->markLinkPaid($locked, $byIntent, $paymentIntentId, $vivaOrderCode, $paymentMethod);

                    return $byIntent;
                }
            }

            if ($vivaOrderCode) {
                $byViva = Booking::query()->where('viva_order_code', $vivaOrderCode)->first();
                if ($byViva) {
                    $this->markLinkPaid($locked, $byViva, $paymentIntentId, $vivaOrderCode, $paymentMethod);

                    return $byViva;
                }
            }

            $slot = $this->resolveSlot($locked, $userDetail);
            $totals = $this->checkoutTotals($locked, $client);
            $amount = (float) $totals['base_amount'];
            $isFullPayment = $locked->payment_type === 'full';

            $booking = Booking::create([
                'user_id' => $client->id,
                'artist_user_id' => $locked->artist_id,
                'tattoo_id' => null,
                'booking_type' => 'custom',
                'custom_tattoo_details' => [
                    'payment_link_id' => $locked->id,
                    'payment_link_code' => $locked->code,
                    'title' => $locked->title,
                    'reference' => $locked->title,
                    'source' => 'payment_link',
                    'estimated_price' => $locked->total_price !== null
                        ? (float) $locked->total_price
                        : $amount,
                ],
                'cancellation_window_hours' => CancellationService::hoursFromArtistWindow($userDetail->cancellation_window ?? '48h'),
                'booking_date' => $slot['booking_date'],
                'start_time_utc' => $slot['start_time_utc'],
                'end_time_utc' => $slot['end_time_utc'],
                'timezone' => $slot['timezone'],
                'has_consultation' => false,
                'status' => 'confirmed',
                'payment_provider' => $vivaOrderCode ? 'viva_iris' : 'stripe',
                'payment_intent_id' => $paymentIntentId,
                'viva_order_code' => $vivaOrderCode ? (int) $vivaOrderCode : null,
                'viva_transaction_id' => $vivaTransactionId,
                'payment_status' => 'paid',
                'deposit_amount' => $amount,
                'full_amount_paid' => $isFullPayment,
                'platform_fee' => $totals['platform_fee'],
                'tax_amount' => $totals['tax_amount'],
                'tax_rate' => $totals['tax_rate'],
                'tax_country' => $totals['tax_country'],
                'tax_label' => $totals['tax_label'],
                'total_amount_paid' => $totals['total_due'],
                'currency' => strtoupper($currency),
                'notes' => 'Payment link: '.$locked->title,
            ]);

            if (! $booking->completion_code) {
                do {
                    $code = strtoupper(Str::random(6));
                } while (Booking::query()->where('completion_code', $code)->exists());
                $booking->completion_code = $code;
                $booking->save();
            }

            try {
                $this->calendarSync->syncForBooking($booking);
            } catch (GoogleCalendarEventRequiredException $e) {
                $this->calendarSync->abortFailedCalendarBooking($booking, $e->getMessage());

                throw $e;
            }

            $this->markLinkPaid($locked, $booking, $paymentIntentId, $vivaOrderCode, $paymentMethod);
            $this->sendEmails($booking, $client, $userDetail, $locked);

            return $booking;
        });
    }

    /**
     * @return array{booking_id: int, booking_reference: string, post_booking_login_url: string}
     */
    public function bookingResponse(Booking $booking, User $client): array
    {
        return [
            'saved' => true,
            'booking_id' => $booking->id,
            'booking_reference' => '#INK-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'post_booking_login_url' => URL::temporarySignedRoute(
                'user.post-booking.access',
                now()->addDays(14),
                ['user' => $client->id, 'booking' => $booking->id]
            ),
        ];
    }

    public function assertPayable(PaymentLink $link): void
    {
        if ($link->isPaid()) {
            throw new RuntimeException('This payment has already been completed.');
        }

        if ($link->isExpired() || $link->status !== PaymentLink::STATUS_ACTIVE) {
            throw new RuntimeException('This payment link has expired.');
        }
    }

    private function artistDetail(PaymentLink $link): UserDetail
    {
        $link->loadMissing(['artist.userDetail.user']);
        $userDetail = $link->artist?->userDetail;
        if (! $userDetail) {
            throw new RuntimeException('Artist not found.');
        }

        return $userDetail;
    }

    /**
     * @return array{booking_date: string, start_time_utc: string, end_time_utc: string, timezone: string}
     */
    private function resolveSlot(PaymentLink $link, UserDetail $userDetail): array
    {
        $timezone = $userDetail->timezone ?: 'UTC';
        $scheduling = $link->scheduling_type ?: ($userDetail->scheduling_type ?? 'managed');
        $durationMinutes = $this->durationMinutes($link);

        if ($scheduling === 'auto') {
            $ymd = trim((string) $link->slot_ymd);
            $time = trim((string) $link->slot_time);
            if ($ymd === '' || $time === '') {
                throw new RuntimeException('Please choose a date and time.');
            }

            $startLocal = $this->parseLocalDateTime($ymd, $time, $timezone);
        } else {
            if (! $link->date_time) {
                throw new RuntimeException('This payment link is missing a date and time.');
            }

            $startLocal = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $link->date_time->format('Y-m-d H:i:s'),
                $timezone
            );
        }

        if (! $startLocal) {
            throw new RuntimeException('Invalid appointment date or time.');
        }

        $endLocal = $startLocal->copy()->addMinutes($durationMinutes);

        return [
            'booking_date' => $startLocal->format('Y-m-d'),
            'start_time_utc' => $startLocal->copy()->utc()->format('H:i:s'),
            'end_time_utc' => $endLocal->copy()->utc()->format('H:i:s'),
            'timezone' => $timezone,
        ];
    }

    private function parseLocalDateTime(string $ymd, string $time, string $timezone): Carbon
    {
        $time = trim($time);
        $formats = ['Y-m-d H:i', 'Y-m-d G:i', 'Y-m-d g:i A', 'Y-m-d g:iA', 'Y-m-d H:i:s'];
        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $ymd.' '.$time, $timezone);
                if ($parsed !== false) {
                    return $parsed;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return Carbon::parse($ymd.' '.$time, $timezone);
    }

    private function durationMinutes(PaymentLink $link): int
    {
        return match ((string) $link->session_duration) {
            '2h' => 120,
            '3h' => 180,
            '4h' => 240,
            'half-day' => 240,
            'full-day' => 480,
            default => 180,
        };
    }

    private function markLinkPaid(
        PaymentLink $link,
        Booking $booking,
        ?string $paymentIntentId,
        ?string $vivaOrderCode,
        string $paymentMethod,
    ): void {
        $link->update([
            'status' => PaymentLink::STATUS_PAID,
            'booking_id' => $booking->id,
            'payment_intent_id' => $paymentIntentId ?: $link->payment_intent_id,
            'viva_order_code' => $vivaOrderCode ?: $link->viva_order_code,
            'payment_method' => $paymentMethod,
            'paid_at' => $link->paid_at ?: now(),
        ]);
    }

    private function sendEmails(Booking $booking, User $client, UserDetail $userDetail, PaymentLink $link): void
    {
        $clientEmail = (string) ($client->email ?? '');
        $artistEmail = (string) ($userDetail->user->email ?? '');

        if ($clientEmail !== '') {
            try {
                Mail::to($clientEmail)->send(new PaymentLinkClientBookedMail(
                    $booking,
                    $client,
                    $userDetail,
                    $link,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send payment-link client confirmation email', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                Mail::to($clientEmail)->send(new PaymentLinkSessionDetailsMail(
                    $booking,
                    $client,
                    $userDetail,
                    $link,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send payment-link session details email', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($artistEmail !== '') {
            try {
                Mail::to($artistEmail)->send(new PaymentLinkArtistBookedMail(
                    $booking,
                    $client,
                    $userDetail,
                    $link,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to send artist payment-link booking notification', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($client->role === 'user' && $clientEmail !== '') {
            $isFirstPaidBooking = Booking::query()
                ->where('user_id', $client->id)
                ->where('payment_status', 'paid')
                ->count() === 1;

            if ($isFirstPaidBooking) {
                try {
                    $recipientName = trim(implode(' ', array_filter([
                        (string) ($client->first_name ?? ''),
                        (string) ($client->last_name ?? ''),
                    ])));
                    Mail::to($clientEmail)->send(new UserWelcomeMail(
                        URL::temporarySignedRoute(
                            'user.post-booking.access',
                            now()->addDays(14),
                            ['user' => $client->id, 'booking' => $booking->id]
                        ),
                        $recipientName,
                    ));
                } catch (\Throwable $e) {
                    Log::error('Failed to send user welcome email for payment-link booking', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
