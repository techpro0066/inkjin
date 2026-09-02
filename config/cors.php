<?php

$allowedOrigins = array_values(array_filter(array_map(
    static fn (string $origin): string => rtrim(trim($origin), '/'),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'https://inkjin.com,https://www.inkjin.com'))
)));

$appUrl = rtrim((string) env('APP_URL', ''), '/');
$bookpayUrl = rtrim((string) env('BOOKPAY_PUBLIC_URL', $appUrl), '/');

foreach ([$appUrl, $bookpayUrl] as $origin) {
    if ($origin !== '' && ! in_array($origin, $allowedOrigins, true)) {
        $allowedOrigins[] = $origin;
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Public artist pages may be viewed on inkjin.com while APIs live on
    | bookpay.inkjin.com. Allow those origins with credentials so session /
    | CSRF cookies work for OTP and waitlist flows.
    |
    */

    'paths' => [
        'api/public/*',
    ],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Content-Type',
        'X-CSRF-TOKEN',
        'X-Requested-With',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
