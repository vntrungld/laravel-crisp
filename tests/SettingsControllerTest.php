<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests;

use Illuminate\Auth\GenericUser;
use Mockery;
use RuntimeException;
use Vntrungld\LaravelCrisp\Services\CrispSettingsService;

class SettingsControllerTest extends TestCase
{
    private GenericUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new GenericUser(['id' => 1]);

        // Provide a login route for auth redirect tests
        $this->app['router']->get('/login', fn () => response('login'))->name('login');
    }

    public function test_show_returns_200_with_settings(): void
    {
        $settings = ['api_key' => 'abc123', 'enabled' => true, 'timeout' => 30];

        $mockService = Mockery::mock(CrispSettingsService::class);
        $mockService->shouldReceive('get')->with('test-website-id')->andReturn($settings);
        $this->app->instance(CrispSettingsService::class, $mockService);

        $this->actingAs($this->user)
            ->get('/crisp/settings?website_id=test-website-id')
            ->assertOk()
            ->assertSee('api_key')
            ->assertSee('abc123');
    }

    public function test_show_displays_error_message_when_api_throws(): void
    {
        $mockService = Mockery::mock(CrispSettingsService::class);
        $mockService->shouldReceive('get')->andThrow(new RuntimeException('API unavailable'));
        $this->app->instance(CrispSettingsService::class, $mockService);

        $this->actingAs($this->user)
            ->get('/crisp/settings?website_id=test-website-id')
            ->assertOk()
            ->assertSee('API unavailable');
    }

    public function test_show_returns_400_when_website_id_missing(): void
    {
        $this->actingAs($this->user)
            ->get('/crisp/settings')
            ->assertBadRequest();
    }

    public function test_show_redirects_to_login_when_unauthenticated(): void
    {
        $this->get('/crisp/settings?website_id=test-website-id')
            ->assertRedirect('/login');
    }

    public function test_update_redirects_back_with_success_flash(): void
    {
        $mockService = Mockery::mock(CrispSettingsService::class);
        $mockService->shouldReceive('save')
            ->with('test-website-id', ['api_key' => 'abc123'])
            ->once();
        $this->app->instance(CrispSettingsService::class, $mockService);

        $this->actingAs($this->user)
            ->from('/crisp/settings?website_id=test-website-id')
            ->post('/crisp/settings?website_id=test-website-id', ['api_key' => 'abc123'])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_update_redirects_back_with_error_flash_when_api_throws(): void
    {
        $mockService = Mockery::mock(CrispSettingsService::class);
        $mockService->shouldReceive('save')->andThrow(new RuntimeException('Save failed'));
        $this->app->instance(CrispSettingsService::class, $mockService);

        $this->actingAs($this->user)
            ->from('/crisp/settings?website_id=test-website-id')
            ->post('/crisp/settings?website_id=test-website-id', ['api_key' => 'abc123'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_update_returns_400_when_website_id_missing(): void
    {
        $this->actingAs($this->user)
            ->post('/crisp/settings', ['api_key' => 'abc123'])
            ->assertBadRequest();
    }
}
