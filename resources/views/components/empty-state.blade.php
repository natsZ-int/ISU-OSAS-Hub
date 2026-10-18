@props([
    'title' => 'No records found.',
    'message' => null,
    'actionUrl' => null,
    'actionLabel' => null,
])

<div class="empty-state-block">
    <div class="empty-state-icon" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
            <rect x="3" y="4" width="18" height="16" rx="2"></rect>
            <path d="M3 10h18"></path>
            <path d="M9 15h6"></path>
        </svg>
    </div>

    <p class="empty-state-title">{{ $title }}</p>

    @if ($message)
        <p class="empty-state-message">{{ $message }}</p>
    @endif

    {{ $slot }}

    @if ($actionUrl && $actionLabel)
        <div class="empty-state-action">
            <a href="{{ $actionUrl }}" class="btn btn-primary btn-sm">{{ $actionLabel }}</a>
        </div>
    @endif
</div>
