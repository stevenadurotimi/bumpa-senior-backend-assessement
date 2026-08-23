<?php

namespace App\Listeners;

use App\Contracts\Payments\CashbackPaymentProvider;
use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\CashbackTransaction;
use App\Payments\Payload\CashbackPaymentRequest;

class AwardBadgeCashback
{
    private const AMOUNT_KOBO = 30000;

    public function __construct(private CashbackPaymentProvider $paymentProvider) {}

    /**
     * Create and settle one cashback transaction for each newly unlocked badge.
     *
     * Cashback is NGN 300 stored in kobo so money values remain integer-based.
     * BadgeUnlocked carries the assessment payload only, so this listener
     * resolves the badge definition by stable name, records an idempotent
     * transaction, pays the user's saved payout account, and delegates provider
     * details to the configured CashbackPaymentProvider implementation.
     */
    public function handle(BadgeUnlocked $event): void
    {
        $badge = Badge::query()->where('name', $event->badge_name)->first();

        if (! $badge) {
            return;
        }

        $provider = (string) config('cashback.provider', 'payment_mock');
        $idempotencyKey = "badge-cashback:{$event->user->getKey()}:{$badge->getKey()}";

        /*
         * One transaction per user/badge preserves idempotency across replays.
         * Successful transactions are never sent to the provider again.
         */
        $transaction = CashbackTransaction::query()->firstOrCreate(
            [
                'user_id' => $event->user->getKey(),
                'badge_id' => $badge->getKey(),
            ],
            [
                'amount_kobo' => self::AMOUNT_KOBO,
                'provider' => $provider,
                'idempotency_key' => $idempotencyKey,
                'status' => 'pending',
            ],
        );

        if ($transaction->status === 'successful') {
            return;
        }

        $payoutAccount = $event->user->payoutAccount()->first();

        if (! $payoutAccount) {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => 'User does not have a payout account.',
            ]);

            return;
        }

        $result = $this->paymentProvider->sendCashback(new CashbackPaymentRequest(
            user: $event->user,
            amountKobo: self::AMOUNT_KOBO,
            reference: "cashback-{$event->user->getKey()}-{$badge->getKey()}",
            idempotencyKey: $idempotencyKey,
            bankCode: $payoutAccount->bank_code,
            accountNumber: $payoutAccount->account_number,
            currency: $payoutAccount->currency,
            metadata: [
                'badge_id' => $badge->getKey(),
                'badge_name' => $badge->name,
            ],
        ));

        $transaction->update([
            'user_payout_account_id' => $payoutAccount->getKey(),
            'status' => $result->successful ? 'successful' : 'failed',
            'provider_reference' => $result->providerReference,
            'failure_reason' => $result->failureReason,
            'metadata' => [
                'provider_status' => $result->status,
                'raw_response' => $result->rawResponse,
            ],
        ]);
    }
}
