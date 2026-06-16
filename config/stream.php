<?php

return [
    'api_key' => env('STREAM_API_KEY'),
    'api_secret' => env('STREAM_API_SECRET'),
    'token_ttl' => (int) env('STREAM_TOKEN_TTL', 86400),
];
