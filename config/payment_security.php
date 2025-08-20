<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security settings for payment processing to ensure
    | safe handling of payment data and prevent fraudulent activities.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for different payment operations to prevent abuse.
    |
    */
    'rate_limits' => [
        'card_payment' => [
            'max_attempts' => 5,
            'decay_minutes' => 10,
        ],
        'crypto_payment' => [
            'max_attempts' => 10,
            'decay_minutes' => 5,
        ],
        'payment_confirmation' => [
            'max_attempts' => 3,
            'decay_minutes' => 15,
        ],
        'payment_retry' => [
            'max_attempts' => 3,
            'decay_minutes' => 15,
        ],
        'payment_switch' => [
            'max_attempts' => 5,
            'decay_minutes' => 10,
        ],
        'webhook' => [
            'max_attempts' => 20,
            'decay_minutes' => 1,
        ],
        'callback' => [
            'max_attempts' => 10,
            'decay_minutes' => 5,
        ],
        'admin_config' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],
        'admin_test' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
        'admin_status' => [
            'max_attempts' => 20,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Amount Validation
    |--------------------------------------------------------------------------
    |
    | Configure validation rules for payment amounts.
    |
    */
    'amount_validation' => [
        'min_amount' => 0.01,
        'max_amount' => 100000.00,
        'max_decimal_places' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Currencies
    |--------------------------------------------------------------------------
    |
    | List of currencies that are allowed for payment processing.
    |
    */
    'allowed_currencies' => [
        'USD',
        'EUR',
        'GBP',
        'CAD',
        'AUD',
        'BTC',
        'ETH',
        'USDT'
    ],

    /*
    |--------------------------------------------------------------------------
    | Suspicious User Agent Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns in User-Agent strings that should be blocked for security.
    |
    */
    'blocked_user_agents' => [
        'bot',
        'crawler',
        'spider',
        'scraper',
        'curl',
        'wget',
        'python-requests'
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configuration for secure session handling during payment processing.
    |
    */
    'session' => [
        'registration_timeout_minutes' => 30,
        'payment_timeout_hours' => 2,
        'encryption_required' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTPS Requirements
    |--------------------------------------------------------------------------
    |
    | Force HTTPS for payment-related requests in production.
    |
    */
    'require_https' => [
        'enabled' => env('APP_ENV', 'production') !== 'local',
        'exclude_routes' => [
            // Routes that don't require HTTPS (for testing)
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Security
    |--------------------------------------------------------------------------
    |
    | Security settings for webhook endpoints.
    |
    */
    'webhook' => [
        'signature_header' => 'X-UniPayment-Signature',
        'signature_algorithm' => 'sha256',
        'require_signature' => true,
        'log_all_requests' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure what payment security events should be logged.
    |
    */
    'logging' => [
        'log_all_payment_requests' => true,
        'log_security_violations' => true,
        'log_rate_limit_hits' => true,
        'log_webhook_requests' => true,
        'sensitive_fields' => [
            'card_number',
            'cvc',
            'api_key',
            'webhook_secret'
        ],
    ],
];
