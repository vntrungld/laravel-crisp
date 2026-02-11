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
