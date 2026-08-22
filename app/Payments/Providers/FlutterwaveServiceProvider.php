<?php

namespace App\Payments\Providers;

use App\Contracts\Payments\CashbackPaymentProvider;
use App\Payments\Payload\CashbackPaymentRequest;
use App\Payments\Payload\PaymentResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

readonly class FlutterwaveServiceProvider implements CashbackPaymentProvider
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private array $config) {}

    public function sendCashback(CashbackPaymentRequest $request): PaymentResult
    {
        try {
            $response = $this->flutterwaveRequest()
                ->post($this->endpoint('/transfers'), $this->payload($request));
        } catch (ConnectionException $exception) {
            return PaymentResult::failure('connection_failed', $exception->getMessage());
        }

        $body = $response->json() ?? [];

        if (! $response->successful() || data_get($body, 'status') !== 'success') {
            return PaymentResult::failure(
                (string) data_get($body, 'data.status', 'failed'),
                (string) data_get($body, 'message', 'Flutterwave transfer request failed.'),
                $body,
            );
        }

        return PaymentResult::success(
            (string) data_get($body, 'data.status', 'successful'),
            (string) data_get($body, 'data.id', data_get($body, 'data.reference', $request->reference)),
            $body,
        );
    }

    private function flutterwaveRequest(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => "Bearer {$this->config['secret_key']}",
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CashbackPaymentRequest $request): array
    {
        return array_filter([
            'account_bank' => $request->bankCode,
            'account_number' => $request->accountNumber,
            'amount' => $request->amountInNaira(),
            'currency' => $request->currency,
            'reference' => $request->reference,
            'callback_url' => $this->config['callback_url'],
            'narration' => $request->narration,
            'meta' => array_merge([
                'user_id' => $request->user->getKey(),
                'email' => $request->user->email,
            ], $request->metadata),
        ], fn (mixed $value) => $value !== null);
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) $this->config['base_url'], '/').$path;
    }
}
