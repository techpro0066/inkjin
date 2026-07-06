<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo 'url: '.url('/api/public/booking/payment/viva/order').PHP_EOL;
echo 'route: '.route('public.booking.payment.viva.order').PHP_EOL;
