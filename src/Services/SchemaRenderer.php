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
