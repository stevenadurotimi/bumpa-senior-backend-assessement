<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AchievementUnlocked
{
    use Dispatchable, SerializesModels;

    /**
     * Payload required by the assessment when an achievement is newly unlocked.
     */
    public function __construct(
        public string $achievement_name,
        public User $user,
    ) {}
}
