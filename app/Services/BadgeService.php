<?php

namespace App\Services;

use App\Events\BadgeUnlocked;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;

class BadgeService
{
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
