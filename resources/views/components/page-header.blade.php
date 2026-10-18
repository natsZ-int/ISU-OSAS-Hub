@props(['title', 'subtitle' => null])

<div class="page-header">
    <div class="page-header-title">
        @isset($breadcrumb)
            {{ $breadcrumb }}
        @endisset
        <h1>{{ $title }}</h1>
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
