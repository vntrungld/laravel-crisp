# Crisp Plugin Settings Page Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a dynamic, schema-driven settings page that embeds in Crisp Dashboard via iframe, automatically rendering forms from JSON Schema and managing settings per website.

**Architecture:** Livewire component fetches JSON Schema from Crisp API, dynamically renders form fields via SchemaRenderer service, validates input, and persists settings back to Crisp. Token-based authentication via middleware ensures security.

**Tech Stack:** Laravel 9+, Livewire 3, Tailwind CSS, Crisp PHP API Client

---

## Task 1: Install Livewire Dependency

**Files:**
- Modify: `composer.json`

**Step 1: Add Livewire to composer.json**

Run: `composer require livewire/livewire:^3.0 --no-update`

**Step 2: Update dependencies**

Run: `composer update`
Expected: Livewire 3.x installed successfully

**Step 3: Verify installation**

Run: `composer show livewire/livewire`
Expected: Shows version ^3.0

**Step 4: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore: add Livewire 3 dependency for settings page"
```

---

## Task 2: Add Settings Configuration

**Files:**
- Modify: `config/crisp.php`
- Test: Manual verification

**Step 1: Extend configuration file**

Add settings section to `config/crisp.php`:

```php
<?php

declare(strict_types=1);

return [
    'tier' => env('CRISP_TIER', 'plugin'),
    'token_id' => env('CRISP_TOKEN_ID', ''),
    'token_key' => env('CRISP_TOKEN_KEY', ''),
    'signing_secret' => env('CRISP_SIGNING_SECRET', ''),
    'webhook_path' => env('CRISP_WEBHOOK_PATH', 'crisp'),
    'plugin_id' => env('CRISP_PLUGIN_ID', ''),

    'settings' => [
        'route_path' => env('CRISP_SETTINGS_PATH', 'crisp/settings'),
        'token_cache_ttl' => (int) env('CRISP_TOKEN_CACHE_TTL', 300),
        'allowed_frame_origins' => [
            'https://app.crisp.chat',
            'https://app.crisp.im',
        ],
    ],
];
```

**Step 2: Verify configuration loads**

Run: `php artisan tinker` then `config('crisp.settings')`
Expected: Returns array with route_path, token_cache_ttl, allowed_frame_origins

**Step 3: Commit**

```bash
git add config/crisp.php
git commit -m "config: add settings section for dynamic settings page"
```

---

## Task 3: Create CrispApiException

**Files:**
- Create: `src/Exceptions/CrispApiException.php`
- Test: `tests/Unit/Exceptions/CrispApiExceptionTest.php`

**Step 1: Write the failing test**

Create `tests/Unit/Exceptions/CrispApiExceptionTest.php`:

```php
<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use Vntrungld\LaravelCrisp\Exceptions\CrispApiException;

class CrispApiExceptionTest extends TestCase
{
    public function test_can_be_instantiated_with_message(): void
    {
        $exception = new CrispApiException('API Error');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('API Error', $exception->getMessage());
    }

    public function test_can_be_thrown(): void
    {
        $this->expectException(CrispApiException::class);
        $this->expectExceptionMessage('Test exception');

        throw new CrispApiException('Test exception');
    }
}
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Exceptions/CrispApiExceptionTest.php`
Expected: FAIL - Class 'CrispApiException' not found

**Step 3: Write minimal implementation**

Create `src/Exceptions/CrispApiException.php`:

```php
<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Exceptions;

use Exception;

class CrispApiException extends Exception
{
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Exceptions/CrispApiExceptionTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add src/Exceptions/CrispApiException.php tests/Unit/Exceptions/CrispApiExceptionTest.php
git commit -m "feat: add CrispApiException for API error handling"
```

---

## Task 4: Extend LaravelCrisp with Settings API Methods

**Files:**
- Modify: `src/LaravelCrisp.php`
- Test: `tests/Unit/LaravelCrispSettingsTest.php`

**Step 1: Write the failing test**

Create `tests/Unit/LaravelCrispSettingsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests\Unit;

use Crisp\CrispClient;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;
use Vntrungld\LaravelCrisp\Exceptions\CrispApiException;
use Vntrungld\LaravelCrisp\LaravelCrisp;

class LaravelCrispSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock config
        $this->mockConfig();
    }

    protected function mockConfig(): void
    {
        if (!function_exists('config')) {
            function config($key) {
                $config = [
                    'crisp.tier' => 'plugin',
                    'crisp.token_id' => 'test-token-id',
                    'crisp.token_key' => 'test-token-key',
                    'crisp.plugin_id' => 'test-plugin-id',
                ];
                return $config[$key] ?? null;
            }
        }
    }

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
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/LaravelCrispSettingsTest.php`
Expected: FAIL - Methods do not exist

**Step 3: Implement settings methods in LaravelCrisp**

Modify `src/LaravelCrisp.php`:

```php
<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp;

use Crisp\CrispClient;
use Illuminate\Support\Facades\Http;
use Vntrungld\LaravelCrisp\Exceptions\CrispApiException;

class LaravelCrisp
{
    public function __construct(protected readonly CrispClient $client)
    {
        $this->client->setTier(config('crisp.tier'));
        $this->client->authenticate(config('crisp.token_id'), config('crisp.token_key'));
    }

    public function client(): CrispClient
    {
        return $this->client;
    }

