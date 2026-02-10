<?php

namespace Vntrungld\LaravelCrisp;

use Crisp\CrispClient;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelCrispServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        $this->bootRoutes();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/crisp.php', 'crisp');

        // Register the service the package provides.
        $this->app->singleton('laravel-crisp', function ($app) {
            return new LaravelCrisp(new CrispClient);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laravel-crisp'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/crisp.php' => config_path('crisp.php'),
        ], 'laravel-crisp.config');
    }

    protected function bootRoutes(): void
    {
        Route::group([
            'prefix' => config('crisp.webhook_path'),
            'namespace' => 'Vntrungld\LaravelCrisp\Http\Controllers',
            'as' => 'crisp.',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }
}
