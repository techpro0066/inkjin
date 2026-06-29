<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'place_api_key' => env('GOOGLE_PLACE_API_KEY'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'connect' => [
            'default_country' => env('STRIPE_CONNECT_DEFAULT_COUNTRY', 'GR'),
            'locale' => env('STRIPE_CONNECT_LOCALE', 'en-US'),
            // Artists always onboard as Stripe individual accounts (skip business type UI).
            'force_individual' => env('STRIPE_CONNECT_FORCE_INDIVIDUAL', true),
            // Hide business profile step in embedded UI; values are prefilled via API.
            'exclude_business_details' => env('STRIPE_CONNECT_EXCLUDE_BUSINESS_DETAILS', true),
            // Bank country/currency chosen in InkJin before Stripe onboarding.
            'exclude_bank_country' => env('STRIPE_CONNECT_EXCLUDE_BANK_COUNTRY', true),
            // Public base URL for artist pages sent to Stripe business_profile.url (required when APP_URL is localhost).
            'business_url_base' => env('STRIPE_CONNECT_BUSINESS_URL_BASE'),
            // Website URL always sent to Stripe for individual connected accounts (business_profile.url).
            'individual_business_website_url' => env('STRIPE_CONNECT_INDIVIDUAL_BUSINESS_WEBSITE_URL', 'https://www.inkjin.com'),
            // How long to cache Stripe CountrySpec payout countries (seconds).
            'countries_cache_ttl' => env('STRIPE_CONNECT_COUNTRIES_CACHE_TTL', 86400),
            // Skip Stripe SMS/popup auth before embedded onboarding forms (application liability).
            'disable_user_authentication' => env('STRIPE_CONNECT_DISABLE_USER_AUTHENTICATION', true),
            // Collect all onboarding requirements up front (personal details, identity upload, bank).
            'onboarding_fields' => env('STRIPE_CONNECT_ONBOARDING_FIELDS', 'eventually_due'),
            'future_requirements' => env('STRIPE_CONNECT_FUTURE_REQUIREMENTS', 'include'),
            // Default industry (MCC) for artist connected accounts — 7299 = personal/other services (tattoo).
            'artist_mcc' => env('STRIPE_CONNECT_ARTIST_MCC', '7299'),
            // Optional token for the public /stripe/delete-account dev tool (required in production).
            'dev_delete_token' => env('STRIPE_CONNECT_DEV_DELETE_TOKEN'),
            'appearance' => [
                'variables' => [
                    'colorPrimary' => '#000000',
                    'buttonPrimaryColorBackground' => '#000000',
                    'buttonPrimaryColorBorder' => '#000000',
                    'buttonPrimaryColorText' => '#FFFFFF',
                ],
            ],
        ],
    ],

    'viva' => [
        'client_id' => env('VIVA_CLIENT_ID'),
        'client_secret' => env('VIVA_CLIENT_SECRET'),
        'merchant_id' => env('VIVA_MERCHANT_ID'),
        'api_key' => env('VIVA_API_KEY'),
        'source_code' => env('VIVA_SOURCE_CODE', '2461'),
        'webhook_key' => env('VIVA_WEBHOOK_KEY'),
        'env' => env('VIVA_ENV', 'production'),
        'accounts_base' => env('VIVA_ACCOUNTS_BASE', 'https://accounts.vivapayments.com'),
        'api_base' => env('VIVA_API_BASE', 'https://api.vivapayments.com'),
        'checkout_base' => env('VIVA_CHECKOUT_BASE', 'https://www.vivapayments.com'),
        'iris_payment_method' => 29,
        'order_timeout_seconds' => (int) env('VIVA_ORDER_TIMEOUT_SECONDS', 300),
        'token_cache_ttl' => (int) env('VIVA_TOKEN_CACHE_TTL', 3300),
        'checkout_color' => env('VIVA_CHECKOUT_COLOR', '310f7a'),
    ],

];
