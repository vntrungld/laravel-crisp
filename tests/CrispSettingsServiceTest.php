<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests;

use Crisp\CrispClient;
use Crisp\Resources\PluginSubscriptions;
use Mockery;
use Vntrungld\LaravelCrisp\LaravelCrisp as LaravelCrispService;
use Vntrungld\LaravelCrisp\Services\CrispSettingsService;

class CrispSettingsServiceTest extends TestCase
{
    public function test_get_returns_subscription_settings(): void
    {
        $settings = ['api_key' => 'abc123', 'enabled' => true];

        $mockPluginSubs = Mockery::mock(PluginSubscriptions::class);
        $mockPluginSubs->shouldReceive('getSubscriptionSettings')
            ->with('website-id', 'test-plugin-id')
            ->once()
            ->andReturn($settings);

        $mockClient = Mockery::mock(CrispClient::class);
        $mockClient->pluginSubscriptions = $mockPluginSubs;

        $mockCrisp = Mockery::mock(LaravelCrispService::class);
        $mockCrisp->shouldReceive('client')->andReturn($mockClient);

        $service = new CrispSettingsService($mockCrisp);

        $this->assertSame($settings, $service->get('website-id'));
    }

    public function test_save_calls_save_subscription_settings(): void
    {
        $data = ['api_key' => 'new-value'];

        $mockPluginSubs = Mockery::mock(PluginSubscriptions::class);
        $mockPluginSubs->shouldReceive('saveSubscriptionSettings')
            ->with('website-id', 'test-plugin-id', $data)
            ->once()
            ->andReturn([]);

        $mockClient = Mockery::mock(CrispClient::class);
        $mockClient->pluginSubscriptions = $mockPluginSubs;

        $mockCrisp = Mockery::mock(LaravelCrispService::class);
        $mockCrisp->shouldReceive('client')->andReturn($mockClient);

        $service = new CrispSettingsService($mockCrisp);
        $service->save('website-id', $data);
    }
}
