@props([
    'label' => 'Data table',
    'hint' => 'Scroll the table horizontally to view more columns.',
    'showHint' => true,
])

<div class="table-card">
    @if ($showHint)
        <p class="table-scroll-hint">{{ $hint }}</p>
    @endif
    <div class="table-responsive" role="region" aria-label="{{ $label }}" tabindex="0">
        {{ $slot }}
    </div>
</div>
