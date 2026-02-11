<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp;

use Crisp\CrispClient;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Vntrungld\LaravelCrisp\Http\Livewire\CrispSettings;
use Vntrungld\LaravelCrisp\Http\Middleware\ValidateCrispToken;

class LaravelCrispServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        $this->bootRoutes();

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-crisp');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-crisp'),
        ], 'laravel-crisp.views');

        // Register Livewire components
        Livewire::component('crisp-settings', CrispSettings::class);

        // Register settings routes
        $this->registerSettingsRoutes();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/crisp.php', 'crisp');

        $this->app->singleton('laravel-crisp', function () {
            return new LaravelCrisp(new CrispClient);
        });

        $this->app->singleton(Services\SchemaRenderer::class);
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

    protected function registerSettingsRoutes(): void
    {
        Route::middleware(['web', ValidateCrispToken::class])
            ->prefix(config('crisp.settings.route_path', 'crisp/settings'))
            ->group(function () {
                Route::get('/', CrispSettings::class)->name('crisp.settings');
            });
    }
}
