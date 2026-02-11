# Crisp Plugin Settings Page Design

**Date:** 2026-02-11
**Status:** Approved
**Technology Stack:** Laravel + Livewire + Tailwind CSS

## Overview

A dynamic, schema-driven settings page that embeds in the Crisp Dashboard via iframe, automatically rendering form fields based on JSON Schema definitions and handling complex data types including nested objects, arrays, and conditional fields.

## Architecture

### High-Level Flow

```
Crisp Dashboard → iframe URL with token & website_id
                ↓
        Laravel Route (middleware validates token)
                ↓
        Livewire Component loads
                ↓
        1. Validate token with Crisp API
        2. Fetch plugin schema from Crisp API
        3. Fetch current settings for website_id
        4. Render dynamic form based on schema
                ↓
        User edits settings
                ↓
        Save settings back to Crisp API
```

### Key Components

1. **Settings Controller/Route** - Entry point for iframe, handles initial token validation
2. **Livewire CrispSettings Component** - Main reactive component that orchestrates everything
3. **SchemaRenderer Service** - Converts JSON Schema into Livewire form fields
4. **Crisp API Client Extensions** - Extends existing LaravelCrisp class with settings endpoints
5. **ValidateCrispToken Middleware** - Validates Crisp tokens and website_id

### Data Flow

- Schema fetched once on component mount from Crisp API
- Settings loaded for specific website_id
- Form fields dynamically generated based on schema types
- Validation rules extracted from schema
- On submit, settings saved via Crisp API

## Authentication & Security

### Token Validation Flow

When Crisp loads the settings page in an iframe:
```
https://yourapp.com/crisp/settings?website_id=xxx&token=yyy
```

**ValidateCrispToken Middleware:**

1. Extract token and website_id from request
2. Call Crisp API to verify token is valid
3. Verify token grants access to specified website_id
4. Store verified website_id in session for subsequent requests
5. Return 401 if validation fails

**Security Measures:**

- **Token caching**: Cache validation results for 5 minutes to reduce API calls
- **CSRF exemption**: Exempt settings route from CSRF (validated via Crisp token instead)
- **Frame options**: Allow framing from Crisp domains only
- **Rate limiting**: Prevent abuse of settings endpoints

**Configuration:**

```php
'settings' => [
    'route_path' => 'crisp/settings',
    'token_cache_ttl' => 300, // 5 minutes
    'allowed_frame_origins' => ['https://app.crisp.chat'],
],
```

## Crisp API Integration

### Extended Methods

Add to `Vntrungld\LaravelCrisp\LaravelCrisp`:

```php
public function validateToken(string $token, string $websiteId): bool
// Verify token has access to website

public function getPluginSchema(): array
// GET /v1/plugin/:plugin_id/settings/schema
// Returns JSON Schema definition

public function getWebsiteSettings(string $websiteId): array
// GET /v1/plugin/:plugin_id/subscription/:website_id/settings
// Returns current settings for this website

public function saveWebsiteSettings(string $websiteId, array $settings): bool
// POST /v1/plugin/:plugin_id/subscription/:website_id/settings
// Validates against schema and saves
```

### API Client Pattern

Since Crisp PHP SDK might not have built-in settings methods:

```php
protected function makePluginApiRequest(string $method, string $path, array $data = [])
{
    $pluginId = config('crisp.plugin_id');
    $url = "https://api.crisp.chat/v1/plugin/{$pluginId}/{$path}";

    // Use existing authenticated client
    return $this->client->makeRequest($method, $url, $data);
}
```

## Schema Renderer Service

### Purpose

Convert JSON Schema into Livewire-compatible form field definitions.

### Field Type Mapping

- `type: "string"` → text input (or textarea if `format: "textarea"`)
- `type: "string", enum: [...]` → select dropdown
- `type: "number"` / `"integer"` → number input
- `type: "boolean"` → checkbox/toggle
- `type: "object"` → nested fieldset (recursive)
- `type: "array"` → repeatable field group with add/remove buttons

### Core Methods

```php
class SchemaRenderer
{
    public function renderSchema(array $schema): array
    // Returns array of field definitions

    protected function mapPropertyToField(string $key, array $property, array $schema): array
    // Converts single property to field config

    protected function extractValidationRules(array $property): array
    // Extracts Laravel validation rules from JSON Schema constraints

    protected function extractConditions(array $property, array $schema): ?array
    // Extracts conditional visibility logic
}
```

### Field Definition Structure

```php
[
    'key' => 'field_name',
    'type' => 'string',
    'label' => 'Field Label',
    'description' => 'Help text',
    'required' => true,
    'default' => null,
    'validation' => ['required', 'max:100'],
    'options' => ['opt1', 'opt2'], // For select fields
    'properties' => [...], // For nested objects
    'items' => [...], // For arrays
    'conditions' => [...], // For conditional fields
]
```

