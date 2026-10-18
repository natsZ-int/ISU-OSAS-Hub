@props(['title' => null])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title)
        <h3>{{ $title }}</h3>
    @endif
    {{ $slot }}
</div>
