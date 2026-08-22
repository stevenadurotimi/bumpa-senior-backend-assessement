<?php

use App\Payments\Providers\FlutterwaveServiceProvider;
use App\Payments\Providers\PaymentMockServiceProvider;

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

    'provider' => env('CASHBACK_PAYMENT_PROVIDER', 'payment_mock'),

    'providers' => [
        // Default provider for local testing and reviewer demos. No secrets or
        // external HTTP calls are required.
        'payment_mock' => [
            'handler' => PaymentMockServiceProvider::class,
            'mode' => env('PAYMENT_MOCK_MODE', 'local'),
        ],

        // Real Flutterwave v3 transfer provider. Enable by setting
        // CASHBACK_PAYMENT_PROVIDER=flutterwave and providing a secret key.
        'flutterwave' => [
            'handler' => FlutterwaveServiceProvider::class,
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3'),
            'callback_url' => env('FLUTTERWAVE_CALLBACK_URL'),
        ],
    ],

];
