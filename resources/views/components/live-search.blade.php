@props([
    'endpoint',
    'name' => 'search',
    'id' => null,
    'value' => null,
    'label' => null,
    'placeholder' => 'Search...',
    'maxlength' => 200,
    'minChars' => 1,
    'debounce' => 250,
    'autocomplete' => 'off',
])

@php
    $inputId = $id ?? 'live-search-'.Illuminate\Support\Str::random(8);
@endphp

<div
    {{ $attributes->merge(['class' => 'live-search']) }}
    data-live-search
    data-live-search-endpoint="{{ $endpoint }}"
    data-live-search-min="{{ $minChars }}"
    data-live-search-debounce="{{ $debounce }}"
>
    @if ($label)
        <label for="{{ $inputId }}">{{ $label }}</label>
    @endif

    <div class="live-search-control">
        <input
            type="search"
            id="{{ $inputId }}"
            name="{{ $name }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            maxlength="{{ $maxlength }}"
            autocomplete="{{ $autocomplete }}"
            role="combobox"
            aria-autocomplete="list"
            aria-expanded="false"
            {{ $attributes->only('required') }}
        >
        <span class="live-search-spinner" aria-hidden="true"></span>
    </div>

    <div class="live-search-results" role="listbox" hidden></div>
    <div class="live-search-sr" aria-live="polite"></div>
</div>
