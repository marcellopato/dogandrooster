<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pricing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the precious metals pricing system.
    |
    */

    // Quote expiry time in minutes
    'quote_expiry_minutes' => env('QUOTE_EXPIRY_MINUTES', 5),

    // Price tolerance in basis points (50 bp = 0.5%)
    'tolerance_bps' => env('PRICE_TOLERANCE_BPS', 50),

    // Payment webhook secret for HMAC verification
    'payment_webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', ''),

    // Fulfillment API configuration
    'fulfillment' => [
        'base_url' => env('FULFILLMENT_API_URL', 'http://localhost/api/mock-fulfillment'),
        'timeout' => env('FULFILLMENT_TIMEOUT', 10),
    ],
];
