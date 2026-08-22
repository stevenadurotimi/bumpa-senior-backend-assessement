<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Models\Badge;
use App\Services\BadgeService;

class EvaluateBadges
{
    public function __construct(private readonly BadgeService $badgeService) {}

    public function handle(AchievementUnlocked $event): void
    {
        $user = $event->user;
        $unlockedAchievementCount = $user->achievements()->count();

        Badge::query()
            ->where('required_achievements_count', '<=', $unlockedAchievementCount)
            ->orderBy('required_achievements_count')
            ->each(fn (Badge $badge) => $this->badgeService->unlock($user, $badge));
    }
}
