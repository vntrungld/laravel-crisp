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
