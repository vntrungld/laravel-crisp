# Crisp Plugin Settings Page

## Overview

The Laravel Crisp package includes a dynamic, schema-driven settings page that embeds directly in the Crisp Dashboard. This feature allows Crisp plugin developers to define settings using JSON Schema in the Crisp Marketplace, which are then automatically rendered as interactive forms in your Laravel application.

## Architecture

### Components

The settings page system consists of several key components:

1. **CrispSettings Livewire Component** (`src/Http/Livewire/CrispSettings.php`)
   - Orchestrates the entire settings flow
   - Loads JSON Schema from Crisp API
   - Renders dynamic forms based on schema
   - Handles validation and settings persistence

2. **SchemaRenderer Service** (`src/Services/SchemaRenderer.php`)
   - Converts JSON Schema to field definitions
   - Extracts Laravel validation rules from schema constraints
   - Handles conditional field logic

3. **ValidateCrispToken Middleware** (`src/Http/Middleware/ValidateCrispToken.php`)
   - Token-based authentication
   - 5-minute caching for performance
   - CSP headers for iframe security

4. **Field Partials** (`resources/views/fields/*.blade.php`)
   - Blade templates for each field type
   - Consistent styling with Tailwind CSS
   - Support for nested structures (objects, arrays)

### Flow

1. User navigates to plugin settings in Crisp Dashboard
2. Crisp loads iframe pointing to `/crisp/settings?token=xxx&website_id=yyy`
3. Middleware validates token and caches result
4. Component fetches JSON Schema from Crisp API
5. SchemaRenderer converts schema to field definitions
6. Component loads current settings for the website
7. Dynamic form renders with field partials
8. User updates settings and submits
9. Validation runs against schema-derived rules
10. Settings saved to Crisp API

## Configuration

### Environment Variables

```env
CRISP_PLUGIN_ID=your-plugin-id
CRISP_TOKEN_ID=your-token-id
CRISP_TOKEN_KEY=your-token-key
CRISP_SETTINGS_PATH=crisp/settings
CRISP_TOKEN_CACHE_TTL=300
```

### Config File (`config/crisp.php`)

```php
'settings' => [
    'route_path' => env('CRISP_SETTINGS_PATH', 'crisp/settings'),
    'token_cache_ttl' => (int) env('CRISP_TOKEN_CACHE_TTL', 300),
    'allowed_frame_origins' => [
        'https://app.crisp.chat',
        'https://app.crisp.im',
    ],
],
```

## JSON Schema Guide

### Basic Schema Structure

```json
{
  "type": "object",
  "properties": {
    "field_name": {
      "type": "string",
      "title": "Display Label",
      "description": "Helper text shown below the field",
      "default": "default value"
    }
  },
  "required": ["field_name"]
}
```

### Field Types Reference

#### String Field

```json
{
  "api_key": {
    "type": "string",
    "title": "API Key",
    "description": "Your integration API key",
    "minLength": 10,
    "maxLength": 100,
    "pattern": "^[A-Za-z0-9]+$"
  }
}
```

**Validation rules extracted:**
- `minLength` → `min:10`
- `maxLength` → `max:100`
- `pattern` → `regex:/^[A-Za-z0-9]+$/`

#### Email Field

```json
{
  "email": {
    "type": "string",
    "format": "email",
    "title": "Email Address"
  }
}
```

**Validation rules:** `email`

#### Number/Integer Field

```json
{
  "timeout": {
    "type": "integer",
    "title": "Timeout (seconds)",
    "minimum": 1,
    "maximum": 60,
    "default": 30
  }
}
```

**Validation rules:**
- `type: integer` → `numeric`
- `minimum` → `min:1`
- `maximum` → `max:60`

#### Boolean Field

```json
{
  "enabled": {
    "type": "boolean",
    "title": "Enable Feature",
    "default": true
  }
}
```

**Validation rules:** `boolean`

#### Select Field (Enum)

```json
{
  "log_level": {
    "type": "string",
    "title": "Log Level",
    "enum": ["debug", "info", "warning", "error"],
    "default": "info"
  }
}
```

**Validation rules:** `in:debug,info,warning,error`

#### Textarea Field

```json
{
  "description": {
    "type": "string",
    "format": "textarea",
    "title": "Description",
    "minLength": 10,
    "maxLength": 500
  }
}
```

#### Object Field (Nested)

```json
{
  "credentials": {
    "type": "object",
    "title": "API Credentials",
    "properties": {
      "username": {
        "type": "string",
        "title": "Username"
      },
      "password": {
        "type": "string",
        "title": "Password"
      }
    },
    "required": ["username"]
  }
}
```

