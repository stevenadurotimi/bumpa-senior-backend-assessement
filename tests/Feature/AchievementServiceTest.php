<?php

use App\Events\AchievementUnlocked;
use App\Models\Achievement;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function makeAchievement(array $overrides = []): Achievement
{
    return Achievement::create(array_merge([
        'name' => 'First Purchase',
        'group' => 'purchases',
        'trigger_type' => 'purchase_count',
        'threshold' => 1,
        'sort_order' => 1,
    ], $overrides));
}

test('it unlocks an achievement for a user', function () {
    Event::fake();

    $user = User::factory()->create();
    $achievement = makeAchievement();

    $unlocked = app(AchievementService::class)->unlock($user, $achievement);

    expect($unlocked)->toBeTrue();

    $this->assertDatabaseHas('achievement_user', [
        'user_id' => $user->id,
        'achievement_id' => $achievement->id,
    ]);

    Event::assertDispatched(
        AchievementUnlocked::class,
        fn (AchievementUnlocked $event) => $event->achievement_name === $achievement->name
            && $event->user->is($user),
    );
});

test('it does not unlock the same achievement twice', function () {
    Event::fake();

    $user = User::factory()->create();
    $achievement = makeAchievement();
    $service = app(AchievementService::class);

    expect($service->unlock($user, $achievement))->toBeTrue()
        ->and($service->unlock($user, $achievement))->toBeFalse();

    expect($user->achievements()->count())->toBe(1);

    Event::assertDispatchedTimes(AchievementUnlocked::class, 1);
});
