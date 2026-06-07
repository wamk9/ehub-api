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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'mercadopago' => [
        'client_id' => env('MP_CLIENT_ID'),
        'client_secret' => env('MP_CLIENT_SECRET'),
        'redirect_uri' => env('MP_REDIRECT_URI'),
        'sandbox' => env('MP_SANDBOX', true),
    ],

    'stripe' => [
        'key' => env('STRIPE_PUBLISHABLE_KEY'),
        'secret' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'connect_client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
        'connect_redirect_uri' => env('STRIPE_CONNECT_REDIRECT_URI', 'http://localhost:8000/api/payment/gateway/callback/stripe_connect'),
        'connect_webhook_secret' => env('STRIPE_CONNECT_WEBHOOK_SECRET'),
    ],

];
