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
