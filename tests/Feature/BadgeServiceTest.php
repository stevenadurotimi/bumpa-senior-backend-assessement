<?php

use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function makeBadge(array $overrides = []): Badge
{
    return Badge::create(array_merge([
        'name' => 'Beginner',
        'required_achievements_count' => 1,
        'sort_order' => 1,
    ], $overrides));
}

test('it unlocks a badge for a user', function () {
    Event::fake();

    $user = User::factory()->create();
    $badge = makeBadge();

    $unlocked = app(BadgeService::class)->unlock($user, $badge);

    expect($unlocked)->toBeTrue();

    $this->assertDatabaseHas('badge_user', [
        'user_id' => $user->id,
        'badge_id' => $badge->id,
    ]);

    Event::assertDispatched(
        BadgeUnlocked::class,
        fn (BadgeUnlocked $event) => $event->badge_name === $badge->name
            && $event->user->is($user),
    );
});

test('it does not unlock the same badge twice', function () {
    Event::fake();

    $user = User::factory()->create();
    $badge = makeBadge();
    $service = app(BadgeService::class);

    expect($service->unlock($user, $badge))->toBeTrue()
        ->and($service->unlock($user, $badge))->toBeFalse();

    expect($user->badges()->count())->toBe(1);

    Event::assertDispatchedTimes(BadgeUnlocked::class, 1);
});
