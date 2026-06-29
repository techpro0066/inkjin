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

    public function buildIrisCheckoutUrl(int|string $orderCode): string
    {
        $base = rtrim((string) config('services.viva.checkout_base'), '/');
        $paymentMethod = (int) config('services.viva.iris_payment_method', 29);
        $color = ltrim((string) config('services.viva.checkout_color', '310f7a'), '#');

        return $base.'/web/checkout?ref='.$orderCode
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
     * @param  array<string, mixed>  $transaction
     */
    public function isTransactionPaid(array $transaction, int $expectedAmountCents, int|string $expectedOrderCode): bool
    {
        $statusId = (string) ($transaction['statusId'] ?? '');
        $amount = (int) ($transaction['amount'] ?? -1);
        $orderCode = (string) ($transaction['orderCode'] ?? '');

        return $statusId === 'F'
            && $amount === $expectedAmountCents
            && $orderCode === (string) $expectedOrderCode;
    }
}
