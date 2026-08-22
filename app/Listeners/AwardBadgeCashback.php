<?php

namespace App\Listeners;

use App\Contracts\Payments\CashbackPaymentProvider;
use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\CashbackTransaction;
use App\Payments\Payload\CashbackPaymentRequest;

class AwardBadgeCashback
{
    // NGN 300 stored in kobo so money values remain integer-based.
    private const AMOUNT_KOBO = 30000;

    public function __construct(private CashbackPaymentProvider $paymentProvider) {}

    /**
     * Create and settle one cashback transaction for each newly unlocked badge.
     */
    public function handle(BadgeUnlocked $event): void
    {
        // BadgeUnlocked intentionally carries the assessment payload only, so
        // the listener resolves the badge definition by its stable name.
        $badge = Badge::query()->where('name', $event->badge_name)->first();

        if (! $badge) {
            return;
        }

        $provider = (string) config('cashback.provider', 'payment_mock');
        $idempotencyKey = "badge-cashback:{$event->user->getKey()}:{$badge->getKey()}";

        // One transaction per user/badge preserves idempotency across replays.
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

        // Cashback is paid to the user's saved bank account. Missing payout
        // details are recorded as a failed transaction instead of crashing.
        $payoutAccount = $event->user->payoutAccount()->first();

        if (! $payoutAccount) {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => 'User does not have a payout account.',
            ]);

            return;
        }

        // The provider interface keeps the domain flow independent of whether
        // the active implementation is Flutterwave or the local mock.
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
