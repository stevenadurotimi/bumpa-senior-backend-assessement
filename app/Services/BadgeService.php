<?php

namespace App\Services;

use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;

class BadgeService
{
    /**
     * Unlock one badge for a user and emit the assessment-required event.
     *
     * The existing check handles normal duplicate calls before attempting a
     * database write, while the pivot unique constraint remains the final guard
     * for concurrent or replayed unlock attempts.
     */
    public function unlock(User $user, Badge $badge): bool
    {
        if ($user->badges()->whereKey($badge->getKey())->exists()) {
            return false;
        }

        try {
            $user->badges()->attach($badge->getKey(), [
                'unlocked_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        Event::dispatch(new BadgeUnlocked($badge->name, $user));

        return true;
    }
}
