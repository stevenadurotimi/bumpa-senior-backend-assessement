<?php

use App\Models\Achievement;
use App\Models\Badge;
use App\Models\User;
use App\Support\RewardDefinitions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedUserAchievementsEndpointDefinitions(): void
{
    foreach (RewardDefinitions::achievements() as $achievement) {
        Achievement::query()->create($achievement);
    }

    foreach (RewardDefinitions::badges() as $badge) {
        Badge::query()->create($badge);
    }
}

it('returns achievement progress for an existing user', function () {
    seedUserAchievementsEndpointDefinitions();

    $user = User::factory()->create();

    $this->getJson("/users/{$user->id}/achievements")
        ->assertOk()
        ->assertExactJson([
            'unlocked_achievements' => [],
            'next_available_achievements' => [RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE],
            'current_badge' => '',
            'next_badge' => RewardDefinitions::BADGE_BEGINNER,
            'remaining_to_unlock_next_badge' => 1,
        ]);
});

it('returns user achievement progress with unlocked rewards', function () {
    seedUserAchievementsEndpointDefinitions();

    $user = User::factory()->create();
    $firstPurchase = Achievement::query()->where('name', RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE)->sole();
    $fivePurchases = Achievement::query()->where('name', RewardDefinitions::ACHIEVEMENT_5_PURCHASES)->sole();
    $beginner = Badge::query()->where('name', RewardDefinitions::BADGE_BEGINNER)->sole();

    $user->achievements()->attach($firstPurchase->id, ['unlocked_at' => now()]);
    $user->achievements()->attach($fivePurchases->id, ['unlocked_at' => now()]);
    $user->badges()->attach($beginner->id, ['unlocked_at' => now()]);

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

it('returns only the next available achievement in each group', function () {
    seedUserAchievementsEndpointDefinitions();

    Achievement::query()->create([
        'name' => 'Completed Profile',
        'group' => 'zz_profile',
        'trigger_type' => 'profile_completed',
        'threshold' => 1,
        'sort_order' => 1,
    ]);

    Achievement::query()->create([
        'name' => 'Updated Profile Twice',
        'group' => 'zz_profile',
        'trigger_type' => 'profile_updated_count',
        'threshold' => 2,
        'sort_order' => 2,
    ]);

    $user = User::factory()->create();

    $this->getJson("/users/{$user->id}/achievements")
        ->assertOk()
        ->assertJsonPath('next_available_achievements', [
            RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE,
            'Completed Profile',
        ]);
});

it('returns 404 for a missing user', function () {
    $this->getJson('/users/999999/achievements')
        ->assertNotFound();
});
