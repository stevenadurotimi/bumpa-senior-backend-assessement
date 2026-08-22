<?php

namespace App\Payments\Payload;

readonly class PaymentResult
{
    /**
     * @param array<string, mixed> $rawResponse
     */
    public function __construct(
        public bool $successful,
        public string $status,
        public ?string $providerReference = null,
        public ?string $failureReason = null,
        public array $rawResponse = [],
    ) {
    }

    /**
     * @param array<string, mixed> $rawResponse
     */
    public static function success(string $status, ?string $providerReference, array $rawResponse = []): self
    {
        return new self(true, $status, $providerReference, null, $rawResponse);
    }

    /**
     * @param array<string, mixed> $rawResponse
     */
    public static function failure(string $status, string $failureReason, array $rawResponse = []): self
    {
        return new self(false, $status, null, $failureReason, $rawResponse);
    }
}