### Validation Extraction

Automatically converts JSON Schema constraints to Laravel validation rules:

- `minLength` / `maxLength` → `min:x` / `max:x`
- `pattern` → `regex:pattern`
- `format: "email"` → `email`
- `format: "url"` → `url`
- `minimum` / `maximum` → `min:x` / `max:x` (numbers)
- `enum` → `in:value1,value2`

## Livewire Component

### Component Structure

```php
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

    // Services injected
    protected LaravelCrisp $crisp;
    protected SchemaRenderer $renderer;
}
```

### Lifecycle Methods

**mount():**
1. Validate token (middleware already verified, but double-check)
2. Load schema from Crisp API
3. Render fields from schema via SchemaRenderer
4. Load current settings for website_id
5. Merge defaults for missing values
6. Set loading = false

**save():**
1. Validate against schema-derived rules
2. Try to save to Crisp API
3. Display success or error message
4. Log failures for debugging

### Dynamic Array Handling

```php
public function addArrayItem(string $fieldKey)
// Add new item to array field

public function removeArrayItem(string $fieldKey, int $index)
// Remove item from array field
```

### Nested Object Support

Settings stored as nested arrays matching schema structure. Livewire's `wire:model` handles nested paths automatically:
```blade
wire:model="settings.parent.child.field"
```

## Blade Views

### Main Template Structure

```
crisp-settings.blade.php
├── Loading spinner (if loading)
├── Success/Error messages
├── Dynamic form
│   ├── Loop through fields
│   ├── Include field type partial
│   └── Submit button
└── Wire loading states
```

### Field Type Partials

Each field type gets its own Blade partial in `resources/views/vendor/laravel-crisp/fields/`:

- `string.blade.php` - Text input
- `number.blade.php` - Number input
- `boolean.blade.php` - Checkbox/toggle
- `select.blade.php` - Dropdown
- `textarea.blade.php` - Textarea
- `array.blade.php` - Repeatable items with add/remove
- `object.blade.php` - Nested fieldset (recursive)
- `object-inline.blade.php` - Inline object rendering for arrays

### Recursive Rendering

Objects and arrays are rendered recursively. Each nested level uses the same field partials, building the path via wire:model.

### Tailwind Styling

- Form groups with proper spacing
- Inline validation error messages
- Loading states on buttons
- Disabled states during submission
- Responsive design for iframe

## Conditional Fields

### JSON Schema Pattern

Using custom `x-condition` property:

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
        "value": true
      }
    }
  }
}
```

### Implementation

**SchemaRenderer extracts conditions:**
```php
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
```

**Livewire Component evaluates visibility:**
```php
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
```

**Blade template conditionally renders:**
```blade
@foreach($fields as $field)
    @if($this->isFieldVisible($field))
        <div wire:key="field-{{ $field['key'] }}">
            @include('laravel-crisp::fields.' . $field['type'], ['field' => $field])
        </div>
    @endif
@endforeach
```

### Optional Enhancement

Add Alpine.js for instant client-side toggling without server roundtrips:
```blade
<div x-data="{ settings: @entangle('settings') }"
     x-show="settings.enable_notifications === true">
    <!-- Conditional field -->
</div>
```

## Routing & Middleware

### Route Registration

```php
// In LaravelCrispServiceProvider::boot()

Route::middleware(['web', 'crisp.validate-token'])
    ->prefix(config('crisp.settings.route_path', 'crisp/settings'))
    ->group(function () {
        Route::get('/', CrispSettings::class)->name('crisp.settings');
    });
```

### Middleware Implementation

```php
class ValidateCrispToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->query('token');
        $websiteId = $request->query('website_id');

        if (!$token || !$websiteId) {
            return response()->json(['error' => 'Missing authentication'], 401);
        }

        // Check cache first
        $cacheKey = "crisp.token.{$token}.{$websiteId}";
        $isValid = Cache::remember($cacheKey, 300, function () use ($token, $websiteId) {
            try {
                return app('laravel-crisp')->validateToken($token, $websiteId);
            } catch (\Exception $e) {
                return false;
            }
        });

        if (!$isValid) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Store validated website_id for component use
        $request->merge(['validated_website_id' => $websiteId]);

        return $next($request);
    }
}
```

### Security Headers

```php
$response->headers->set('X-Frame-Options', 'ALLOW-FROM https://app.crisp.chat');
$response->headers->set('Content-Security-Policy', "frame-ancestors https://app.crisp.chat");
```

### CSRF Exception

```php
// In App\Http\Middleware\VerifyCsrfToken

