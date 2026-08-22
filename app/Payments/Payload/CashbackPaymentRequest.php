<?php

namespace App\Payments\Payload;

use App\Models\User;

readonly class CashbackPaymentRequest
{
    /**
     * Provider-neutral payload for sending badge cashback to a bank account.
     *
     * @param  array<string, mixed>  $metadata
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
    ) {}

    public function amountInNaira(): float
    {
        // Cashback is stored internally as kobo but Flutterwave expects naira.
        return $this->amountKobo / 100;
    }
}
