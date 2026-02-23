<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crisp Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50">

<div class="mx-auto max-w-2xl px-4 py-12">

    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">Crisp Settings</h1>
        <p class="mt-1 text-sm text-gray-500">Configure your Crisp plugin settings for this website.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @isset($error)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $error }}
        </div>
    @else
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <form method="POST" action="{{ route('crisp.settings.update', ['website_id' => $websiteId]) }}">
                @csrf

                <div class="divide-y divide-gray-100">
                    @foreach ($settings as $key => $value)
                        <div class="flex items-center justify-between gap-6 px-6 py-4">
                            <label for="{{ $key }}" class="text-sm font-medium text-gray-700">
                                {{ ucwords(str_replace('_', ' ', $key)) }}
                            </label>

                            @if (is_bool($value))
                                <input
                                    type="checkbox"
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    value="1"
                                    {{ old($key, $value) ? 'checked' : '' }}
                                    class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                >
                            @elseif (is_numeric($value))
                                <input
                                    type="number"
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    value="{{ old($key, $value) }}"
                                    class="w-40 rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-900 shadow-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                >
                            @else
                                <input
                                    type="text"
                                    id="{{ $key }}"
                                    name="{{ $key }}"
                                    value="{{ old($key, $value) }}"
                                    class="w-64 rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-900 shadow-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                                >
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end border-t border-gray-100 px-6 py-4">
                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-xs hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                    >
                        Save settings
                    </button>
                </div>
            </form>
        </div>
    @endisset

</div>

</body>
</html>
