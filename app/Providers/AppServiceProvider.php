<?php

namespace App\Providers;

use App\Contracts\Payments\CashbackPaymentProvider;
use InvalidArgumentException;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            CashbackPaymentProvider::class,
            function () {
                $provider = config('cashback.provider');
                $config = config("cashback.providers.{$provider}");

                if (! is_array($config) || ! isset($config['handler'])) {
                    throw new InvalidArgumentException("Unsupported cashback payment provider [{$provider}].");
                }

                return new $config['handler']($config);
            },
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
