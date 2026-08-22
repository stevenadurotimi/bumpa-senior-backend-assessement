<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Support\RewardDefinitions;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Seed badge definitions.
     */
    public function run(): void
    {
        foreach (RewardDefinitions::badges() as $badge) {
            // updateOrCreate keeps reseeding safe during repeated local testing.
            Badge::updateOrCreate(
                ['name' => $badge['name']],
                $badge,
            );
        }
    }
}
