<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BadgeUnlocked
{
    use Dispatchable, SerializesModels;

    /**
     * Payload required by the assessment when a badge is newly unlocked.
     */
    public function __construct(
        public string $badge_name,
        public User $user,
    ) {}
}
