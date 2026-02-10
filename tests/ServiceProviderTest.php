<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests;

use Vntrungld\LaravelCrisp\LaravelCrispServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function test_service_provider_is_loaded(): void
    {
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(LaravelCrispServiceProvider::class, $providers);
    }

    public function test_config_is_loaded(): void
    {
        $this->assertSame('plugin', config('crisp.tier'));
        $this->assertSame('test-token-id', config('crisp.token_id'));
        $this->assertSame('test-token-key', config('crisp.token_key'));
        $this->assertSame('test-secret', config('crisp.signing_secret'));
        $this->assertSame('crisp', config('crisp.webhook_path'));
    }

    public function test_routes_are_registered(): void
    {
        $routes = collect($this->app['router']->getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->toArray();

        $this->assertContains('crisp.webhook', $routes);
    }

    public function test_webhook_route_has_correct_uri(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('crisp.webhook');

        $this->assertNotNull($route);
        $this->assertSame('crisp/webhook', $route->uri());
        $this->assertContains('POST', $route->methods());
    }
}
