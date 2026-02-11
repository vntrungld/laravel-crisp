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
