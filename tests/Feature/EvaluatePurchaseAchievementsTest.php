<?php

use App\Events\AchievementUnlocked;
use App\Events\PurchaseRecorded;
use App\Models\Achievement;
use App\Models\Purchase;
use App\Models\User;
use App\Support\RewardDefinitions;
use Database\Seeders\AchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('first purchase unlocks the first purchase achievement', function () {
    Event::fake([AchievementUnlocked::class]);

    $this->seed(AchievementSeeder::class);

    $user = User::factory()->create();
    $purchase = Purchase::create([
        'user_id' => $user->id,
        'reference' => 'purchase-001',
        'amount' => 5000,
    ]);

    PurchaseRecorded::dispatch($purchase);

    expect($user->achievements()->pluck('name')->all())
        ->toBe([RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE]);

    Event::assertDispatched(
        AchievementUnlocked::class,
        fn (AchievementUnlocked $event) => $event->achievement_name === RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE
            && $event->user->is($user),
    );
});

test('fifth purchase unlocks all eligible purchase milestones', function () {
    Event::fake([AchievementUnlocked::class]);

    $this->seed(AchievementSeeder::class);

    $user = User::factory()->create();

    foreach (range(1, 5) as $purchaseNumber) {
        Purchase::create([
            'user_id' => $user->id,
            'reference' => "purchase-00{$purchaseNumber}",
            'amount' => 5000,
        ]);
    }

    PurchaseRecorded::dispatch($user->purchases()->latest('id')->firstOrFail());

    $achievementNames = $user->achievements()
        ->orderBy('threshold')
        ->pluck('name')
        ->all();

    expect($achievementNames)->toBe([
        RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE,
        RewardDefinitions::ACHIEVEMENT_5_PURCHASES,
    ]);

    Event::assertDispatchedTimes(AchievementUnlocked::class, 2);
});

test('reprocessing the same purchase event does not duplicate achievements', function () {
    Event::fake([AchievementUnlocked::class]);

    $this->seed(AchievementSeeder::class);

    $user = User::factory()->create();

    foreach (range(1, 5) as $purchaseNumber) {
        Purchase::create([
            'user_id' => $user->id,
            'reference' => "purchase-00{$purchaseNumber}",
            'amount' => 5000,
        ]);
    }

    $purchase = $user->purchases()->latest('id')->firstOrFail();

    PurchaseRecorded::dispatch($purchase);
    PurchaseRecorded::dispatch($purchase);

    expect($user->achievements()->count())->toBe(2)
        ->and(Achievement::whereHas('users', fn ($query) => $query->whereKey($user->id))->count())->toBe(2);

    Event::assertDispatchedTimes(AchievementUnlocked::class, 2);
});
