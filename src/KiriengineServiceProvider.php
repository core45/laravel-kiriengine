<?php

namespace Core45\LaravelKiriengine;

use Core45\LaravelKiriengine\Kiriengine\Balance;
use Core45\LaravelKiriengine\Kiriengine\Model3d;
use Core45\LaravelKiriengine\Kiriengine\Upload3dgsScan;
use Core45\LaravelKiriengine\Kiriengine\UploadObjectScan;
use Core45\LaravelKiriengine\Kiriengine\UploadPhotoScan;
use Core45\LaravelKiriengine\Services\KiriEngineApiKeyResolver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Core45\LaravelKiriengine\Events\KiriWebhookReceived;
use Core45\LaravelKiriengine\Listeners\ProcessKiriWebhook;

class KiriengineServiceProvider extends ServiceProvider
{
    protected $listen = [
        KiriWebhookReceived::class => [
            ProcessKiriWebhook::class,
        ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-kiriengine.php', 'laravel-kiriengine');

        // Register the API key resolver service
        $this->app->singleton(KiriEngineApiKeyResolver::class, function ($app) {
            return new KiriEngineApiKeyResolver();
        });

        // Register the service the package provides.
        $this->app->singleton('kiriengine', function ($app) {
            return new Kiriengine();
        });

        $this->app->singleton('kiriengine.balance', function ($app) {
            return new Balance();
        });

        $this->app->singleton('kiriengine.model3d', function ($app) {
            return new Model3d();
        });

        $this->app->singleton('kiriengine.upload3dgsScan', function ($app) {
            return new Upload3dgsScan();
        });

        $this->app->singleton('kiriengine.uploadObjectScan', function ($app) {
            return new UploadObjectScan();
        });

        $this->app->singleton('kiriengine.uploadPhotoScan', function ($app) {
            return new UploadPhotoScan();
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
        $this->app['config']->set('laravel-kiriengine.base_url', env('KIRIENGINE_BASE_URL', 'https://api.kiriengine.app/api/v1/open/'));
        $this->app['config']->set('laravel-kiriengine.debug', env('KIRIENGINE_DEBUG', false));
        $this->app['config']->set('laravel-kiriengine.verify', env('KIRIENGINE_VERIFY', true));
        $this->app['config']->set('laravel-kiriengine.webhook.secret', env('KIRIENGINE_WEBHOOK_SECRET', ''));
        $this->app['config']->set('laravel-kiriengine.webhook.path', env('KIRIENGINE_WEBHOOK_PATH', 'kiri-engine-webhook'));
        $this->app['config']->set('laravel-kiriengine.webhook.storage_path', env('KIRIENGINE_STORAGE_PATH', 'storage/app/private/kiri-engine'));

        // Register webhook route
        $webhookPath = config('laravel-kiriengine.webhook.path', 'kiri-engine-webhook');
        Route::post($webhookPath, [Http\Controllers\WebhookController::class, 'handle'])
            ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
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
