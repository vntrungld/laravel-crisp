<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests\Feature\Livewire;

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Orchestra\Testbench\TestCase;
use Vntrungld\LaravelCrisp\Http\Livewire\CrispSettings;
use Vntrungld\LaravelCrisp\LaravelCrisp;
use Vntrungld\LaravelCrisp\Services\SchemaRenderer;

class CrispSettingsTest extends TestCase
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

    public function test_component_can_be_rendered(): void
    {
        $crisp = $this->mock(LaravelCrisp::class);
        $crisp->shouldReceive('getPluginSchema')->andReturn([
            'properties' => ['api_key' => ['type' => 'string']],
        ]);
        $crisp->shouldReceive('getWebsiteSettings')->andReturn([]);

        Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ])
            ->assertSet('loading', false)
            ->assertSet('websiteId', 'test-website')
            ->assertViewIs('laravel-crisp::livewire.crisp-settings');
    }

    public function test_loads_schema_and_settings_on_mount(): void
    {
        $schema = [
            'properties' => [
                'api_key' => ['type' => 'string', 'title' => 'API Key'],
            ],
        ];

        $settings = ['api_key' => 'test-key'];

        $crisp = $this->mock(LaravelCrisp::class);
        $crisp->shouldReceive('getPluginSchema')->andReturn($schema);
        $crisp->shouldReceive('getWebsiteSettings')->andReturn($settings);

        Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ])
            ->assertSet('settings', $settings)
            ->assertCount('fields', 1);
    }

    public function test_saves_settings_successfully(): void
    {
        $crisp = $this->mock(LaravelCrisp::class);
        $crisp->shouldReceive('getPluginSchema')->andReturn([
            'properties' => ['api_key' => ['type' => 'string']],
        ]);
        $crisp->shouldReceive('getWebsiteSettings')->andReturn([]);
        $crisp->shouldReceive('saveWebsiteSettings')->once();

        Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ])
            ->set('settings.api_key', 'new-key')
            ->call('save');
    }

    public function test_displays_error_on_save_failure(): void
    {
        $crisp = $this->mock(LaravelCrisp::class);
        $crisp->shouldReceive('getPluginSchema')->andReturn([
            'properties' => ['api_key' => ['type' => 'string']],
        ]);
        $crisp->shouldReceive('getWebsiteSettings')->andReturn([]);
        $crisp->shouldReceive('saveWebsiteSettings')->andThrow(new \Vntrungld\LaravelCrisp\Exceptions\CrispApiException('Invalid'));

        Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ])
            ->call('save')
            ->assertSet('errorMessage', 'Crisp API Error: Invalid');
    }

    public function test_validates_required_fields(): void
    {
        $crisp = $this->mock(LaravelCrisp::class);
        $crisp->shouldReceive('getPluginSchema')->andReturn([
            'properties' => [
                'api_key' => ['type' => 'string'],
            ],
            'required' => ['api_key'],
        ]);
        $crisp->shouldReceive('getWebsiteSettings')->andReturn([]);

        Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ])
            ->set('settings.api_key', '')
            ->call('save')
            ->assertHasErrors(['settings.api_key' => 'required']);
    }

    public function test_evaluates_conditional_field_visibility(): void
    {
        $crisp = $this->mock(LaravelCrisp::class);
        $crisp->shouldReceive('getPluginSchema')->andReturn([
            'properties' => [
                'enabled' => ['type' => 'boolean'],
                'email' => [
                    'type' => 'string',
                    'x-condition' => [
                        'field' => 'enabled',
                        'value' => true,
                    ],
                ],
            ],
        ]);
        $crisp->shouldReceive('getWebsiteSettings')->andReturn([]);

        $component = Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ]);

        // email field hidden when enabled = false
        $component->set('settings.enabled', false);
        $instance = $component->instance();
        $this->assertFalse($instance->isFieldVisible($instance->fields['email']));

        // email field visible when enabled = true
        $component->set('settings.enabled', true);
        $instance = $component->instance();
        $this->assertTrue($instance->isFieldVisible($instance->fields['email']));
    }

    public function test_refreshes_settings_on_button_click(): void
    {
        $crisp = $this->mock(LaravelCrisp::class);
        $crisp->shouldReceive('getPluginSchema')->andReturn([
            'properties' => ['api_key' => ['type' => 'string']],
        ]);
        $crisp->shouldReceive('getWebsiteSettings')->andReturn(['api_key' => 'old-key'])->twice();

        Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ])
            ->set('settings.api_key', 'new-key')
            ->call('mount')
            ->assertSet('settings.api_key', 'old-key');
    }
}
