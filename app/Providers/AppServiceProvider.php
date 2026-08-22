<?php

namespace App\Providers;

use App\Events\AchievementUnlocked;
use App\Events\PurchaseRecorded;
use App\Listeners\EvaluateBadges;
use App\Listeners\EvaluatePurchaseAchievements;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(AchievementUnlocked::class, EvaluateBadges::class);
        Event::listen(PurchaseRecorded::class, EvaluatePurchaseAchievements::class);
    }
}
