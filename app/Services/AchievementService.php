<?php

namespace App\Services;

use App\Events\AchievementUnlocked;
use App\Models\Achievement;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;

class AchievementService
{
    /**
     * Unlock one achievement for a user and emit the assessment-required event.
     */
    public function unlock(User $user, Achievement $achievement): bool
    {
        // Fast path for normal duplicate calls before attempting a database write.
        if ($user->achievements()->whereKey($achievement->getKey())->exists()) {
            return false;
        }

        try {
            // The pivot unique constraint is the final idempotency guard.
            $user->achievements()->attach($achievement->getKey(), [
                'unlocked_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        Event::dispatch(new AchievementUnlocked($achievement->name, $user));

        return true;
    }

    /**
     * Build the read model used by GET /users/{user}/achievements.
     *
     * @return array{
     *     unlocked_achievements: list<string>,
     *     next_available_achievements: list<string>,
     *     current_badge: string,
     *     next_badge: string,
     *     remaining_to_unlock_next_badge: int
     * }
     */
    public function progressFor(User $user): array
    {
        // The API returns unlocked achievements as ordered names, not database rows.
        $unlockedAchievements = $user->achievements()
            ->orderBy('sort_order')
            ->orderBy('threshold')
            ->get();

        $unlockedAchievementIds = $unlockedAchievements->modelKeys();
        $unlockedAchievementCount = $unlockedAchievements->count();

        // The current badge is the highest threshold badge the user has unlocked.
        $currentBadge = $user->badges()
            ->orderByDesc('required_achievements_count')
            ->orderByDesc('sort_order')
            ->first();

        // The next badge is the first badge above the current badge threshold.
        $nextBadge = Badge::query()
            ->when(
                $currentBadge,
                fn ($query) => $query->where('required_achievements_count', '>', $currentBadge->required_achievements_count),
            )
            ->orderBy('required_achievements_count')
            ->orderBy('sort_order')
            ->first();

        return [
            'unlocked_achievements' => $unlockedAchievements
                ->pluck('name')
                ->values()
                ->all(),
            'next_available_achievements' => $this->nextAvailableAchievementNames($unlockedAchievementIds),
            'current_badge' => $currentBadge?->name ?? '',
            'next_badge' => $nextBadge?->name ?? '',
            'remaining_to_unlock_next_badge' => $nextBadge
                ? max(0, $nextBadge->required_achievements_count - $unlockedAchievementCount)
                : 0,
        ];
    }

    /**
     * Return only one locked achievement per group so the API shows the next
     * target in each category instead of every possible future milestone.
     *
     * @param  list<int|string>  $unlockedAchievementIds
     * @return list<string>
     */
    private function nextAvailableAchievementNames(array $unlockedAchievementIds): array
    {
        return Achievement::query()
            ->when($unlockedAchievementIds !== [], fn ($query) => $query->whereNotIn('id', $unlockedAchievementIds))
            ->orderBy('group')
            ->orderBy('threshold')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group')
            ->map(fn ($achievements) => $achievements->first()->name)
            ->values()
            ->all();
    }
}
