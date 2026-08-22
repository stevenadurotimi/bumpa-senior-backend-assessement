<?php

use App\Models\Achievement;
use App\Models\Badge;
use App\Models\User;
use App\Services\AchievementService;
use App\Support\RewardDefinitions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedAchievementProgressDefinitions(): void
{
    foreach (RewardDefinitions::achievements() as $achievement) {
        Achievement::query()->create($achievement);
    }

    foreach (RewardDefinitions::badges() as $badge) {
        Badge::query()->create($badge);
    }
}

it('returns progress for a new user', function () {
    seedAchievementProgressDefinitions();

    $user = User::factory()->create();

    $progress = app(AchievementService::class)->progressFor($user);

    expect($progress)->toBe([
        'unlocked_achievements' => [],
        'next_available_achievements' => [RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE],
        'current_badge' => '',
        'next_badge' => RewardDefinitions::BADGE_BEGINNER,
        'remaining_to_unlock_next_badge' => 1,
    ]);
});

it('returns only the next purchase milestone after unlocked purchase achievements', function () {
    seedAchievementProgressDefinitions();

    $user = User::factory()->create();
    $firstPurchase = Achievement::query()->where('name', RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE)->sole();
    $fivePurchases = Achievement::query()->where('name', RewardDefinitions::ACHIEVEMENT_5_PURCHASES)->sole();
    $beginner = Badge::query()->where('name', RewardDefinitions::BADGE_BEGINNER)->sole();

    $user->achievements()->attach($firstPurchase->id, ['unlocked_at' => now()]);
    $user->achievements()->attach($fivePurchases->id, ['unlocked_at' => now()]);
    $user->badges()->attach($beginner->id, ['unlocked_at' => now()]);

    $progress = app(AchievementService::class)->progressFor($user);

    expect($progress)->toBe([
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

it('returns no next badge when the final badge is unlocked', function () {
    seedAchievementProgressDefinitions();

    $user = User::factory()->create();

    Achievement::query()->each(
        fn (Achievement $achievement) => $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]),
    );

    Badge::query()->each(
        fn (Badge $badge) => $user->badges()->attach($badge->id, ['unlocked_at' => now()]),
    );

    $progress = app(AchievementService::class)->progressFor($user);

    expect($progress['current_badge'])->toBe(RewardDefinitions::BADGE_MASTER)
        ->and($progress['next_badge'])->toBe('')
        ->and($progress['remaining_to_unlock_next_badge'])->toBe(0);
});
