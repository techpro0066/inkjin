<?php

return [
    /*
    |--------------------------------------------------------------------------
    | InkJin API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the InkJin Tattoo API integration.
    |
    */

    'api_url' => env('INKJIN_API_URL', 'http://inkjinapi.mp8dev.reea.net'),

    'client_id' => env('INKJIN_CLIENT_ID', 'LFJH_8_XJTViKP-9K2HOtZf6tKlLP_U8vhO2jn14V8s'),

    'client_secret' => env('INKJIN_CLIENT_SECRET', 'S4#,B9Gc4hkEJ}4'),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */

    'token_cache_duration' => env('INKJIN_TOKEN_CACHE_DURATION', 3600),

    /*
    |--------------------------------------------------------------------------
    | Default Pagination
    |--------------------------------------------------------------------------
    */

    'default_per_page' => env('INKJIN_DEFAULT_PER_PAGE', 10),

    'max_per_page' => env('INKJIN_MAX_PER_PAGE', 9999),
];

