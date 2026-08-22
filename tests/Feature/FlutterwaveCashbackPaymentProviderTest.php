<?php

use App\Contracts\Payments\CashbackPaymentProvider;
use App\Models\User;
use App\Payments\Payload\CashbackPaymentRequest;
use App\Payments\Providers\FlutterwaveServiceProvider;
use Illuminate\Support\Facades\Http;

test('cashback payment provider resolves to flutterwave implementation', function () {
    config(['cashback.provider' => 'flutterwave']);

    expect(app(CashbackPaymentProvider::class))
        ->toBeInstanceOf(FlutterwaveServiceProvider::class);
});

test('flutterwave provider creates a v3 bank transfer with the secret key', function () {
    Http::fake([
        'https://api.flutterwave.test/v3/transfers' => Http::response([
            'status' => 'success',
            'message' => 'Transfer created',
            'data' => [
                'id' => 'trf_123',
                'reference' => 'cashback-1',
                'status' => 'NEW',
            ],
        ]),
    ]);

    $provider = new FlutterwaveServiceProvider([
        'secret_key' => 'FLWSECK_TEST-secret-key-X',
        'base_url' => 'https://api.flutterwave.test/v3',
        'callback_url' => null,
    ]);

    $result = $provider->sendCashback(new CashbackPaymentRequest(
        user: new User(['email' => 'customer@example.com']),
        amountKobo: 30000,
        reference: 'cashback-1',
        idempotencyKey: 'badge-cashback-1',
        bankCode: '044',
        accountNumber: '0690000031',
    ));

    expect($result->successful)->toBeTrue()
        ->and($result->providerReference)->toBe('trf_123');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.flutterwave.test/v3/transfers'
        && $request->hasHeader('Authorization', 'Bearer FLWSECK_TEST-secret-key-X')
        && $request['reference'] === 'cashback-1'
        && $request['currency'] === 'NGN'
        && $request['amount'] === 300.0
        && $request['account_bank'] === '044'
        && $request['account_number'] === '0690000031');
});
