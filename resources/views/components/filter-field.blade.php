@props([
    'label',
    'forId' => null,
])

<div class="filter-field">
    <label @if ($forId) for="{{ $forId }}" @endif>{{ $label }}</label>
    {{ $slot }}
</div>
