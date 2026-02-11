<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests\Feature\Livewire;

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


        Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ])
            ->assertSet('settings', $settings)
            ->assertCount('fields', 1);
    }

    public function test_saves_settings_successfully(): void
    {
        Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ])
            ->set('settings.api_key', 'new-key')
            ->call('save')
            ->assertSet('successMessage', 'Settings saved successfully!')
            ->assertSet('errorMessage', null);
    }

    public function test_displays_error_on_save_failure(): void
    {
        Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ])
            ->set('settings.api_key', 'invalid')
            ->call('save')
            ->assertSet('errorMessage', 'Crisp API Error: Invalid');
    }

    public function test_validates_required_fields(): void
    {

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
}
