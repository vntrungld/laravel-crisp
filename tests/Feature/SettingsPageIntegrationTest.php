<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use Vntrungld\LaravelCrisp\Tests\Fixtures\CrispApiMock;

class SettingsPageIntegrationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Livewire\LivewireServiceProvider::class,
            \Vntrungld\LaravelCrisp\LaravelCrispServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('crisp.plugin_id', 'test-plugin');
        $app['config']->set('crisp.token_id', 'test-token');
        $app['config']->set('crisp.token_key', 'test-key');
        $app['config']->set('crisp.tier', 'plugin');
    }

    public function test_full_settings_flow_with_simple_schema(): void
    {
        Http::fake([
            '*/plugin/*/subscription/*/verify' => Http::response(['valid' => true]),
            '*/plugin/*/settings/schema' => Http::response(CrispApiMock::simpleSchema()),
            '*/plugin/*/subscription/*/settings' => Http::sequence()
                ->push(CrispApiMock::settingsResponse(['api_key' => 'old-key', 'enabled' => true]))
                ->push(CrispApiMock::saveSuccessResponse()),
        ]);

        $response = $this->get('/crisp/settings?token=valid-token&website_id=test-website');

        $response->assertOk();
        $response->assertSee('API Key');
        $response->assertSee('Enabled');
    }

    public function test_full_settings_flow_with_complex_schema(): void
    {
        Http::fake([
            '*/plugin/*/subscription/*/verify' => Http::response(['valid' => true]),
            '*/plugin/*/settings/schema' => Http::response(CrispApiMock::complexNestedSchema()),
            '*/plugin/*/subscription/*/settings' => Http::response(CrispApiMock::settingsResponse([])),
        ]);

        $response = $this->get('/crisp/settings?token=valid-token&website_id=test-website');

        $response->assertOk();
        $response->assertSee('General Settings');
        $response->assertSee('Notifications');
        $response->assertSee('Webhooks');
    }

    public function test_denies_access_without_token(): void
    {
        $response = $this->get('/crisp/settings?website_id=test-website');

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Missing authentication']);
    }

    public function test_denies_access_with_invalid_token(): void
    {
        Http::fake([
            '*/plugin/*/subscription/*/verify' => Http::response(['valid' => false], 401),
        ]);

        $response = $this->get('/crisp/settings?token=invalid-token&website_id=test-website');

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid token']);
    }
}
