<div class="max-w-4xl mx-auto p-6 bg-white">
    @if($loading)
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
    @else
        <form wire:submit.prevent="save" class="space-y-6">
            <!-- Messages -->
            @if($successMessage)
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    {{ $successMessage }}
                </div>
            @endif

            @if($errorMessage)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {{ $errorMessage }}
                </div>
            @endif

            <!-- Dynamic Fields -->
            @foreach($fields as $field)
                @if($this->isFieldVisible($field))
                    <div wire:key="field-{{ $field['key'] }}">
                        @include('laravel-crisp::fields.' . $field['type'], ['field' => $field])
                    </div>
                @endif
            @endforeach

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t">
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                    <span wire:loading.remove>Save Settings</span>
                    <span wire:loading>Saving...</span>
                </button>
            </div>
        </form>
    @endif
</div>
