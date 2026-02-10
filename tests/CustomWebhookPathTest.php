<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests;

class CustomWebhookPathTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Override the webhook path to test custom configuration
        config()->set('crisp.webhook_path', 'custom-path');
    }

    public function test_custom_webhook_path_works(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('crisp.webhook');

        $this->assertNotNull($route);
        $this->assertSame('custom-path/webhook', $route->uri());
        $this->assertContains('POST', $route->methods());
    }
}