#### Array Field (Repeatable)

```json
{
  "webhooks": {
    "type": "array",
    "title": "Webhook URLs",
    "items": {
      "type": "object",
      "properties": {
        "url": {
          "type": "string",
          "format": "url",
          "title": "URL"
        },
        "secret": {
          "type": "string",
          "title": "Secret"
        }
      }
    }
  }
}
```

### Conditional Fields

Show/hide fields based on other field values using `x-condition`:

```json
{
  "properties": {
    "enable_notifications": {
      "type": "boolean",
      "title": "Enable Notifications"
    },
    "notification_email": {
      "type": "string",
      "format": "email",
      "title": "Notification Email",
      "x-condition": {
        "field": "enable_notifications",
        "operator": "equals",
        "value": true
      }
    }
  }
}
```

**Supported operators:**
- `equals`: Field value equals specified value
- `not_equals`: Field value does not equal specified value
- `in`: Field value is in array of values
- `not_in`: Field value is not in array of values

### Nested Conditional Fields

```json
{
  "x-condition": {
    "field": "parent.child.enabled",
    "operator": "equals",
    "value": true
  }
}
```

## Validation

### Automatic Validation Rules

The SchemaRenderer automatically converts JSON Schema constraints to Laravel validation rules:

| JSON Schema | Laravel Rule |
|-------------|--------------|
| `minLength` | `min:X` |
| `maxLength` | `max:X` |
| `pattern` | `regex:X` |
| `format: email` | `email` |
| `format: url` | `url` |
| `format: uuid` | `uuid` |
| `minimum` | `min:X` |
| `maximum` | `max:X` |
| `enum` | `in:a,b,c` |
| `required` | `required` |

### Validation Error Display

- Inline errors appear below each field
- Global errors show in a banner at the top
- Real-time validation on form submission
- Error messages are user-friendly

## Programmatic Access

### Reading Settings

```php
use Vntrungld\LaravelCrisp\LaravelCrisp;

// Via facade
$settings = LaravelCrisp::getWebsiteSettings('website-id');

// Via dependency injection
public function __construct(protected LaravelCrisp $crisp) {}

public function getSettings(string $websiteId): array
{
    return $this->crisp->getWebsiteSettings($websiteId);
}

// Access specific setting
$apiKey = $settings['api_key'] ?? null;
$enabled = $settings['enabled'] ?? false;

// Nested settings
$username = $settings['credentials']['username'] ?? null;
```

### Writing Settings

```php
use Vntrungld\LaravelCrisp\LaravelCrisp;

LaravelCrisp::saveWebsiteSettings('website-id', [
    'api_key' => 'new-key',
    'enabled' => true,
    'credentials' => [
        'username' => 'admin',
        'password' => 'secret',
    ],
]);
```

### Getting Schema

```php
$schema = LaravelCrisp::getPluginSchema();
```

## Testing

### Unit Tests

```php
use Vntrungld\LaravelCrisp\Services\SchemaRenderer;

public function test_renders_string_field(): void
{
    $renderer = new SchemaRenderer();

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

    $fields = $renderer->renderSchema($schema);

    $this->assertEquals('string', $fields['api_key']['type']);
    $this->assertEquals('API Key', $fields['api_key']['label']);
    $this->assertTrue($fields['api_key']['required']);
    $this->assertContains('max:100', $fields['api_key']['validation']);
}
```

### Integration Tests

```php
use Illuminate\Support\Facades\Http;

public function test_full_settings_flow(): void
{
    Http::fake([
        '*/plugin/*/subscription/*/verify' => Http::response(['valid' => true]),
        '*/plugin/*/settings/schema' => Http::response([
            'properties' => ['api_key' => ['type' => 'string']],
        ]),
        '*/plugin/*/subscription/*/settings' => Http::response(['data' => []]),
    ]);

    $response = $this->get('/crisp/settings?token=valid&website_id=test');

    $response->assertOk();
    $response->assertSee('API Key');
}
```

### Test Fixtures

Use `CrispApiMock` for consistent test data:

```php
use Vntrungld\LaravelCrisp\Tests\Fixtures\CrispApiMock;

Http::fake([
    '*/settings/schema' => Http::response(CrispApiMock::simpleSchema()),
    '*/settings' => Http::response(CrispApiMock::settingsResponse(['key' => 'value'])),
]);
```

## Troubleshooting

### Token Validation Fails

**Symptom:** 401 error with "Invalid token"

