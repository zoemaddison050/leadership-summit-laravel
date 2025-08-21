<?php

return [

    /*
    |--------------------------------------------------------------------------
    | UniPayment Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for UniPayment integration.
    | You can configure your API credentials and other settings here.
    |
    */

    'app_id' => env('UNIPAYMENT_APP_ID'),

    'client_id' => env('UNIPAYMENT_CLIENT_ID'),

    'client_secret' => env('UNIPAYMENT_CLIENT_SECRET'),

    'environment' => env('UNIPAYMENT_ENVIRONMENT', 'sandbox'),

    'webhook_secret' => env('UNIPAYMENT_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    |
    | The base URLs for UniPayment API endpoints based on environment.
    |
    */

    'api_urls' => [
        'sandbox' => 'https://sandbox-api.unipayment.io',
        'production' => 'https://api.unipayment.io',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | Default payment configuration settings.
    |
    */

    'default_currency' => env('UNIPAYMENT_DEFAULT_CURRENCY', 'USD'),

    'supported_currencies' => [
        'USD',
        'EUR',
        'GBP',
        'CAD',
        'AUD',
        'JPY'
    ],

    'processing_fee_percentage' => env('UNIPAYMENT_PROCESSING_FEE', 2.9),

    'minimum_amount' => env('UNIPAYMENT_MIN_AMOUNT', 1.00),

    'maximum_amount' => env('UNIPAYMENT_MAX_AMOUNT', 10000.00),

    /*
    |--------------------------------------------------------------------------
    | Timeout Settings
    |--------------------------------------------------------------------------
    |
    | API request timeout settings in seconds.
    |
    */

    'timeout' => env('UNIPAYMENT_TIMEOUT', 30),

    'connect_timeout' => env('UNIPAYMENT_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging for UniPayment API requests and responses.
    |
    */

    'logging' => [
        'enabled' => env('UNIPAYMENT_LOGGING_ENABLED', true),
        'level' => env('UNIPAYMENT_LOG_LEVEL', 'info'),
    ],

];
