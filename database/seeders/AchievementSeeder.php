<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Seed achievement definitions.
     */
    public function run(): void
    {
        $achievements = [
            [
                'name' => 'First Purchase',
                'group' => 'purchases',
                'trigger_type' => 'purchase_count',
                'threshold' => 1,
                'sort_order' => 1,
            ],
            [
                'name' => '5 Purchases',
                'group' => 'purchases',
                'trigger_type' => 'purchase_count',
                'threshold' => 5,
                'sort_order' => 2,
            ],
            [
                'name' => '10 Purchases',
                'group' => 'purchases',
                'trigger_type' => 'purchase_count',
                'threshold' => 10,
                'sort_order' => 3,
            ],
            [
                'name' => '20 Purchases',
                'group' => 'purchases',
                'trigger_type' => 'purchase_count',
                'threshold' => 20,
                'sort_order' => 4,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(
                ['name' => $achievement['name']],
                $achievement,
            );
        }
    }
}
