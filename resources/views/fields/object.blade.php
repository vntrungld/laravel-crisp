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
