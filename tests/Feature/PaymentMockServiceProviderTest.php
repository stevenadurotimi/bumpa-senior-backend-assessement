<?php

use App\Contracts\Payments\CashbackPaymentProvider;
use App\Models\User;
use App\Payments\Payload\CashbackPaymentRequest;
use App\Payments\Providers\PaymentMockServiceProvider;

test('cashback payment provider resolves to the payment mock by default', function () {
    config(['cashback.provider' => 'payment_mock']);

    expect(app(CashbackPaymentProvider::class))
        ->toBeInstanceOf(PaymentMockServiceProvider::class);
});

test('payment mock provider returns a successful local transfer result', function () {
    $provider = new PaymentMockServiceProvider([
        'mode' => 'test',
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
        ->and($result->status)->toBe('successful')
        ->and($result->providerReference)->toBe('mock-transfer-cashback-1')
        ->and($result->rawResponse['status'])->toBe('success')
        ->and($result->rawResponse['data']['amount'])->toBe(300.0)
        ->and($result->rawResponse['data']['account_bank'])->toBe('044')
        ->and($result->rawResponse['data']['account_number'])->toBe('0690000031')
        ->and($result->rawResponse['data']['mode'])->toBe('test');
});
