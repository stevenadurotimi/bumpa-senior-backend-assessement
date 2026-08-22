<?php

use App\Payments\FlutterwaveServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Cashback Payment Provider
    |--------------------------------------------------------------------------
    |
    | The domain code depends on App\Contracts\Payments\CashbackPaymentProvider.
    | This value decides which concrete provider implementation is bound to
    | that contract. Add new providers here as new integrations are built.
    |
    */

    'provider' => env('CASHBACK_PAYMENT_PROVIDER', 'flutterwave'),

    'providers' => [
        'flutterwave' => [
            'handler' => FlutterwaveServiceProvider::class,
            'client_id' => env('FLUTTERWAVE_CLIENT_ID'),
            'client_secret' => env('FLUTTERWAVE_CLIENT_SECRET'),
            'token_url' => env('FLUTTERWAVE_TOKEN_URL', 'https://idp.flutterwave.com/realms/flutterwave/protocol/openid-connect/token'),
            'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://developersandbox-api.flutterwave.com'),
            'callback_url' => env('FLUTTERWAVE_CALLBACK_URL'),
            'scenario_key' => env('FLUTTERWAVE_SCENARIO_KEY'),
        ],
    ],

];
