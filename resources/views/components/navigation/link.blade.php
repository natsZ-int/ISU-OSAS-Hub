@props([
    'href',
    'active' => false,
])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => 'nav-item' . ($active ? ' is-active' : '')]) }}
    @if ($active) aria-current="page" @endif
>{{ $slot }}</a>