**Solutions:**
1. Verify `CRISP_PLUGIN_ID`, `CRISP_TOKEN_ID`, and `CRISP_TOKEN_KEY` are correct
2. Clear token cache: `php artisan cache:forget crisp.token.*`
3. Check Crisp plugin credentials in dashboard

### Schema Not Loading

**Symptom:** "Unable to load settings schema"

**Solutions:**
1. Verify schema is defined in Crisp Marketplace
2. Check API credentials have correct permissions
3. Review logs: `tail -f storage/logs/laravel.log | grep Crisp`
4. Test API access manually:
   ```php
   $crisp = app('laravel-crisp');
   $schema = $crisp->getPluginSchema();
   dd($schema);
   ```

### Field Not Rendering

**Symptom:** Missing or blank field

**Solutions:**
1. Verify field type is supported (string, number, boolean, select, textarea, object, array, integer)
2. Check for typos in `type` property
3. Ensure field partial exists: `resources/views/fields/{type}.blade.php`
4. Check browser console for JavaScript errors

### Conditional Fields Not Working

**Symptom:** Field doesn't show/hide based on condition

**Solutions:**
1. Verify `x-condition.field` path is correct (use dot notation for nested fields)
2. Check operator is one of: `equals`, `not_equals`, `in`, `not_in`
3. Ensure dependent field value type matches condition value type
4. Test in browser console:
   ```javascript
   Livewire.find('component-id').$get('settings.field_name')
   ```

### Validation Errors Not Showing

**Symptom:** Form submits but validation errors don't display

**Solutions:**
1. Check field key matches schema property key exactly
2. Verify `@error` directive is in field partial
3. Ensure validation rules are being extracted (check `SchemaRenderer`)
4. Review Laravel logs for validation failures

### Settings Not Saving

**Symptom:** "Settings saved" message but values don't persist

**Solutions:**
1. Verify API credentials have write permissions
2. Check website_id is correct
3. Review Crisp API response in logs
4. Test manual save:
   ```php
   LaravelCrisp::saveWebsiteSettings('website-id', ['test' => 'value']);
   ```

### Iframe Not Loading

**Symptom:** Blank page or CORS errors in Crisp Dashboard

**Solutions:**
1. Verify `allowed_frame_origins` includes Crisp domains
2. Check route is registered: `php artisan route:list | grep crisp.settings`
3. Ensure no conflicting middleware blocking iframe rendering
4. Test URL directly: `https://yourapp.com/crisp/settings?token=xxx&website_id=yyy`

### Performance Issues

**Symptom:** Slow page load or timeouts

**Solutions:**
1. Increase `token_cache_ttl` to reduce API calls
2. Enable Laravel cache for better performance
3. Optimize field count (consider grouping in objects)
4. Check for N+1 queries in custom hooks

## Security

### Token Validation

- Tokens are validated on every request
- Results cached for 5 minutes (configurable)
- Invalid tokens result in 401 response
- Timing-safe comparison prevents timing attacks

### CSP Headers

Frame-ancestors CSP header restricts embedding to Crisp domains only:
- `https://app.crisp.chat`
- `https://app.crisp.im`

### CSRF Protection

CSRF validation is handled by ValidateCrispToken middleware using Crisp's token system instead of Laravel's default CSRF tokens.

## Advanced Usage

### Custom Field Types

Extend with custom field types by creating new partials in `resources/views/fields/`:

```blade
<!-- resources/views/vendor/laravel-crisp/fields/custom.blade.php -->
<div class="mb-4">
    <label>{{ $field['label'] }}</label>
    <!-- Your custom field implementation -->
</div>
```

### Custom Validation

Override validation in your schema using Laravel's validation syntax:

```json
{
  "custom_field": {
    "type": "string",
    "title": "Custom",
    "x-validation": "required|string|min:10|custom_rule"
  }
}
```

### Publish Views

Customize field templates:

```bash
php artisan vendor:publish --tag="laravel-crisp.views"
```

Edit files in `resources/views/vendor/laravel-crisp/`.

## Best Practices

1. **Keep schemas simple**: Group related fields in objects
2. **Use descriptive titles**: Clear labels improve UX
3. **Provide descriptions**: Help text reduces support requests
4. **Set sensible defaults**: Pre-fill common values
5. **Validate appropriately**: Balance security with usability
6. **Test conditionals**: Verify show/hide logic works correctly
7. **Cache aggressively**: Set appropriate TTL for your use case
8. **Monitor logs**: Watch for API errors or validation failures
9. **Version schemas carefully**: Avoid breaking changes
10. **Document settings**: Provide examples for complex configurations

## Examples

See `tests/Fixtures/CrispApiMock.php` for complete schema examples covering all field types and scenarios.
