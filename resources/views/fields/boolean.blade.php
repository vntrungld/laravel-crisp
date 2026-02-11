<div class="mb-4">
    <label class="flex items-center cursor-pointer">
        <input
            type="checkbox"
            wire:model="settings.{{ $field['key'] }}"
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
        >
        <span class="ml-2 text-sm font-medium text-gray-700">
            {{ $field['label'] }}
        </span>
    </label>

    @if($field['description'])
        <p class="ml-6 text-sm text-gray-500 mt-1">{{ $field['description'] }}</p>
    @endif

    @error('settings.' . $field['key'])
        <p class="ml-6 mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