    /**
     * Validate token has access to website
     */
    public function validateToken(string $token, string $websiteId): bool
    {
        try {
            $response = $this->makePluginApiRequest(
                'GET',
                "subscription/{$websiteId}/verify",
                [],
                $token
            );

            return $response['valid'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get plugin settings schema
     */
    public function getPluginSchema(): array
    {
        $response = $this->makePluginApiRequest('GET', 'settings/schema');

        return $response;
    }

    /**
     * Get website settings
     */
    public function getWebsiteSettings(string $websiteId): array
    {
        $response = $this->makePluginApiRequest(
            'GET',
            "subscription/{$websiteId}/settings"
        );

        return $response['data'] ?? [];
    }

    /**
     * Save website settings
     */
    public function saveWebsiteSettings(string $websiteId, array $settings): bool
    {
        $response = $this->makePluginApiRequest(
            'POST',
            "subscription/{$websiteId}/settings",
            ['settings' => $settings]
        );

        if (!($response['success'] ?? false)) {
            throw new CrispApiException(
                $response['error'] ?? 'Failed to save settings'
            );
        }

        return true;
    }

    /**
     * Make authenticated request to Crisp Plugin API
     */
    protected function makePluginApiRequest(
        string $method,
        string $path,
        array $data = [],
        ?string $token = null
    ): array {
        $pluginId = config('crisp.plugin_id');
        $url = "https://api.crisp.chat/v1/plugin/{$pluginId}/{$path}";

        $options = [
            'headers' => [
                'Authorization' => $token
                    ? "Bearer {$token}"
                    : 'Basic ' . base64_encode(
                        config('crisp.token_id') . ':' . config('crisp.token_key')
                    ),
                'X-Crisp-Tier' => config('crisp.tier'),
            ],
        ];

        if (!empty($data)) {
            $options['json'] = $data;
        }

        $response = Http::withOptions($options)->$method($url);

        return $response->json();
    }
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/LaravelCrispSettingsTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add src/LaravelCrisp.php tests/Unit/LaravelCrispSettingsTest.php
git commit -m "feat: add settings API methods to LaravelCrisp client"
```

---

## Task 5: Create SchemaRenderer Service

**Files:**
- Create: `src/Services/SchemaRenderer.php`
- Test: `tests/Unit/Services/SchemaRendererTest.php`

**Step 1: Write the failing test**

Create `tests/Unit/Services/SchemaRendererTest.php`:

```php
<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Vntrungld\LaravelCrisp\Services\SchemaRenderer;

class SchemaRendererTest extends TestCase
{
    protected SchemaRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new SchemaRenderer();
    }

    public function test_renders_string_field(): void
    {
        $schema = [
            'properties' => [
                'api_key' => [
                    'type' => 'string',
                    'title' => 'API Key',
                    'maxLength' => 100,
                ],
            ],
            'required' => ['api_key'],
        ];

        $fields = $this->renderer->renderSchema($schema);

        $this->assertArrayHasKey('api_key', $fields);
        $this->assertEquals('string', $fields['api_key']['type']);
        $this->assertEquals('API Key', $fields['api_key']['label']);
        $this->assertTrue($fields['api_key']['required']);
        $this->assertContains('max:100', $fields['api_key']['validation']);
    }

    public function test_renders_number_field(): void
    {
        $schema = [
            'properties' => [
                'timeout' => [
                    'type' => 'number',
                    'title' => 'Timeout',
                    'minimum' => 1,
                    'maximum' => 60,
                ],
            ],
        ];

        $fields = $this->renderer->renderSchema($schema);

        $this->assertEquals('number', $fields['timeout']['type']);
        $this->assertContains('min:1', $fields['timeout']['validation']);
        $this->assertContains('max:60', $fields['timeout']['validation']);
    }

    public function test_renders_boolean_field(): void
    {
        $schema = [
            'properties' => [
                'enabled' => [
                    'type' => 'boolean',
                    'title' => 'Enabled',
                    'default' => true,
                ],
            ],
        ];

        $fields = $this->renderer->renderSchema($schema);

        $this->assertEquals('boolean', $fields['enabled']['type']);
        $this->assertTrue($fields['enabled']['default']);
    }

    public function test_renders_select_field_from_enum(): void
    {
        $schema = [
            'properties' => [
                'level' => [
                    'type' => 'string',
                    'title' => 'Level',
                    'enum' => ['low', 'medium', 'high'],
                ],
            ],
        ];

        $fields = $this->renderer->renderSchema($schema);

        $this->assertEquals('select', $fields['level']['type']);
        $this->assertEquals(['low', 'medium', 'high'], $fields['level']['options']);
    }

    public function test_renders_textarea_from_format(): void
    {
        $schema = [
            'properties' => [
                'description' => [
                    'type' => 'string',
                    'title' => 'Description',
                    'format' => 'textarea',
                ],
            ],
        ];

        $fields = $this->renderer->renderSchema($schema);

        $this->assertEquals('textarea', $fields['description']['type']);
    }

    public function test_extracts_validation_rules_from_constraints(): void
    {
        $schema = [
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'minLength' => 5,
                ],
            ],
        ];

        $fields = $this->renderer->renderSchema($schema);

        $this->assertContains('email', $fields['email']['validation']);
        $this->assertContains('min:5', $fields['email']['validation']);
    }

    public function test_extracts_conditional_logic(): void
    {
        $schema = [
            'properties' => [
                'enable_notifications' => [
                    'type' => 'boolean',
                ],
                'notification_email' => [
                    'type' => 'string',
                    'x-condition' => [
                        'field' => 'enable_notifications',
                        'value' => true,
                    ],
                ],
            ],
        ];

        $fields = $this->renderer->renderSchema($schema);

        $this->assertNotNull($fields['notification_email']['conditions']);
        $this->assertEquals('enable_notifications', $fields['notification_email']['conditions']['field']);
        $this->assertEquals('equals', $fields['notification_email']['conditions']['operator']);
        $this->assertTrue($fields['notification_email']['conditions']['value']);
    }

    public function test_renders_object_field(): void
    {
        $schema = [
            'properties' => [
                'credentials' => [
                    'type' => 'object',
                    'title' => 'Credentials',
                    'properties' => [
                        'username' => ['type' => 'string'],
                        'password' => ['type' => 'string'],
                    ],
                ],
            ],
        ];

        $fields = $this->renderer->renderSchema($schema);

        $this->assertEquals('object', $fields['credentials']['type']);
        $this->assertArrayHasKey('username', $fields['credentials']['properties']);
        $this->assertArrayHasKey('password', $fields['credentials']['properties']);
    }

    public function test_renders_array_field(): void
    {
        $schema = [
            'properties' => [
                'tags' => [
                    'type' => 'array',
                    'title' => 'Tags',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];

        $fields = $this->renderer->renderSchema($schema);

        $this->assertEquals('array', $fields['tags']['type']);
        $this->assertArrayHasKey('type', $fields['tags']['items']);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Services/SchemaRendererTest.php`
Expected: FAIL - Class not found

**Step 3: Implement SchemaRenderer service**

Create `src/Services/SchemaRenderer.php`:

```php
<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Services;

class SchemaRenderer
{
    /**
     * Render JSON Schema into field definitions
     */
    public function renderSchema(array $schema): array
    {
        $fields = [];

        foreach ($schema['properties'] ?? [] as $key => $property) {
            $fields[$key] = $this->mapPropertyToField($key, $property, $schema);
        }

        return $fields;
    }

    /**
     * Map single property to field definition
     */
    protected function mapPropertyToField(string $key, array $property, array $schema): array
    {
        return [
            'key' => $key,
            'type' => $this->getFieldType($property),
            'label' => $property['title'] ?? $this->formatLabel($key),
            'description' => $property['description'] ?? null,
            'required' => in_array($key, $schema['required'] ?? []),
            'default' => $property['default'] ?? null,
            'validation' => $this->extractValidationRules($property),
            'options' => $property['enum'] ?? null,
            'properties' => $property['properties'] ?? null,
            'items' => $property['items'] ?? null,
            'conditions' => $this->extractConditions($property, $schema),
        ];
    }

    /**
     * Determine field type from property
     */
    protected function getFieldType(array $property): string
    {
        $type = $property['type'] ?? 'string';

        // Handle enum as select
        if (isset($property['enum'])) {
            return 'select';
        }

        // Handle format variations
        if ($type === 'string' && isset($property['format'])) {
            if ($property['format'] === 'textarea') {
                return 'textarea';
            }
        }

        return $type;
    }

    /**
     * Extract Laravel validation rules from JSON Schema
     */
    protected function extractValidationRules(array $property): array
    {
        $rules = [];
        $type = $property['type'] ?? 'string';

        // String validation
        if ($type === 'string') {
            if (isset($property['minLength'])) {
                $rules[] = "min:{$property['minLength']}";
            }
            if (isset($property['maxLength'])) {
                $rules[] = "max:{$property['maxLength']}";
            }
            if (isset($property['pattern'])) {
                $rules[] = "regex:{$property['pattern']}";
            }
            if (isset($property['format'])) {
                $format = $property['format'];
                if (in_array($format, ['email', 'url', 'uuid', 'date'])) {
                    $rules[] = $format;
                }
            }
        }

        // Number validation
        if (in_array($type, ['number', 'integer'])) {
            $rules[] = 'numeric';
            if (isset($property['minimum'])) {
                $rules[] = "min:{$property['minimum']}";
            }
            if (isset($property['maximum'])) {
                $rules[] = "max:{$property['maximum']}";
            }
        }

        // Boolean validation
        if ($type === 'boolean') {
            $rules[] = 'boolean';
        }

        // Enum validation
        if (isset($property['enum'])) {
            $rules[] = 'in:' . implode(',', $property['enum']);
        }

        return $rules;
    }

    /**
     * Extract conditional visibility logic
     */
    protected function extractConditions(array $property, array $schema): ?array
    {
        if (!isset($property['x-condition'])) {
            return null;
        }

        return [
            'field' => $property['x-condition']['field'],
            'operator' => $property['x-condition']['operator'] ?? 'equals',
            'value' => $property['x-condition']['value'],
        ];
    }

    /**
     * Format key as human-readable label
     */
    protected function formatLabel(string $key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Services/SchemaRendererTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add src/Services/SchemaRenderer.php tests/Unit/Services/SchemaRendererTest.php
git commit -m "feat: add SchemaRenderer service for dynamic form generation"
```

---

## Task 6: Create ValidateCrispToken Middleware

**Files:**
- Create: `src/Http/Middleware/ValidateCrispToken.php`
- Test: `tests/Feature/Middleware/ValidateCrispTokenTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Middleware/ValidateCrispTokenTest.php`:

```php
<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests\Feature\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;
use Vntrungld\LaravelCrisp\Http\Middleware\ValidateCrispToken;
use Vntrungld\LaravelCrisp\LaravelCrisp;

class ValidateCrispTokenTest extends TestCase
{
    public function test_returns_401_when_token_missing(): void
    {
        $middleware = new ValidateCrispToken();
        $request = Request::create('/test', 'GET', ['website_id' => '123']);

        $response = $middleware->handle($request, fn() => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Missing authentication', $response->getContent());
    }

    public function test_returns_401_when_website_id_missing(): void
    {
        $middleware = new ValidateCrispToken();
        $request = Request::create('/test', 'GET', ['token' => 'abc']);

        $response = $middleware->handle($request, fn() => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_returns_401_when_token_invalid(): void
    {
        $crispMock = $this->createMock(LaravelCrisp::class);
        $crispMock->method('validateToken')->willReturn(false);

        $this->app->instance('laravel-crisp', $crispMock);

        $middleware = new ValidateCrispToken();
        $request = Request::create('/test', 'GET', [
            'token' => 'invalid',
            'website_id' => '123',
        ]);

        $response = $middleware->handle($request, fn() => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid token', $response->getContent());
    }

    public function test_allows_request_with_valid_token(): void
    {
        $crispMock = $this->createMock(LaravelCrisp::class);
        $crispMock->method('validateToken')->willReturn(true);

        $this->app->instance('laravel-crisp', $crispMock);

        $middleware = new ValidateCrispToken();
        $request = Request::create('/test', 'GET', [
            'token' => 'valid',
            'website_id' => '123',
        ]);

        $response = $middleware->handle($request, fn() => response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_caches_token_validation_result(): void
    {
        Cache::flush();

        $crispMock = $this->createMock(LaravelCrisp::class);
        $crispMock->expects($this->once())->method('validateToken')->willReturn(true);

        $this->app->instance('laravel-crisp', $crispMock);

        $middleware = new ValidateCrispToken();
        $request = Request::create('/test', 'GET', [
            'token' => 'valid',
            'website_id' => '123',
        ]);

        // First call
        $middleware->handle($request, fn() => response('OK'));

        // Second call - should use cache
        $middleware->handle($request, fn() => response('OK'));
    }
}
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Middleware/ValidateCrispTokenTest.php`
Expected: FAIL - Class not found

**Step 3: Implement ValidateCrispToken middleware**

Create `src/Http/Middleware/ValidateCrispToken.php`:

```php
<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ValidateCrispToken
{
    /**
     * Handle incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->query('token');
        $websiteId = $request->query('website_id');

        if (!$token || !$websiteId) {
            return response()->json(
                ['error' => 'Missing authentication'],
                401
            );
        }

        // Check cache first
        $cacheKey = "crisp.token.{$token}.{$websiteId}";
        $cacheTtl = config('crisp.settings.token_cache_ttl', 300);

        $isValid = Cache::remember($cacheKey, $cacheTtl, function () use ($token, $websiteId) {
            try {
                return app('laravel-crisp')->validateToken($token, $websiteId);
            } catch (\Exception $e) {
                return false;
            }
        });

        if (!$isValid) {
            return response()->json(
                ['error' => 'Invalid token'],
                401
            );
        }

        // Store validated website_id for component use
        $request->merge(['validated_website_id' => $websiteId]);

        $response = $next($request);

        // Add security headers
        $response->headers->set(
            'Content-Security-Policy',
            'frame-ancestors ' . implode(' ', config('crisp.settings.allowed_frame_origins'))
        );

        return $response;
    }
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Middleware/ValidateCrispTokenTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add src/Http/Middleware/ValidateCrispToken.php tests/Feature/Middleware/ValidateCrispTokenTest.php
git commit -m "feat: add token validation middleware for settings page"
```

---

## Task 7: Create Livewire CrispSettings Component

**Files:**
- Create: `src/Http/Livewire/CrispSettings.php`
- Create: `resources/views/livewire/crisp-settings.blade.php`
- Test: `tests/Feature/Livewire/CrispSettingsTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Livewire/CrispSettingsTest.php`:

```php
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

    public function test_component_can_be_rendered(): void
    {
        Http::fake([
            '*/plugin/*/settings/schema' => Http::response([
                'properties' => ['api_key' => ['type' => 'string']],
            ]),
            '*/plugin/*/subscription/*/settings' => Http::response(['data' => []]),
        ]);

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

        Http::fake([
            '*/plugin/*/settings/schema' => Http::response($schema),
            '*/plugin/*/subscription/*/settings' => Http::response(['data' => $settings]),
        ]);

        Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ])
            ->assertSet('settings', $settings)
            ->assertCount('fields', 1);
    }

    public function test_saves_settings_successfully(): void
    {
        Http::fake([
            '*/plugin/*/settings/schema' => Http::response([
                'properties' => ['api_key' => ['type' => 'string']],
            ]),
            '*/plugin/*/subscription/*/settings' => Http::sequence()
                ->push(['data' => []])
                ->push(['success' => true]),
        ]);

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
        Http::fake([
            '*/plugin/*/settings/schema' => Http::response([
                'properties' => ['api_key' => ['type' => 'string']],
            ]),
            '*/plugin/*/subscription/*/settings' => Http::sequence()
                ->push(['data' => []])
                ->push(['error' => 'Invalid'], 400),
        ]);

        Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ])
            ->call('save')
            ->assertSet('successMessage', null)
            ->assertSet('errorMessage', 'Crisp API Error: Invalid');
    }

    public function test_validates_required_fields(): void
    {
        Http::fake([
            '*/plugin/*/settings/schema' => Http::response([
                'properties' => [
                    'api_key' => ['type' => 'string'],
                ],
                'required' => ['api_key'],
            ]),
            '*/plugin/*/subscription/*/settings' => Http::response(['data' => []]),
        ]);

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
        Http::fake([
            '*/plugin/*/settings/schema' => Http::response([
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
            ]),
            '*/plugin/*/subscription/*/settings' => Http::response(['data' => []]),
        ]);

        $component = Livewire::test(CrispSettings::class, [
            'websiteId' => 'test-website',
            'token' => 'test-token',
        ]);

        // email field hidden when enabled = false
        $component->set('settings.enabled', false);
        $this->assertFalse($component->isFieldVisible($component->fields['email']));

        // email field visible when enabled = true
        $component->set('settings.enabled', true);
        $this->assertTrue($component->isFieldVisible($component->fields['email']));
    }
}
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/Livewire/CrispSettingsTest.php`
Expected: FAIL - Class not found

**Step 3: Implement CrispSettings component**

Create `src/Http/Livewire/CrispSettings.php`:

```php
<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Http\Livewire;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Vntrungld\LaravelCrisp\Exceptions\CrispApiException;
use Vntrungld\LaravelCrisp\LaravelCrisp;
use Vntrungld\LaravelCrisp\Services\SchemaRenderer;

class CrispSettings extends Component
{
    // Props from URL
    public string $websiteId;
    public string $token;

    // Component state
    public array $schema = [];
    public array $fields = [];
    public array $settings = [];
    public bool $loading = true;
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    /**
     * Mount component
     */
    public function mount(LaravelCrisp $crisp, SchemaRenderer $renderer): void
    {
        try {
            // Load schema
            $this->schema = $crisp->getPluginSchema();

            if (empty($this->schema)) {
                $this->errorMessage = 'Unable to load settings schema. Please try again later.';
                $this->loading = false;
                return;
            }

            // Render fields from schema
            $this->fields = $renderer->renderSchema($this->schema);

            // Load current settings
            $this->settings = $crisp->getWebsiteSettings($this->websiteId);

            // Merge defaults for missing values
            $this->settings = $this->mergeDefaults($this->settings, $this->fields);

        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to load settings: ' . $e->getMessage();
            Log::error('Crisp settings load failed', ['error' => $e]);
        }

        $this->loading = false;
    }

    /**
     * Save settings
     */
    public function save(LaravelCrisp $crisp): void
    {
        try {
            // Validate against schema-derived rules
            $this->validate($this->buildValidationRules());

            // Save to Crisp API
            $crisp->saveWebsiteSettings($this->websiteId, $this->settings);

            $this->successMessage = 'Settings saved successfully!';
            $this->errorMessage = null;

        } catch (ValidationException $e) {
            throw $e;

        } catch (CrispApiException $e) {
            $this->errorMessage = 'Crisp API Error: ' . $e->getMessage();
            Log::error('Crisp settings save failed', [
                'website_id' => $this->websiteId,
                'error' => $e->getMessage(),
                'settings' => $this->settings,
            ]);

        } catch (\Exception $e) {
            $this->errorMessage = 'An unexpected error occurred. Please try again.';
            Log::error('Settings save exception', ['error' => $e]);
        }
    }

    /**
     * Real-time validation
     */
    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    /**
     * Check if field should be visible
     */
    public function isFieldVisible(array $field): bool
    {
        if (!isset($field['conditions'])) {
            return true;
        }

        $condition = $field['conditions'];
        $dependentValue = data_get($this->settings, $condition['field']);

        return match($condition['operator']) {
            'equals' => $dependentValue === $condition['value'],
            'not_equals' => $dependentValue !== $condition['value'],
            'in' => in_array($dependentValue, $condition['value']),
            'not_in' => !in_array($dependentValue, $condition['value']),
            default => true,
        };
    }

    /**
     * Add item to array field
     */
    public function addArrayItem(string $fieldKey): void
    {
        $this->settings[$fieldKey][] = $this->getDefaultArrayItem($fieldKey);
    }

    /**
     * Remove item from array field
     */
    public function removeArrayItem(string $fieldKey, int $index): void
    {
        unset($this->settings[$fieldKey][$index]);
        $this->settings[$fieldKey] = array_values($this->settings[$fieldKey]);
    }

    /**
     * Build validation rules from fields
     */
    protected function buildValidationRules(): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $fieldRules = [];

            if ($field['required']) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            $fieldRules = array_merge($fieldRules, $field['validation']);

            $rules["settings.{$field['key']}"] = implode('|', $fieldRules);
        }

        return $rules;
    }

    /**
     * Merge default values into settings
     */
    protected function mergeDefaults(array $settings, array $fields): array
    {
        foreach ($fields as $field) {
            if (!isset($settings[$field['key']]) && $field['default'] !== null) {
                $settings[$field['key']] = $field['default'];
            }
        }

        return $settings;
    }

    /**
     * Get default item for array field
     */
    protected function getDefaultArrayItem(string $fieldKey): mixed
    {
        $field = $this->fields[$fieldKey] ?? null;

        if (!$field || $field['type'] !== 'array') {
            return null;
        }

        $itemType = $field['items']['type'] ?? 'string';

        return match($itemType) {
            'string' => '',
            'number', 'integer' => 0,
            'boolean' => false,
            'object' => [],
            default => null,
        };
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('laravel-crisp::livewire.crisp-settings');
    }
}
```

**Step 4: Create basic view**

Create `resources/views/livewire/crisp-settings.blade.php`:

```blade
<div class="max-w-4xl mx-auto p-6 bg-white">
    @if($loading)
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
    @else
        <form wire:submit.prevent="save" class="space-y-6">
            <!-- Messages -->
            @if($successMessage)
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    {{ $successMessage }}
                </div>
            @endif

            @if($errorMessage)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {{ $errorMessage }}
                </div>
            @endif

            <!-- Fields placeholder -->
            <div>Fields will render here</div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t">
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                    <span wire:loading.remove>Save Settings</span>
                    <span wire:loading>Saving...</span>
                </button>
            </div>
        </form>
    @endif
</div>
```

**Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/Livewire/CrispSettingsTest.php`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Http/Livewire/CrispSettings.php resources/views/livewire/crisp-settings.blade.php tests/Feature/Livewire/CrispSettingsTest.php
git commit -m "feat: add CrispSettings Livewire component"
```

---

## Task 8: Create Field Blade Partials (String, Number, Boolean)

**Files:**
- Create: `resources/views/fields/string.blade.php`
- Create: `resources/views/fields/number.blade.php`
- Create: `resources/views/fields/boolean.blade.php`
- Test: Manual rendering test

**Step 1: Create string field partial**

Create `resources/views/fields/string.blade.php`:

```blade
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">
        {{ $field['label'] }}
        @if($field['required'])
            <span class="text-red-500">*</span>
        @endif
    </label>

    @if($field['description'])
        <p class="text-sm text-gray-500 mb-2">{{ $field['description'] }}</p>
    @endif

    <input
        type="text"
        wire:model="settings.{{ $field['key'] }}"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        @if($field['required']) required @endif
    >

    @error('settings.' . $field['key'])
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

**Step 2: Create number field partial**

Create `resources/views/fields/number.blade.php`:

```blade
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">
        {{ $field['label'] }}
        @if($field['required'])
            <span class="text-red-500">*</span>
        @endif
    </label>

    @if($field['description'])
        <p class="text-sm text-gray-500 mb-2">{{ $field['description'] }}</p>
    @endif

    <input
        type="number"
        wire:model="settings.{{ $field['key'] }}"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        @if($field['required']) required @endif
    >

    @error('settings.' . $field['key'])
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

**Step 3: Create boolean field partial**

Create `resources/views/fields/boolean.blade.php`:

```blade
<div class="mb-4">
    <label class="flex items-center cursor-pointer">
        <input
            type="checkbox"
            wire:model="settings.{{ $field['key'] }}"
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
        >
        <span class="ml-2 text-sm font-medium text-gray-700">
            {{ $field['label'] }}
        </span>
    </label>

    @if($field['description'])
        <p class="ml-6 text-sm text-gray-500 mt-1">{{ $field['description'] }}</p>
    @endif

    @error('settings.' . $field['key'])
        <p class="ml-6 mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

**Step 4: Update main view to use partials**

Modify `resources/views/livewire/crisp-settings.blade.php`:

Replace `<div>Fields will render here</div>` with:

```blade
<!-- Dynamic Fields -->
@foreach($fields as $field)
    @if($this->isFieldVisible($field))
        <div wire:key="field-{{ $field['key'] }}">
            @include('laravel-crisp::fields.' . $field['type'], ['field' => $field])
        </div>
    @endif
@endforeach
```

**Step 5: Test rendering manually**

Run: `php artisan serve` and visit the settings page
Expected: Fields render correctly

**Step 6: Commit**

```bash
git add resources/views/fields/string.blade.php resources/views/fields/number.blade.php resources/views/fields/boolean.blade.php resources/views/livewire/crisp-settings.blade.php
git commit -m "feat: add basic field type partials (string, number, boolean)"
```

---

## Task 9: Create Additional Field Partials (Select, Textarea, Integer)

**Files:**
- Create: `resources/views/fields/select.blade.php`
- Create: `resources/views/fields/textarea.blade.php`
- Create: `resources/views/fields/integer.blade.php`

**Step 1: Create select field partial**

Create `resources/views/fields/select.blade.php`:

```blade
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">
        {{ $field['label'] }}
        @if($field['required'])
            <span class="text-red-500">*</span>
        @endif
    </label>

    @if($field['description'])
        <p class="text-sm text-gray-500 mb-2">{{ $field['description'] }}</p>
    @endif

    <select
        wire:model="settings.{{ $field['key'] }}"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        @if($field['required']) required @endif
    >
        <option value="">-- Select --</option>
        @foreach($field['options'] ?? [] as $option)
            <option value="{{ $option }}">{{ ucfirst($option) }}</option>
        @endforeach
    </select>

    @error('settings.' . $field['key'])
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

**Step 2: Create textarea field partial**

Create `resources/views/fields/textarea.blade.php`:

```blade
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">
        {{ $field['label'] }}
        @if($field['required'])
            <span class="text-red-500">*</span>
        @endif
    </label>

    @if($field['description'])
        <p class="text-sm text-gray-500 mb-2">{{ $field['description'] }}</p>
    @endif

    <textarea
        wire:model="settings.{{ $field['key'] }}"
        rows="4"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        @if($field['required']) required @endif
    ></textarea>

    @error('settings.' . $field['key'])
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

**Step 3: Create integer field partial**

Create `resources/views/fields/integer.blade.php`:

```blade
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-1">
        {{ $field['label'] }}
        @if($field['required'])
            <span class="text-red-500">*</span>
        @endif
    </label>

    @if($field['description'])
        <p class="text-sm text-gray-500 mb-2">{{ $field['description'] }}</p>
    @endif

    <input
        type="number"
        step="1"
        wire:model="settings.{{ $field['key'] }}"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        @if($field['required']) required @endif
    >

    @error('settings.' . $field['key'])
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

**Step 4: Commit**

```bash
git add resources/views/fields/
git commit -m "feat: add select, textarea, and integer field partials"
```

---

## Task 10: Create Advanced Field Partials (Object, Array)

**Files:**
- Create: `resources/views/fields/object.blade.php`
- Create: `resources/views/fields/array.blade.php`

**Step 1: Create object field partial**

Create `resources/views/fields/object.blade.php`:

```blade
<fieldset class="mb-4 border border-gray-200 rounded-md p-4">
    <legend class="text-sm font-medium text-gray-700 px-2">
        {{ $field['label'] }}
        @if($field['required'])
            <span class="text-red-500">*</span>
        @endif
    </legend>

    @if($field['description'])
        <p class="text-sm text-gray-500 mb-3">{{ $field['description'] }}</p>
    @endif

    @foreach($field['properties'] ?? [] as $key => $property)
        @php
            $nestedField = [
                'key' => $field['key'] . '.' . $key,
                'type' => $property['type'] ?? 'string',
                'label' => $property['title'] ?? ucfirst(str_replace('_', ' ', $key)),
                'description' => $property['description'] ?? null,
                'required' => in_array($key, $property['required'] ?? []),
                'default' => $property['default'] ?? null,
                'validation' => [],
                'options' => $property['enum'] ?? null,
            ];
        @endphp

        @include('laravel-crisp::fields.' . $nestedField['type'], ['field' => $nestedField])
    @endforeach
</fieldset>
```

**Step 2: Create array field partial**

Create `resources/views/fields/array.blade.php`:

```blade
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ $field['label'] }}
        @if($field['required'])
            <span class="text-red-500">*</span>
        @endif
    </label>

    @if($field['description'])
        <p class="text-sm text-gray-500 mb-3">{{ $field['description'] }}</p>
    @endif

    <div class="space-y-3">
        @foreach($this->settings[$field['key']] ?? [] as $index => $item)
            <div class="flex gap-2 items-start p-3 bg-gray-50 rounded-md border border-gray-200">
                <div class="flex-1">
                    @php
                        $itemType = $field['items']['type'] ?? 'string';
                        $itemField = [
                            'key' => $field['key'] . '.' . $index,
                            'type' => $itemType,
                            'label' => 'Item ' . ($index + 1),
                            'description' => null,
                            'required' => false,
                            'default' => null,
                            'validation' => [],
                            'options' => $field['items']['enum'] ?? null,
                        ];
                    @endphp

                    @if($itemType === 'object')
                        @foreach($field['items']['properties'] ?? [] as $key => $property)
                            @php
                                $nestedField = [
                                    'key' => $field['key'] . '.' . $index . '.' . $key,
                                    'type' => $property['type'] ?? 'string',
                                    'label' => $property['title'] ?? ucfirst(str_replace('_', ' ', $key)),
                                    'description' => null,
                                    'required' => false,
                                    'validation' => [],
                                    'options' => $property['enum'] ?? null,
                                ];
                            @endphp
                            @include('laravel-crisp::fields.' . $nestedField['type'], ['field' => $nestedField])
                        @endforeach
                    @else
                        @include('laravel-crisp::fields.' . $itemType, ['field' => $itemField])
                    @endif
                </div>

                <button
                    type="button"
                    wire:click="removeArrayItem('{{ $field['key'] }}', {{ $index }})"
                    class="px-3 py-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded"
                    title="Remove item"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endforeach

        <button
            type="button"
            wire:click="addArrayItem('{{ $field['key'] }}')"
            class="inline-flex items-center px-4 py-2 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded"
        >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Item
        </button>
    </div>

    @error('settings.' . $field['key'])
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

**Step 3: Commit**

```bash
git add resources/views/fields/object.blade.php resources/views/fields/array.blade.php
git commit -m "feat: add object and array field partials for nested data"
```

---

## Task 11: Register Routes and Middleware in Service Provider

**Files:**
- Modify: `src/LaravelCrispServiceProvider.php`

**Step 1: Update service provider with routes and middleware**

Modify `src/LaravelCrispServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Vntrungld\LaravelCrisp\Http\Livewire\CrispSettings;
use Vntrungld\LaravelCrisp\Http\Middleware\ValidateCrispToken;

class LaravelCrispServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/crisp.php', 'crisp');

        $this->app->singleton('laravel-crisp', function ($app) {
            return new LaravelCrisp(new \Crisp\CrispClient());
        });

        $this->app->singleton(Services\SchemaRenderer::class);
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/crisp.php' => config_path('crisp.php'),
        ], 'laravel-crisp.config');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

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

    protected function registerSettingsRoutes(): void
    {
        Route::middleware(['web', ValidateCrispToken::class])
            ->prefix(config('crisp.settings.route_path', 'crisp/settings'))
            ->group(function () {
                Route::get('/', CrispSettings::class)->name('crisp.settings');
            });
    }
}
```

**Step 2: Verify routes are registered**

Run: `php artisan route:list | grep crisp`
Expected: Shows crisp.settings route

**Step 3: Commit**

```bash
git add src/LaravelCrispServiceProvider.php
git commit -m "feat: register settings routes and Livewire component"
```

---

## Task 12: Create Test Fixtures for Mocking

**Files:**
- Create: `tests/Fixtures/CrispApiMock.php`

**Step 1: Create CrispApiMock fixture**

Create `tests/Fixtures/CrispApiMock.php`:

```php
<?php

declare(strict_types=1);

namespace Vntrungld\LaravelCrisp\Tests\Fixtures;

class CrispApiMock
{
    /**
     * Get simple schema with basic fields
     */
    public static function simpleSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'api_key' => [
                    'type' => 'string',
                    'title' => 'API Key',
                    'description' => 'Your API key',
                    'maxLength' => 100,
                ],
                'enabled' => [
                    'type' => 'boolean',
                    'title' => 'Enabled',
                    'default' => true,
                ],
            ],
            'required' => ['api_key'],
        ];
    }

    /**
     * Get complex nested schema
     */
    public static function complexNestedSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'general' => [
                    'type' => 'object',
                    'title' => 'General Settings',
                    'properties' => [
                        'app_name' => ['type' => 'string', 'title' => 'App Name'],
                        'timeout' => ['type' => 'integer', 'title' => 'Timeout', 'minimum' => 1],
                    ],
                ],
                'notifications' => [
                    'type' => 'object',
                    'title' => 'Notifications',
                    'properties' => [
                        'enabled' => ['type' => 'boolean', 'title' => 'Enable Notifications'],
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'title' => 'Email',
                            'x-condition' => [
                                'field' => 'notifications.enabled',
                                'value' => true,
                            ],
                        ],
                    ],
                ],
                'webhooks' => [
                    'type' => 'array',
                    'title' => 'Webhooks',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'url' => ['type' => 'string', 'format' => 'url'],
                            'secret' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get schema with all field types
     */
    public static function allFieldTypesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'text_field' => ['type' => 'string', 'title' => 'Text'],
                'number_field' => ['type' => 'number', 'title' => 'Number'],
                'integer_field' => ['type' => 'integer', 'title' => 'Integer'],
                'boolean_field' => ['type' => 'boolean', 'title' => 'Boolean'],
                'select_field' => [
                    'type' => 'string',
                    'enum' => ['option1', 'option2', 'option3'],
                    'title' => 'Select',
                ],
                'textarea_field' => [
                    'type' => 'string',
                    'format' => 'textarea',
                    'title' => 'Textarea',
                ],
                'email_field' => [
                    'type' => 'string',
                    'format' => 'email',
                    'title' => 'Email',
                ],
            ],
        ];
    }

    /**
     * Get settings response
     */
    public static function settingsResponse(array $data): array
    {
        return ['data' => $data];
    }

    /**
     * Get successful save response
     */
    public static function saveSuccessResponse(): array
    {
        return ['success' => true];
    }

    /**
     * Get error response
     */
    public static function errorResponse(string $message, int $code = 400): array
    {
        return [
            'error' => $message,
            'code' => $code,
        ];
    }
}
```

**Step 2: Commit**

```bash
git add tests/Fixtures/CrispApiMock.php
git commit -m "test: add CrispApiMock fixture for consistent test data"
```

---

## Task 13: Add Integration Tests

**Files:**
- Create: `tests/Feature/SettingsPageIntegrationTest.php`

**Step 1: Write integration tests**

Create `tests/Feature/SettingsPageIntegrationTest.php`:

```php
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

    protected function defineEnvironment($app)
    {
        $app['config']->set('crisp.plugin_id', 'test-plugin');
        $app['config']->set('crisp.token_id', 'test-token');
        $app['config']->set('crisp.token_key', 'test-key');
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
```

**Step 2: Run tests**

Run: `vendor/bin/phpunit tests/Feature/SettingsPageIntegrationTest.php`
Expected: PASS

**Step 3: Commit**

```bash
git add tests/Feature/SettingsPageIntegrationTest.php
git commit -m "test: add integration tests for complete settings flow"
```

---

## Task 14: Update README with Settings Documentation

**Files:**
- Modify: `readme.md`

**Step 1: Add settings section to README**

Add to `readme.md` after webhook section:

```markdown
### Plugin Settings Page

The package includes a dynamic settings page that embeds in the Crisp Dashboard. Settings are defined via JSON Schema in the Crisp Marketplace and automatically rendered as forms.

#### Accessing the Settings Page

The settings page is available at `/crisp/settings` and requires authentication via token:

```
https://yourapp.com/crisp/settings?token=xxx&website_id=yyy
```

Crisp automatically passes these parameters when loading the settings iframe.

#### Supported Field Types

The settings page dynamically supports all JSON Schema field types:

- **String**: Text input, email, URL, password
- **Number/Integer**: Numeric input with min/max validation
- **Boolean**: Checkbox toggle
- **Enum**: Select dropdown
- **Textarea**: Multi-line text input
- **Object**: Nested field groups
- **Array**: Repeatable items with add/remove
- **Conditional Fields**: Show/hide based on other field values

#### Configuration

Settings page configuration in `config/crisp.php`:

```php
'settings' => [
    'route_path' => 'crisp/settings',
    'token_cache_ttl' => 300, // Cache token validation for 5 minutes
    'allowed_frame_origins' => [
        'https://app.crisp.chat',
        'https://app.crisp.im',
    ],
],
```

#### JSON Schema Example

Define your settings schema in the Crisp Marketplace:

```json
{
  "type": "object",
  "properties": {
    "api_key": {
      "type": "string",
      "title": "API Key",
      "description": "Your integration API key",
      "maxLength": 100
    },
    "enable_notifications": {
      "type": "boolean",
      "title": "Enable Notifications",
      "default": true
    },
    "notification_email": {
      "type": "string",
      "format": "email",
      "title": "Notification Email",
      "x-condition": {
        "field": "enable_notifications",
        "value": true
      }
    }
  },
  "required": ["api_key"]
}
```

#### Conditional Fields

Use `x-condition` to show/hide fields based on other values:

```json
{
  "field": "enable_notifications",
  "operator": "equals",
  "value": true
}
```

Supported operators: `equals`, `not_equals`, `in`, `not_in`

#### Accessing Settings in Code

Retrieve settings for a specific website:

```php
$settings = LaravelCrisp::getWebsiteSettings('website-id');
$apiKey = $settings['api_key'] ?? null;
```

Save settings programmatically:

```php
LaravelCrisp::saveWebsiteSettings('website-id', [
    'api_key' => 'new-key',
    'enable_notifications' => true,
]);
```
```

**Step 2: Commit**

```bash
git add readme.md
git commit -m "docs: add settings page documentation to README"
```

---

## Task 15: Run Full Test Suite and Fix Issues

**Files:**
- All test files

**Step 1: Run complete test suite**

Run: `composer test`
Expected: All tests pass

**Step 2: Fix any failing tests**

If tests fail:
1. Read error messages carefully
2. Fix implementation or test as needed
3. Re-run tests

**Step 3: Verify test coverage**

Run: `composer test-coverage` (if configured)
Expected: Good coverage on core components

**Step 4: Commit any fixes**

```bash
git add .
git commit -m "test: fix test suite issues and ensure all tests pass"
```

---

## Task 16: Final Manual Testing

**Files:**
- N/A

**Step 1: Start development server**

Run: `php artisan serve`

**Step 2: Test with mock schema**

Create test route in `routes/web.php`:

```php
Route::get('/test-settings', function () {
    return view('laravel-crisp::livewire.crisp-settings', [
        'websiteId' => 'test',
        'token' => 'test',
    ]);
});
```

Visit: `http://localhost:8000/test-settings`

**Step 3: Verify all field types render**

- Text inputs work
- Number inputs validate
- Checkboxes toggle
- Select dropdowns function
- Nested objects display
- Arrays can add/remove items
- Conditional fields show/hide

**Step 4: Test form submission**

Fill out form and submit
Expected: Success/error messages display correctly

**Step 5: Remove test route**

Remove test route from `routes/web.php`

**Step 6: Commit**

```bash
git add .
git commit -m "chore: manual testing complete, all functionality verified"
```

---

## Task 17: Create Final Documentation

**Files:**
- Create: `docs/SETTINGS.md`

**Step 1: Write comprehensive settings guide**

Create `docs/SETTINGS.md` with detailed documentation covering:
- Architecture overview
- JSON Schema guide
- Field type reference
- Conditional fields
- Validation rules
- Testing guide
- Troubleshooting

**Step 2: Commit**

```bash
git add docs/SETTINGS.md
git commit -m "docs: add comprehensive settings page guide"
```

---

## Summary

**Implementation complete!** This plan delivers:

✅ Livewire-powered dynamic settings page
✅ JSON Schema to form field rendering
✅ All field types (string, number, boolean, select, textarea, object, array)
✅ Conditional field visibility
✅ Token-based authentication
✅ Comprehensive validation
✅ Full test coverage
✅ Complete documentation

**Next Steps:**
1. Test with real Crisp plugin
2. Deploy to staging
3. Gather user feedback
4. Iterate on UX improvements
