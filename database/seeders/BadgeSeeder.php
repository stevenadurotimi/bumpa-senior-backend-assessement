<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Seed badge definitions.
     */
    public function run(): void
    {
        $badges = [
            [
                'name' => 'Beginner',
                'required_achievements_count' => 1,
                'sort_order' => 1,
            ],
            [
                'name' => 'Intermediate',
                'required_achievements_count' => 4,
                'sort_order' => 2,
            ],
            [
                'name' => 'Advanced',
                'required_achievements_count' => 8,
                'sort_order' => 3,
            ],
            [
                'name' => 'Master',
                'required_achievements_count' => 10,
                'sort_order' => 4,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['name' => $badge['name']],
                $badge,
            );
        }
    }
}
