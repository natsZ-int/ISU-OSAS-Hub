@props(['title', 'subtitle' => null])

<div class="page-header">
    <div>
        <h2>{{ $title }}</h2>
        @if ($subtitle)
            <p class="muted">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="page-header-actions">
            {{ $actions }}
        </div>
    @endisset
</div>