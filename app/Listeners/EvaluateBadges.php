<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Models\Badge;
use App\Services\BadgeService;

class EvaluateBadges
{
    public function __construct(private readonly BadgeService $badgeService) {}

    /**
     * Evaluate badge thresholds whenever a new achievement is unlocked.
     *
     * The listener only decides which badge definitions the user now qualifies
     * for. BadgeService handles duplicate prevention and dispatches the badge
     * unlock event only for newly unlocked badges.
     */
    public function handle(AchievementUnlocked $event): void
    {
        $user = $event->user;
        $unlockedAchievementCount = $user->achievements()->count();

        /*
         * Select all badge thresholds the user now qualifies for. BadgeService
         * skips already-unlocked badges, so catch-up and replay are safe.
         */
        Badge::query()
            ->where('required_achievements_count', '<=', $unlockedAchievementCount)
            ->orderBy('required_achievements_count')
            ->each(function (Badge $badge) use ($user): void {
                $this->badgeService->unlock($user, $badge);
            });
    }
}
