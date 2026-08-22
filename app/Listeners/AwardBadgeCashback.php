<?php

namespace App\Listeners;

use App\Contracts\Payments\CashbackPaymentProvider;
use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\CashbackTransaction;
use App\Payments\CashbackPaymentRequest;

class AwardBadgeCashback
{
    private const AMOUNT_KOBO = 30000;

    public function __construct(private CashbackPaymentProvider $paymentProvider) {}

    public function handle(BadgeUnlocked $event): void
    {
        $badge = Badge::query()->where('name', $event->badge_name)->first();

        if (! $badge) {
            return;
        }

        $provider = (string) config('cashback.provider', 'flutterwave');
        $idempotencyKey = "badge-cashback:{$event->user->getKey()}:{$badge->getKey()}";

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
