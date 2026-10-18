@props([
    'action',
    'method' => 'GET',
])

<form method="{{ $method }}" action="{{ $action }}" class="filter-bar" {{ $attributes }}>
    @if (strtoupper($method) !== 'GET')
        @csrf
    @endif

    <div class="filter-bar-fields">
        {{ $slot }}
    </div>

    @isset($actions)
        <div class="filter-bar-footer">
            {{ $actions }}
        </div>
    @endisset
</form>
