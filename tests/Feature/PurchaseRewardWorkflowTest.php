<?php

use App\Contracts\Payments\CashbackPaymentProvider;
use App\Events\PurchaseRecorded;
use App\Models\CashbackTransaction;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\Purchase;
use App\Models\User;
use App\Payments\Payload\CashbackPaymentRequest;
use App\Payments\Payload\PaymentResult;
use App\Support\RewardDefinitions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

class EndToEndCashbackPaymentProviderFake implements CashbackPaymentProvider
{
    /** @var list<CashbackPaymentRequest> */
    public array $requests = [];

    public function sendCashback(CashbackPaymentRequest $request): PaymentResult
    {
        $this->requests[] = $request;

        return PaymentResult::success('NEW', 'trf_end_to_end');
    }
}

function createPurchaseWorkflowDefinitions(): void
{
    foreach (RewardDefinitions::achievements() as $achievement) {
        Achievement::query()->create($achievement);
    }

    foreach (RewardDefinitions::badges() as $badge) {
        Badge::query()->create($badge);
    }
}

it('records purchases, unlocks rewards, awards cashback, and returns final API progress', function () {
    createPurchaseWorkflowDefinitions();

    $provider = new EndToEndCashbackPaymentProviderFake();
    app()->instance(CashbackPaymentProvider::class, $provider);

    $user = User::factory()->create();
    $user->payoutAccount()->create([
        'provider' => 'flutterwave',
        'bank_code' => '044',
        'account_number' => '0123456789',
        'account_name' => 'Jane Doe',
        'currency' => 'NGN',
    ]);

    foreach (range(1, 5) as $purchaseNumber) {
        Purchase::query()->create([
            'user_id' => $user->id,
            'reference' => "purchase-{$purchaseNumber}",
            'amount' => 5000,
        ]);
    }

    PurchaseRecorded::dispatch($user->purchases()->latest('id')->firstOrFail());

    expect($user->achievements()->orderBy('threshold')->pluck('name')->all())->toBe([
        RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE,
        RewardDefinitions::ACHIEVEMENT_5_PURCHASES,
    ])
        ->and($user->badges()->pluck('name')->all())->toBe([RewardDefinitions::BADGE_BEGINNER])
        ->and($provider->requests)->toHaveCount(1)
        ->and($provider->requests[0]->amountKobo)->toBe(30000)
        ->and(CashbackTransaction::query()->sole()->status)->toBe('successful');

    $this->getJson("/users/{$user->id}/achievements")
        ->assertOk()
        ->assertExactJson([
            'unlocked_achievements' => [
                RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE,
                RewardDefinitions::ACHIEVEMENT_5_PURCHASES,
            ],
            'next_available_achievements' => [RewardDefinitions::ACHIEVEMENT_10_PURCHASES],
            'current_badge' => RewardDefinitions::BADGE_BEGINNER,
            'next_badge' => RewardDefinitions::BADGE_INTERMEDIATE,
            'remaining_to_unlock_next_badge' => 2,
        ]);
});
