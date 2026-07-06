<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$viva = app(App\Services\VivaPaymentsService::class);

echo "=== Viva connectivity test ===".PHP_EOL.PHP_EOL;

try {
    $token = $viva->getAccessToken();
    echo "[OK] OAuth token received (".strlen($token)." chars)".PHP_EOL;
} catch (Throwable $e) {
    echo "[FAIL] OAuth: ".$e->getMessage().PHP_EOL;
    exit(1);
}

try {
    $order = $viva->createPaymentOrder(
        amountCents: 100,
        merchantTrns: 'inkjin:test:'.uniqid(),
        customerTrns: 'InkJin test payment',
        customer: [
            'email' => 'test@example.com',
            'fullName' => 'Test User',
            'phone' => '6912345678',
            'countryCode' => 'GR',
        ],
        preselectIris: false,
    );
    $url = $viva->buildIrisCheckoutUrl($order['order_code']);
    echo "[OK] Order created: ".$order['order_code'].PHP_EOL;
    echo "     Checkout URL: ".$url.PHP_EOL;
} catch (Throwable $e) {
    echo "[FAIL] Create order: ".$e->getMessage().PHP_EOL;
    exit(1);
}

echo PHP_EOL."Open the checkout URL in your browser and pay with a demo card.".PHP_EOL;
