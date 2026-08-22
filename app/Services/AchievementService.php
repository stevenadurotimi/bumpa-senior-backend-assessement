<?php

namespace App\Services;

use App\Events\AchievementUnlocked;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;

class AchievementService
{
    public function unlock(User $user, Achievement $achievement): bool
    {
        if ($user->achievements()->whereKey($achievement->getKey())->exists()) {
            return false;
        }

        try {
            $user->achievements()->attach($achievement->getKey(), [
                'unlocked_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        Event::dispatch(new AchievementUnlocked($achievement->name, $user));

        return true;
    }
}
