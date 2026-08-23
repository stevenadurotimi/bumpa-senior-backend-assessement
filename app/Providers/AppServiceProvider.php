<?php

namespace App\Providers;

use App\Contracts\Payments\CashbackPaymentProvider;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bind the cashback contract to the provider selected in config/cashback.php.
     *
     * Domain code depends on CashbackPaymentProvider, so switching from the
     * local mock to Flutterwave or a future provider only requires config.
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
     * Bootstrap application services.
     *
     * Reward listeners are registered through Laravel event discovery.
     */
    public function boot(): void
    {
    }
}
