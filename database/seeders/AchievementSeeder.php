<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Support\RewardDefinitions;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Seed achievement definitions.
     */
    public function run(): void
    {
        foreach (RewardDefinitions::achievements() as $achievement) {
            // updateOrCreate keeps reseeding safe during repeated local testing.
            Achievement::updateOrCreate(
                ['name' => $achievement['name']],
                $achievement,
            );
        }
    }
}
