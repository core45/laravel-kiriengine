<?php

namespace Core45\LaravelKiriengine;

use Illuminate\Support\ServiceProvider;

class KiriengineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-kiriengine.php', 'kiriengine');

        // Register the service the package provides.
        $this->app->singleton('kiriengine', function ($app) {
            return new Kiriengine;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        // ============ Publish assets with php artisan vendor:publish ============
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laravel-kiriengine.php' => config_path('laravel-kiriengine.php'),
            ], 'laravel-kiriengine');
        }
    }


    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['kiriengine'];
    }
}
