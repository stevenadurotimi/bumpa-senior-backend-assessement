<?php

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Models\Achievement;
use App\Models\User;
use App\Support\RewardDefinitions;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function createBadgeEvaluationAchievements(User $user, int $count): void
{
    foreach (range(1, $count) as $number) {
        $achievement = Achievement::create([
            'name' => "Achievement {$number}",
            'group' => 'test',
            'trigger_type' => 'test_count',
            'threshold' => $number,
            'sort_order' => $number,
        ]);

        $user->achievements()->attach($achievement->id, [
            'unlocked_at' => now(),
        ]);
    }
}

test('first unlocked achievement unlocks beginner badge', function () {
    Event::fake([BadgeUnlocked::class]);

    $this->seed(BadgeSeeder::class);

    $user = User::factory()->create();
    createBadgeEvaluationAchievements($user, 1);

    AchievementUnlocked::dispatch(RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE, $user);

    expect($user->badges()->pluck('name')->all())
        ->toBe([RewardDefinitions::BADGE_BEGINNER]);

    Event::assertDispatched(
        BadgeUnlocked::class,
        fn (BadgeUnlocked $event) => $event->badge_name === RewardDefinitions::BADGE_BEGINNER
            && $event->user->is($user),
    );
});

test('badge thresholds are respected', function () {
    Event::fake([BadgeUnlocked::class]);

    $this->seed(BadgeSeeder::class);

    $user = User::factory()->create();
    createBadgeEvaluationAchievements($user, 3);

    AchievementUnlocked::dispatch('Achievement 3', $user);

    expect($user->badges()->pluck('name')->all())
        ->toBe([RewardDefinitions::BADGE_BEGINNER]);

    Event::assertDispatchedTimes(BadgeUnlocked::class, 1);
});

test('multiple eligible badges are unlocked in threshold order', function () {
    Event::fake([BadgeUnlocked::class]);

    $this->seed(BadgeSeeder::class);

    $user = User::factory()->create();
    createBadgeEvaluationAchievements($user, 4);

    AchievementUnlocked::dispatch('Achievement 4', $user);

    $badgeNames = $user->badges()
        ->orderBy('required_achievements_count')
        ->pluck('name')
        ->all();

    expect($badgeNames)->toBe([
        RewardDefinitions::BADGE_BEGINNER,
        RewardDefinitions::BADGE_INTERMEDIATE,
    ]);

    Event::assertDispatchedTimes(BadgeUnlocked::class, 2);
});

test('reprocessing an achievement event does not duplicate badges', function () {
    Event::fake([BadgeUnlocked::class]);

    $this->seed(BadgeSeeder::class);

    $user = User::factory()->create();
    createBadgeEvaluationAchievements($user, 4);

    AchievementUnlocked::dispatch('Achievement 4', $user);
    AchievementUnlocked::dispatch('Achievement 4', $user);

    expect($user->badges()->count())->toBe(2);

    Event::assertDispatchedTimes(BadgeUnlocked::class, 2);
});
