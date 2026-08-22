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
     */
    public function unlock(User $user, Badge $badge): bool
    {
        // Fast path for normal duplicate calls before attempting a database write.
        if ($user->badges()->whereKey($badge->getKey())->exists()) {
            return false;
        }

        try {
            // The pivot unique constraint is the final idempotency guard.
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
