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
