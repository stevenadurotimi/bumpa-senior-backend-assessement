<?php

namespace App\Payments\Providers;

use App\Contracts\Payments\CashbackPaymentProvider;
use App\Payments\Payload\CashbackPaymentRequest;
use App\Payments\Payload\PaymentResult;

readonly class PaymentMockServiceProvider implements CashbackPaymentProvider
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private array $config) {}

    public function sendCashback(CashbackPaymentRequest $request): PaymentResult
    {
        $providerReference = "mock-transfer-{$request->reference}";

        return PaymentResult::success('successful', $providerReference, [
            'status' => 'success',
            'message' => 'Mock transfer created',
            'data' => [
                'id' => $providerReference,
                'reference' => $request->reference,
                'status' => 'successful',
                'amount' => $request->amountInNaira(),
                'currency' => $request->currency,
                'account_bank' => $request->bankCode,
                'account_number' => $request->accountNumber,
                'mode' => $this->config['mode'] ?? 'local',
            ],
        ]);
    }
}
