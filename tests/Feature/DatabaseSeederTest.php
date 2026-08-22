<?php

use App\Models\CashbackTransaction;
use App\Models\Purchase;
use App\Models\User;
use App\Models\UserPayoutAccount;
use App\Services\AchievementService;
use App\Support\RewardDefinitions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds useful demo data for testing the reward workflow', function () {
    $this->seed();

    $newUser = User::query()->where('email', 'new.customer@example.com')->sole();
    $activeUser = User::query()->where('email', 'active.customer@example.com')->sole();
    $missingPayoutUser = User::query()->where('email', 'missing.payout@example.com')->sole();

    expect(User::query()->count())->toBe(6)
        ->and(UserPayoutAccount::query()->count())->toBe(5)
        ->and(Purchase::query()->count())->toBe(37)
        ->and(CashbackTransaction::query()->count())->toBe(6)
        ->and($newUser->payoutAccount)->not->toBeNull()
        ->and($missingPayoutUser->payoutAccount)->toBeNull()
        ->and($missingPayoutUser->cashbackTransactions()->sole()->status)->toBe('failed');

    expect(app(AchievementService::class)->progressFor($activeUser))->toBe([
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
