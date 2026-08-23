<?php

namespace App\Listeners;

use App\Events\PurchaseRecorded;
use App\Models\Achievement;
use App\Services\AchievementService;
use App\Support\RewardDefinitions;

readonly class EvaluatePurchaseAchievements
{
    public function __construct(private AchievementService $achievementService) {}

    /**
     * Re-check purchase milestones whenever a purchase is recorded.
     *
     * The purchase event may contain a model that already has its user loaded.
     * loadMissing keeps that path efficient while still supporting events that
     * carry a bare Purchase model.
     */
    public function handle(PurchaseRecorded $event): void
    {
        $purchase = $event->purchase->loadMissing('user');
        $user = $purchase->user;
        $purchaseCount = $user->purchases()->count();

        /*
         * Find every achievement this listener is responsible for and that the
         * user now qualifies for. The group narrows this to purchase-related
         * achievements, while trigger_type narrows it further to achievements
         * unlocked by total purchase count. The threshold check selects every
         * milestone already reached, so catch-up works if several purchases
         * existed before evaluation. AchievementService keeps each unlock
         * idempotent, so already-unlocked milestones are skipped safely.
         */
        Achievement::query()
            ->where('group', RewardDefinitions::GROUP_PURCHASES)
            ->where('trigger_type', RewardDefinitions::TRIGGER_PURCHASE_COUNT)
            ->where('threshold', '<=', $purchaseCount)
            ->orderBy('threshold')
            ->each(function (Achievement $achievement) use ($user): void {
                $this->achievementService->unlock($user, $achievement);
            });
    }
}
