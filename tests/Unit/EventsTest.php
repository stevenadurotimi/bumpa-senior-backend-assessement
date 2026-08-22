<?php

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Events\PurchaseRecorded;
use App\Models\Purchase;
use App\Models\User;
use App\Support\RewardDefinitions;

test('achievement unlocked event exposes the required payload', function () {
    $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);

    $event = new AchievementUnlocked(RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE, $user);

    expect($event->achievement_name)->toBe(RewardDefinitions::ACHIEVEMENT_FIRST_PURCHASE)
        ->and($event->user)->toBe($user);
});

test('badge unlocked event exposes the required payload', function () {
    $user = new User(['name' => 'Test User', 'email' => 'test@example.com']);

    $event = new BadgeUnlocked(RewardDefinitions::BADGE_BEGINNER, $user);

    expect($event->badge_name)->toBe(RewardDefinitions::BADGE_BEGINNER)
        ->and($event->user)->toBe($user);
});

test('purchase recorded event exposes the purchase', function () {
    $purchase = new Purchase(['reference' => 'purchase-001', 'amount' => 5000]);

    $event = new PurchaseRecorded($purchase);

    expect($event->purchase)->toBe($purchase);
});
