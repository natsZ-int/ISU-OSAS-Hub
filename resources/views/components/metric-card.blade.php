@props([
    'label',
    'value',
    'hint' => null,
    'href' => null,
    'tone' => 'brand',
])

@php
    $toneMap = [
        'brand'   => '',
        'success' => ' tone-success',
        'warning' => ' tone-warning',
        'danger'  => ' tone-danger',
        'info'    => ' tone-info',
        'neutral' => ' tone-neutral',
    ];
    $toneClass = $toneMap[$tone] ?? '';
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} class="metric-tile{{ $toneClass }}"@if ($href) href="{{ $href }}"@endif>
    <span class="metric-tile-value">{{ $value }}</span>
    <span class="metric-tile-label">{{ $label }}</span>
    @if ($hint)
        <span class="metric-tile-hint">{{ $hint }}</span>
    @endif
</{{ $tag }}>
