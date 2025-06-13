<?php

namespace Core45\LaravelKiriengine;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class KiriengineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-kiriengine.php', 'laravel-kiriengine');

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
            ], 'kiriengine');
        }

        // Load environment variables
        $this->app['config']->set('laravel-kiriengine.api_key', env('KIRIENGINE_API_KEY', ''));
        $this->app['config']->set('laravel-kiriengine.base_url', env('KIRIENGINE_BASE_URL', 'https://api.kiriengine.app/api/v1/'));
        $this->app['config']->set('laravel-kiriengine.debug', env('KIRIENGINE_DEBUG', false));
        $this->app['config']->set('laravel-kiriengine.verify', env('KIRIENGINE_VERIFY', true));
        $this->app['config']->set('laravel-kiriengine.webhook.secret', env('KIRIENGINE_WEBHOOK_SECRET', ''));
        $this->app['config']->set('laravel-kiriengine.webhook.path', env('KIRIENGINE_WEBHOOK_PATH', 'kiri-engine-webhook'));
        $this->app['config']->set('laravel-kiriengine.webhook.storage_path', env('KIRIENGINE_STORAGE_PATH', 'storage/app/private/kiri-engine'));

        // Register webhook route
        $webhookPath = config('laravel-kiriengine.webhook.path', 'kiri-engine-webhook');
        Route::post($webhookPath, [Http\Controllers\WebhookController::class, 'handle'])
            ->name('kiriengine.webhook');
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
