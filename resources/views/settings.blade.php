<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Crisp Settings</title>
</head>
<body>

@if (session('success'))
    <div>{{ session('success') }}</div>
@endif

@if (session('error'))
    <div>{{ session('error') }}</div>
@endif

@isset($error)
    <div>{{ $error }}</div>
@else
    <form method="POST" action="{{ route('crisp.settings.update', ['website_id' => $websiteId]) }}">
        @csrf

        @foreach ($settings as $key => $value)
            <div>
                <label for="{{ $key }}">{{ ucwords(str_replace('_', ' ', $key)) }}</label>

                @if (is_bool($value))
                    <input
                        type="checkbox"
                        id="{{ $key }}"
                        name="{{ $key }}"
                        value="1"
                        {{ old($key, $value) ? 'checked' : '' }}
                    >
                @elseif (is_numeric($value))
                    <input
                        type="number"
                        id="{{ $key }}"
                        name="{{ $key }}"
                        value="{{ old($key, $value) }}"
                    >
                @else
                    <input
                        type="text"
                        id="{{ $key }}"
                        name="{{ $key }}"
                        value="{{ old($key, $value) }}"
                    >
                @endif
            </div>
        @endforeach

        <button type="submit">Save</button>
    </form>
@endisset

</body>
</html>
