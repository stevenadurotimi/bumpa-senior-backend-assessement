<?php

namespace App\Payments;

use App\Models\User;

readonly class CashbackPaymentRequest
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public User $user,
        public int $amountKobo,
        public string $reference,
        public string $idempotencyKey,
        public string $bankCode,
        public string $accountNumber,
        public string $currency = 'NGN',
        public string $narration = 'Bumpa badge cashback',
        public array $metadata = [],
    ) {
    }

    public function amountInNaira(): float
    {
        return $this->amountKobo / 100;
    }
}
