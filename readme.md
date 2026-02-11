# Laravel Crisp

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Tests][ico-tests]][link-tests]

A Laravel package that provides a seamless integration with [Crisp Chat](https://crisp.chat/). This package wraps the official Crisp PHP API client and adds Laravel-specific features like webhook handling with signature verification.

## Features

- 🚀 Easy integration with Crisp Chat API
- 🔐 Webhook signature verification for security
- 📢 Event-driven webhook handling
- ⚙️ Configurable webhook endpoints
- 🎯 Laravel 9+ support
- 🧪 Comprehensive test coverage

## Requirements

- PHP 8.1 or higher
- Laravel 9.0 or higher

## Installation

Install the package via Composer:

```bash
composer require vntrungld/laravel-crisp
```

The package will automatically register its service provider.

### Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="laravel-crisp.config"
```

This will create a `config/crisp.php` file in your application.

## Configuration

Add the following environment variables to your `.env` file:

```env
CRISP_TIER=plugin
CRISP_TOKEN_ID=your-token-id
CRISP_TOKEN_KEY=your-token-key
CRISP_SIGNING_SECRET=your-signing-secret
CRISP_WEBHOOK_PATH=crisp
CRISP_PLUGIN_ID=your-plugin-id
```

### Configuration Options

- `tier`: The Crisp API tier (default: `plugin`)
- `token_id`: Your Crisp API token identifier
- `token_key`: Your Crisp API token key
- `signing_secret`: Secret for webhook signature verification
- `webhook_path`: Base path for webhook routes (default: `crisp`)
- `plugin_id`: Your Crisp plugin identifier

## Usage

### Using the Crisp Client

You can access the Crisp client in several ways:

#### Via Facade

```php
use LaravelCrisp;

// Get the Crisp client instance
$client = LaravelCrisp::client();

// Example: Send a message
$client->websiteConversations->sendMessageInConversation(
    'website_id',
    'session_id',
    [
        'type' => 'text',
        'content' => 'Hello from Laravel!'
    ]
);
```

#### Via Dependency Injection

```php
use Vntrungld\LaravelCrisp\LaravelCrisp;

class YourController extends Controller
{
    public function __construct(protected LaravelCrisp $crisp)
    {
    }

    public function sendMessage()
    {
        $client = $this->crisp->client();
        // Use the client...
    }
}
```

#### Via Container

```php
$crisp = app('laravel-crisp');
$client = $crisp->client();
```

### Handling Webhooks

The package automatically registers a webhook endpoint at `/crisp/webhook` (or your custom path).

#### Webhook Signature Verification

Webhook signature verification is automatically enabled when you set `CRISP_SIGNING_SECRET`. This ensures that incoming webhooks are genuinely from Crisp.

The middleware will:
1. Extract the timestamp and signature from request headers
2. Compute the expected signature using HMAC-SHA256
3. Compare signatures using a timing-safe comparison
4. Reject requests with invalid signatures (401 response)

#### Listening to Webhook Events

When a webhook is received, a `WebhookReceived` event is dispatched. You can listen to this event in your application:

```php
use Illuminate\Support\Facades\Event;
use Vntrungld\LaravelCrisp\Events\WebhookReceived;

Event::listen(WebhookReceived::class, function (WebhookReceived $event) {
    $payload = $event->payload;

    // Handle the webhook payload
    logger('Crisp webhook received', $payload);

    // Example: Handle message sent event
    if ($payload['event'] === 'message:send') {
        // Process the message...
    }
});
```

Or create a dedicated listener:

```php
php artisan make:listener HandleCrispWebhook
```

```php
namespace App\Listeners;

use Vntrungld\LaravelCrisp\Events\WebhookReceived;

class HandleCrispWebhook
{
    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;

        match($payload['event']) {
            'message:send' => $this->handleMessageSent($payload),
            'message:received' => $this->handleMessageReceived($payload),
            // ... other event types
            default => null,
        };
    }

    protected function handleMessageSent(array $payload): void
    {
        // Your logic here
    }

    protected function handleMessageReceived(array $payload): void
    {
        // Your logic here
    }
}
```

Register the listener in your `EventServiceProvider`:

```php
use Vntrungld\LaravelCrisp\Events\WebhookReceived;
use App\Listeners\HandleCrispWebhook;

protected $listen = [
    WebhookReceived::class => [
        HandleCrispWebhook::class,
    ],
];
```

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

### Crisp API Examples

For detailed API documentation, refer to the [official Crisp API documentation](https://docs.crisp.chat/api/v1/).

#### Get Website Conversations

```php
$conversations = LaravelCrisp::client()
    ->websiteConversations
    ->getConversationsList('website_id');
```

#### Get Conversation Messages

```php
$messages = LaravelCrisp::client()
    ->websiteConversations
    ->getMessagesInConversation('website_id', 'session_id');
```

#### Send a Text Message

```php
LaravelCrisp::client()
    ->websiteConversations
    ->sendMessageInConversation(
        'website_id',
        'session_id',
        [
            'type' => 'text',
            'content' => 'Your message here',
            'from' => 'operator',
            'origin' => 'chat'
        ]
    );
```

#### Update Conversation Meta

```php
LaravelCrisp::client()
    ->websiteConversations
    ->updateConversationMetas(
        'website_id',
        'session_id',
        [
            'nickname' => 'John Doe',
            'email' => 'john@example.com'
        ]
    );
```

## Testing

The package includes comprehensive tests covering all major functionality:

```bash
composer test
```

### Running Tests for Specific Laravel Versions

The package supports Laravel 9, 10, 11, and 12. The test suite runs against all versions in CI.

To test locally with a specific Laravel version:

```bash
# Laravel 9
composer require "laravel/framework:^9.0" "orchestra/testbench:^7.0" --dev
composer test

# Laravel 10
composer require "laravel/framework:^10.0" "orchestra/testbench:^8.0" --dev
composer test

# Laravel 11
composer require "laravel/framework:^11.0" "orchestra/testbench:^9.0" --dev
composer test

# Laravel 12
composer require "laravel/framework:^12.0" "orchestra/testbench:^10.0" --dev
composer test
```

## Laravel Version Support

| Laravel Version | PHP Version | Package Support |
|----------------|-------------|-----------------|
| 9.x            | 8.1, 8.2    | ✅              |
| 10.x           | 8.1, 8.2, 8.3 | ✅              |
| 11.x           | 8.2, 8.3    | ✅              |
| 12.x           | 8.2, 8.3    | ✅              |

The **minimum supported Laravel version is 9.0**.

## Security

### Webhook Signature Verification

Always set `CRISP_SIGNING_SECRET` in production to ensure webhook authenticity. Without this, anyone could send fake webhooks to your application.

### Reporting Security Issues

If you discover any security-related issues, please email security@example.com instead of using the issue tracker.

## Change Log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Credits

- [vntrungld][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/vntrungld/laravel-crisp.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/vntrungld/laravel-crisp.svg?style=flat-square
[ico-tests]: https://img.shields.io/github/actions/workflow/status/vntrungld/laravel-crisp/tests.yml?branch=master&label=tests&style=flat-square

[link-packagist]: https://packagist.org/packages/vntrungld/laravel-crisp
[link-downloads]: https://packagist.org/packages/vntrungld/laravel-crisp
[link-tests]: https://github.com/vntrungld/laravel-crisp/actions
[link-author]: https://github.com/vntrungld
[link-contributors]: ../../contributors
