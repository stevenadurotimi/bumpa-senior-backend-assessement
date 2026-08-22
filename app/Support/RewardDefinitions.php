<?php

namespace App\Support;

final class RewardDefinitions
{
    public const ACHIEVEMENT_FIRST_PURCHASE = 'First Purchase';
    public const ACHIEVEMENT_5_PURCHASES = '5 Purchases';
    public const ACHIEVEMENT_10_PURCHASES = '10 Purchases';
    public const ACHIEVEMENT_20_PURCHASES = '20 Purchases';

    public const BADGE_BEGINNER = 'Beginner';
    public const BADGE_INTERMEDIATE = 'Intermediate';
    public const BADGE_ADVANCED = 'Advanced';
    public const BADGE_MASTER = 'Master';

    /**
     * @return array<int, array{name: string, group: string, trigger_type: string, threshold: int, sort_order: int}>
     */
    public static function achievements(): array
    {
        return [
            [
                'name' => self::ACHIEVEMENT_FIRST_PURCHASE,
                'group' => 'purchases',
                'trigger_type' => 'purchase_count',
                'threshold' => 1,
                'sort_order' => 1,
            ],
            [
                'name' => self::ACHIEVEMENT_5_PURCHASES,
                'group' => 'purchases',
                'trigger_type' => 'purchase_count',
                'threshold' => 5,
                'sort_order' => 2,
            ],
            [
                'name' => self::ACHIEVEMENT_10_PURCHASES,
                'group' => 'purchases',
                'trigger_type' => 'purchase_count',
                'threshold' => 10,
                'sort_order' => 3,
            ],
            [
                'name' => self::ACHIEVEMENT_20_PURCHASES,
                'group' => 'purchases',
                'trigger_type' => 'purchase_count',
                'threshold' => 20,
                'sort_order' => 4,
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, required_achievements_count: int, sort_order: int}>
     */
    public static function badges(): array
    {
        return [
            [
                'name' => self::BADGE_BEGINNER,
                'required_achievements_count' => 1,
                'sort_order' => 1,
            ],
            [
                'name' => self::BADGE_INTERMEDIATE,
                'required_achievements_count' => 4,
                'sort_order' => 2,
            ],
            [
                'name' => self::BADGE_ADVANCED,
                'required_achievements_count' => 8,
                'sort_order' => 3,
            ],
            [
                'name' => self::BADGE_MASTER,
                'required_achievements_count' => 10,
                'sort_order' => 4,
            ],
        ];
    }
}
