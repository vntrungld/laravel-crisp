<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp;

use Crisp\CrispClient;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelCrispServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        $this->bootRoutes();
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-crisp');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/crisp.php', 'crisp');

        $this->app->singleton('laravel-crisp', function () {
            return new LaravelCrisp(new CrispClient);
        });

        $this->app->alias('laravel-crisp', LaravelCrisp::class);
    }

    public function provides(): array
    {
        return ['laravel-crisp'];
    }

    protected function bootForConsole(): void
    {
        $this->publishes([
            __DIR__.'/../config/crisp.php' => config_path('crisp.php'),
        ], 'laravel-crisp.config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-crisp'),
        ], 'laravel-crisp.views');
    }

    protected function bootRoutes(): void
    {
        Route::group([
            'prefix' => config('crisp.webhook_path'),
            'as' => 'crisp.',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }
}
