<?php

namespace Core45\LaravelKiriengine\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Core45\LaravelKiriengine\KiriengineServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            KiriengineServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set KIRI Engine test configuration
        $app['config']->set('laravel-kiriengine.api_key', 'test_api_key');
        $app['config']->set('laravel-kiriengine.base_url', 'https://api.kiriengine.app/api/v1/');
        $app['config']->set('laravel-kiriengine.debug', false);
        $app['config']->set('laravel-kiriengine.verify', true);
    }
} 