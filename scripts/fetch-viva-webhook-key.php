<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $result = app(App\Services\VivaPaymentsService::class)->retrieveWebhookVerificationKey();
    echo json_encode($result, JSON_PRETTY_PRINT).PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: '.$e->getMessage().PHP_EOL);
    exit(1);
}
