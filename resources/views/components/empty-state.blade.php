@props([
    'title' => 'No records found.',
    'message' => null,
    'actionUrl' => null,
    'actionLabel' => null,
])

<div class="empty-state-block">
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