<?php

namespace App\Payments;

use App\Contracts\Payments\CashbackPaymentProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

readonly class FlutterwaveServiceProvider implements CashbackPaymentProvider
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private array $config) {}

    public function sendCashback(CashbackPaymentRequest $request): PaymentResult
    {
        try {
            $accessToken = $this->accessToken();

            $response = $this->flutterwaveRequest($accessToken, $request->idempotencyKey)
                ->post($this->endpoint('/direct-transfers'), $this->payload($request));
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
            (string) data_get($body, 'data.id', data_get($body, 'data.reference')),
            $body,
        );
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    private function accessToken(): string
    {
        $response = Http::asForm()->post((string) $this->config['token_url'], [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'grant_type' => 'client_credentials',
        ]);

        return (string) $response->throw()->json('access_token');
    }

    private function flutterwaveRequest(string $accessToken, string $idempotencyKey): PendingRequest
    {
        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json',
            'X-Trace-Id' => (string) Str::uuid(),
            'X-Idempotency-Key' => $idempotencyKey,
        ];

        if ($this->config['scenario_key']) {
            $headers['X-Scenario-Key'] = $this->config['scenario_key'];
        }

        return Http::withHeaders($headers);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CashbackPaymentRequest $request): array
    {
        return array_filter([
            'action' => 'instant',
            'type' => 'bank',
            'callback_url' => $this->config['callback_url'],
            'narration' => $request->narration,
            'reference' => $request->reference,
            'payment_instruction' => [
                'source_currency' => $request->currency,
                'destination_currency' => $request->currency,
                'amount' => [
                    'applies_to' => 'destination_currency',
                    'value' => $request->amountInNaira(),
                ],
                'recipient' => [
                    'bank' => [
                        'code' => $request->bankCode,
                        'account_number' => $request->accountNumber,
                    ],
                ],
            ],
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
