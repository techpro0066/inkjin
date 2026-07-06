<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VivaPaymentsService
{
    public function isConfigured(): bool
    {
        return (bool) config('services.viva.client_id')
            && (bool) config('services.viva.client_secret')
            && (bool) config('services.viva.source_code');
    }

    public function getAccessToken(): string
    {
        $cacheKey = 'viva_oauth_token_'.md5((string) config('services.viva.client_id'));

        return Cache::remember($cacheKey, (int) config('services.viva.token_cache_ttl', 3300), function () {
            $response = Http::asForm()->post(
                rtrim((string) config('services.viva.accounts_base'), '/').'/connect/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('services.viva.client_id'),
                    'client_secret' => config('services.viva.client_secret'),
                ]
            );

            if (! $response->successful()) {
                Log::error('Viva OAuth token request failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                throw new RuntimeException('Unable to authenticate with Viva Wallet.');
            }

            $token = (string) ($response->json('access_token') ?? '');
            if ($token === '') {
                throw new RuntimeException('Viva OAuth token was empty.');
            }

            return $token;
        });
    }

    /**
     * @param  array{email: string, fullName: string, phone: string, countryCode: string}  $customer
     * @param  array<int, string>  $tags
     * @return array{order_code: int, raw: array<string, mixed>}
     */
    public function createPaymentOrder(
        int $amountCents,
        string $merchantTrns,
        string $customerTrns,
        array $customer,
        bool $preselectIris = true,
        array $tags = [],
    ): array {
        $body = [
            'amount' => $amountCents,
            'sourceCode' => (string) config('services.viva.source_code'),
            'customerTrns' => $customerTrns,
            'merchantTrns' => $merchantTrns,
            'paymentTimeout' => (int) config('services.viva.order_timeout_seconds', 300),
            'allowRecurring' => false,
            'maxInstallments' => 0,
            'customer' => [
                'email' => $customer['email'],
                'fullName' => $customer['fullName'],
                'phone' => $customer['phone'],
                'countryCode' => $customer['countryCode'],
                'requestLang' => $customer['countryCode'] === 'GR' ? 'el-GR' : 'en-GB',
            ],
        ];

        if ($preselectIris) {
            $body['preselectedPaymentMethod'] = 'IRIS';
            $body['disableWallet'] = true;
        }

        if ($tags !== []) {
            $body['tags'] = $tags;
        }

        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->post(rtrim((string) config('services.viva.api_base'), '/').'/checkout/v2/orders', $body);

        if (! $response->successful()) {
            Log::error('Viva create order failed', [
                'status' => $response->status(),
                'body' => $response->json(),
                'merchant_trns' => $merchantTrns,
            ]);

            throw new RuntimeException($response->json('message') ?: 'Unable to create Viva payment order.');
        }

        $orderCode = (int) ($response->json('orderCode') ?? 0);
        if ($orderCode <= 0) {
            throw new RuntimeException('Viva did not return an order code.');
        }

        return [
            'order_code' => $orderCode,
            'raw' => $response->json(),
        ];
    }

    public function formatCustomerPhone(string $normalizedPhone, string $countryCode): string
    {
        if ($countryCode === 'GR') {
            if (str_starts_with($normalizedPhone, '+30')) {
                return substr($normalizedPhone, 3);
            }

            if (preg_match('/^30(\d{10})$/', $normalizedPhone, $matches)) {
                return $matches[1];
            }
        }

        return ltrim($normalizedPhone, '+');
    }

    public function buildIrisCheckoutUrl(int|string $orderCode): string
    {
        $base = rtrim((string) config('services.viva.checkout_base'), '/');
        $paymentMethod = (int) config('services.viva.iris_payment_method', 29);
        $color = ltrim((string) config('services.viva.checkout_color', '310f7a'), '#');
        $ref = (string) $orderCode;

        return $base.'/web/checkout?ref='.$ref
            .'&paymentMethod='.$paymentMethod
            .'&color='.$color
            .'&lang=el-GR';
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveTransaction(string $transactionId): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->get(rtrim((string) config('services.viva.api_base'), '/').'/checkout/v2/transactions/'.$transactionId);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to retrieve Viva transaction.');
        }

        return $response->json();
    }

    /**
     * Viva order creation uses cents; retrieve/webhook responses use major units (e.g. 22.0 for €22).
     */
    public function amountMatchesCents(float|int|string|null $amount, int $expectedAmountCents): bool
    {
        if ($amount === null || $amount === '') {
            return false;
        }

        $raw = (float) $amount;
        $asCentsDirect = (int) round($raw);
        $asCentsFromMajor = (int) round($raw * 100);

        return $asCentsDirect === $expectedAmountCents
            || $asCentsFromMajor === $expectedAmountCents;
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    public function isTransactionPaid(array $transaction, int $expectedAmountCents, int|string $expectedOrderCode): bool
    {
        $statusId = (string) ($transaction['statusId'] ?? $transaction['StatusId'] ?? '');
        $amount = $transaction['amount'] ?? $transaction['Amount'] ?? null;
        $orderCode = (string) ($transaction['orderCode'] ?? $transaction['OrderCode'] ?? '');

        $paid = $statusId === 'F'
            && $this->amountMatchesCents($amount, $expectedAmountCents)
            && $orderCode === (string) $expectedOrderCode;

        if (! $paid) {
            Log::warning('Viva transaction verification mismatch', [
                'status_id' => $statusId,
                'amount' => $amount,
                'expected_amount_cents' => $expectedAmountCents,
                'order_code' => $orderCode,
                'expected_order_code' => (string) $expectedOrderCode,
            ]);
        }

        return $paid;
    }

    /**
     * Retrieve the webhook verification key (Settings → API Access → Webhooks → Verify).
     *
     * @return array{Key: string}
     */
    public function retrieveWebhookVerificationKey(): array
    {
        $merchantId = (string) config('services.viva.merchant_id');
        $apiKey = (string) config('services.viva.api_key');

        if ($merchantId === '' || $apiKey === '') {
            throw new RuntimeException('Set VIVA_MERCHANT_ID and VIVA_API_KEY to retrieve the webhook verification key.');
        }

        $bases = array_values(array_unique(array_filter([
            rtrim((string) config('services.viva.checkout_base'), '/'),
            rtrim((string) config('services.viva.api_base'), '/'),
        ])));

        $lastStatus = null;
        $lastBody = null;
        $lastUrl = null;

        foreach ($bases as $base) {
            $url = $base.'/api/messages/config/token';
            $response = Http::withBasicAuth($merchantId, $apiKey)
                ->acceptJson()
                ->get($url);

            if ($response->successful()) {
                $key = (string) ($response->json('Key') ?? '');

                if ($key !== '') {
                    return ['Key' => $key];
                }
            }

            $lastStatus = $response->status();
            $lastBody = $response->json() ?? $response->body();
            $lastUrl = $url;
        }

        Log::error('Viva webhook key request failed', [
            'status' => $lastStatus,
            'body' => $lastBody,
            'url' => $lastUrl,
        ]);

        throw new RuntimeException('Unable to retrieve Viva webhook verification key.');
    }
}