protected $except = [
    'crisp/settings',
    'crisp/settings/*',
];
```

## Error Handling & Validation

### Multi-Layer Validation

**1. Schema-Based Validation (Server-side):**

```php
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

        // Handle nested objects/arrays recursively
        if ($field['type'] === 'object' || $field['type'] === 'array') {
            $rules = array_merge($rules, $this->buildNestedRules($field));
        }
    }

    return $rules;
}
```

**2. Real-time Validation:**

```php
public function updated($propertyName)
{
    $this->validateOnly($propertyName);
}
```

**3. API Error Handling:**

```php
public function save()
{
    try {
        $this->validate($this->buildValidationRules());
        $this->crisp->saveWebsiteSettings($this->websiteId, $this->settings);
        $this->successMessage = 'Settings saved successfully!';
        $this->errorMessage = null;

    } catch (ValidationException $e) {
        throw $e; // Livewire displays inline errors

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
```

### Error Display

- **Inline errors**: Below each field via `@error` directive
- **Global errors**: Banner at top of page for API/system errors
- **Loading states**: Disabled buttons and spinners during save

### Graceful Degradation

```php
// If schema can't be loaded
if (empty($this->schema)) {
    $this->errorMessage = 'Unable to load settings schema. Please try again later.';
    $this->loading = false;
    return;
}
```

## Testing Strategy

### Unit Tests

- **SchemaRendererTest**: Field type mapping, validation extraction, nested objects, conditional logic
- **LaravelCrispTest**: API method additions, request formatting

### Feature Tests

- **SettingsPageTest**: Token validation, settings loading, form rendering, save functionality
- **TokenValidationTest**: Middleware behavior, cache logic, security headers

### Integration Tests

- Mock Crisp API responses using `Http::fake()`
- Test complete flows with Livewire::test()
- Verify validation rules work correctly
- Test error handling paths

### Test Fixtures

```php
class CrispApiMock
{
    public static function successfulSchema(): array
    public static function complexNestedSchema(): array
    public static function settingsResponse(array $data): array
}
```

### Browser Tests (Optional)

Dusk tests for complete user flow in iframe context.

## File Structure

```
laravel-crisp/
├── config/
│   └── crisp.php (add 'settings' section)
│
├── src/
│   ├── LaravelCrisp.php (extend with settings methods)
│   ├── LaravelCrispServiceProvider.php (register routes)
│   │
│   ├── Http/
│   │   ├── Livewire/
│   │   │   └── CrispSettings.php
│   │   │
│   │   └── Middleware/
│   │       └── ValidateCrispToken.php
│   │
│   ├── Services/
│   │   └── SchemaRenderer.php
│   │
│   └── Exceptions/
│       └── CrispApiException.php
│
├── resources/
│   └── views/
│       ├── livewire/
│       │   └── crisp-settings.blade.php
│       │
│       └── vendor/laravel-crisp/fields/
│           ├── string.blade.php
│           ├── number.blade.php
│           ├── boolean.blade.php
│           ├── select.blade.php
│           ├── textarea.blade.php
│           ├── array.blade.php
│           ├── object.blade.php
│           └── object-inline.blade.php
│
└── tests/
    ├── Unit/
    │   ├── SchemaRendererTest.php
    │   └── LaravelCrispTest.php
    │
    ├── Feature/
    │   ├── SettingsPageTest.php
    │   └── TokenValidationTest.php
    │
    └── Fixtures/
        └── CrispApiMock.php
```

## Implementation Notes

### Dependencies

Add to `composer.json`:
```json
{
    "require": {
        "livewire/livewire": "^3.0"
    }
}
```

### Livewire Assets

Publish Livewire assets or include via CDN in layout.

### Tailwind Configuration

Ensure Tailwind is configured to scan Livewire components and field partials.

### Multi-tenancy

Each website_id has isolated settings. No cross-website data leakage.

### Performance Considerations

- Cache schema for 5-15 minutes (changes rarely)
- Cache token validation for 5 minutes
- Optimize recursive rendering for deeply nested schemas
- Consider lazy loading for large schemas

### Future Enhancements

- Real-time updates via webhooks/polling
- Schema version management
- Settings history/audit log
- Export/import settings
- Settings templates
- Multi-language support for field labels

## Success Criteria

✅ Embeds seamlessly in Crisp Dashboard iframe
✅ Authenticates securely via token validation
✅ Fetches and renders any valid JSON Schema
✅ Supports all advanced field types (arrays, objects, conditionals)
✅ Validates comprehensively (client + server + API)
✅ Saves settings successfully to Crisp API
✅ Displays clear error messages
✅ Includes comprehensive test coverage
✅ Works across all supported Laravel versions (9+)

## References

- [Crisp Plugin Settings Documentation](https://docs.crisp.chat/guides/plugins/settings/quickstart/)
- [JSON Schema Specification](https://json-schema.org/)
- [Livewire Documentation](https://livewire.laravel.com/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
