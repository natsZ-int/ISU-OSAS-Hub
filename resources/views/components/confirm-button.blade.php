@props([
    'action',
    'method' => 'POST',
    'confirm' => 'Are you sure?',
    'label' => 'Submit',
    'variant' => 'primary',
    'size' => null,
])

@php
    $btnClass = 'btn btn-' . $variant . ($size ? ' btn-' . $size : '');
@endphp

<form method="POST" action="{{ $action }}" class="inline-form" onsubmit="return confirm({{ json_encode($confirm) }})">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    <button type="submit" {{ $attributes->merge(['class' => $btnClass]) }}>{{ $label }}</button>
</form>