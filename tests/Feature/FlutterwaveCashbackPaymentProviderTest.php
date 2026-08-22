<?php

use App\Contracts\Payments\CashbackPaymentProvider;
use App\Models\User;
use App\Payments\CashbackPaymentRequest;
use App\Payments\FlutterwaveServiceProvider;
use Illuminate\Support\Facades\Http;

test('cashback payment provider resolves to flutterwave implementation', function () {
    config(['cashback.provider' => 'flutterwave']);

    expect(app(CashbackPaymentProvider::class))
        ->toBeInstanceOf(FlutterwaveServiceProvider::class);
});

test('flutterwave provider requests an access token and creates a direct bank transfer', function () {
    Http::fake([
        'https://idp.flutterwave.test/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 600,
        ]),
        'https://api.flutterwave.test/direct-transfers' => Http::response([
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
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
        'token_url' => 'https://idp.flutterwave.test/token',
        'base_url' => 'https://api.flutterwave.test',
        'callback_url' => null,
        'scenario_key' => null,
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

    Http::assertSent(fn ($request) => $request->url() === 'https://idp.flutterwave.test/token'
        && $request['client_id'] === 'client-id'
        && $request['client_secret'] === 'client-secret'
        && $request['grant_type'] === 'client_credentials');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.flutterwave.test/direct-transfers'
        && $request->hasHeader('Authorization', 'Bearer test-access-token')
        && $request->hasHeader('X-Idempotency-Key', 'badge-cashback-1')
        && $request['action'] === 'instant'
        && $request['type'] === 'bank'
        && $request['reference'] === 'cashback-1'
        && $request['payment_instruction']['source_currency'] === 'NGN'
        && $request['payment_instruction']['amount']['value'] === 300.0
        && $request['payment_instruction']['recipient']['bank']['code'] === '044'
        && $request['payment_instruction']['recipient']['bank']['account_number'] === '0690000031');
});
