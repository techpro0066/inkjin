<?php

return [
    'api_key' => env('STREAM_API_KEY'),
    'api_secret' => env('STREAM_API_SECRET'),
    'token_ttl' => (int) env('STREAM_TOKEN_TTL', 86400),
    'timeout' => (float) env('STREAM_CHAT_TIMEOUT', 10),
    // Group rapid inbox messages into one email (artist→client and client/studio→artist).
    'client_message_email_debounce_minutes' => (int) env('STREAM_CLIENT_MESSAGE_EMAIL_DEBOUNCE_MINUTES', 10),
];
