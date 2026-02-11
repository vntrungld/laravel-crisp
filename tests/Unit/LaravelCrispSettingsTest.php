<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests\Unit;

use Crisp\CrispClient;
use Illuminate\Support\Facades\Http;
use Vntrungld\LaravelCrisp\Exceptions\CrispApiException;
use Vntrungld\LaravelCrisp\LaravelCrisp;
use Vntrungld\LaravelCrisp\Tests\TestCase;

class LaravelCrispSettingsTest extends TestCase
{

    public function test_validate_token_returns_true_for_valid_token(): void
    {
        Http::fake([
            '*/plugin/*/subscription/*/verify' => Http::response(['valid' => true], 200),
        ]);

        $crisp = new LaravelCrisp(new CrispClient());
        $result = $crisp->validateToken('valid-token', 'website-123');

        $this->assertTrue($result);
    }

    public function test_validate_token_returns_false_for_invalid_token(): void
    {
        Http::fake([
            '*/plugin/*/subscription/*/verify' => Http::response(['valid' => false], 401),
        ]);

        $crisp = new LaravelCrisp(new CrispClient());
        $result = $crisp->validateToken('invalid-token', 'website-123');

        $this->assertFalse($result);
    }

    public function test_get_plugin_schema_returns_schema_array(): void
    {
        $expectedSchema = [
            'type' => 'object',
            'properties' => [
                'api_key' => ['type' => 'string'],
            ],
        ];

        Http::fake([
            '*/plugin/*/settings/schema' => Http::response($expectedSchema, 200),
        ]);

        $crisp = new LaravelCrisp(new CrispClient());
        $schema = $crisp->getPluginSchema();

        $this->assertEquals($expectedSchema, $schema);
    }

    public function test_get_website_settings_returns_settings_array(): void
    {
        $expectedSettings = ['api_key' => 'test-key'];

        Http::fake([
            '*/plugin/*/subscription/*/settings' => Http::response(['data' => $expectedSettings], 200),
        ]);

        $crisp = new LaravelCrisp(new CrispClient());
        $settings = $crisp->getWebsiteSettings('website-123');

        $this->assertEquals($expectedSettings, $settings);
    }

    public function test_save_website_settings_returns_true_on_success(): void
    {
        Http::fake([
            '*/plugin/*/subscription/*/settings' => Http::response(['success' => true], 200),
        ]);

        $crisp = new LaravelCrisp(new CrispClient());
        $result = $crisp->saveWebsiteSettings('website-123', ['api_key' => 'new-key']);

        $this->assertTrue($result);
    }

    public function test_save_website_settings_throws_exception_on_failure(): void
    {
        Http::fake([
            '*/plugin/*/subscription/*/settings' => Http::response(['error' => 'Invalid'], 400),
        ]);

        $this->expectException(CrispApiException::class);

        $crisp = new LaravelCrisp(new CrispClient());
        $crisp->saveWebsiteSettings('website-123', ['api_key' => 'bad']);
    }
}
