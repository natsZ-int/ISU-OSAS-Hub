@extends('layouts.app')
@section('title', 'Notifications - ISU OSAS Hub')

@section('content')
<x-page-header
    title="My Notifications"
    subtitle="{{ $unreadCount }} unread notification(s)">
    @if ($unreadCount > 0)
        <x-slot:actions>
            <form action="{{ route('notifications.read-all') }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline-dark">Mark All as Read</button>
            </form>
        </x-slot:actions>
    @endif
</x-page-header>

@if (session('status'))
    <x-alert type="success">{{ session('status') }}</x-alert>
@endif

<x-card class="filter-card">
    <form method="GET" action="{{ route('notifications.index') }}" class="filter-form" role="search">
        <div class="filter-row">
            <div class="form-group" style="flex:2;">
                <label for="notification_search">Search Notifications</label>
                <input type="search" id="notification_search" name="search"
                       value="{{ request('search') }}"
                       placeholder="Title, message, category, or reference..."
                       maxlength="200"
                       data-live-search
                       data-live-search-endpoint="{{ route('notifications.quick-search') }}">
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                @if (request('search'))
                    <a href="{{ route('notifications.index') }}" class="btn btn-outline-dark btn-sm">Clear</a>
                @endif
            </div>
        </div>
    </form>
</x-card>

@if ($notifications->count() > 0)
    <div class="notification-list">
        @foreach ($notifications as $notification)
            <div id="notification-{{ $notification->id }}" class="notification-item {{ $notification->isUnread() ? 'is-unread' : '' }}">
                <div class="notification-item-main">
                    <div class="notification-item-head">
                        @if ($notification->isUnread())
                            <span class="notification-dot" aria-hidden="true"></span>
                            <span class="sr-only">Unread</span>
                        @endif
                        <span class="notification-title">{{ $notification->title }}</span>
                        <span class="notification-category">{{ $notification->getCategoryLabel() }}</span>
                    </div>
                    <p class="notification-message">{{ $notification->message }}</p>

                    @if ($notification->reference() || $notification->statusLabel())
                        <p class="notification-time" style="margin-top:0.15rem;">
                            @if ($notification->reference())
                                <span><strong>{{ $notification->referenceLabel() ?: 'Reference' }}:</strong> {{ $notification->reference() }}</span>
                            @endif
                            @if ($notification->statusLabel())
                                <span> &middot; <strong>Status:</strong> {{ $notification->statusLabel() }}</span>
                            @endif
                        </p>
                    @endif

                    <small class="notification-time">
                        Updated {{ $notification->created_at->format('M j, Y g:i A') }}
                        ({{ $notification->created_at->diffForHumans() }})
                    </small>

                    @if ($notification->staffExplanation())
                        <p class="notification-message" style="color:#6b7280;">
                            <strong>Explanation:</strong> {{ $notification->staffExplanation() }}
                        </p>
                    @endif

                    @if ($notification->getDetailUrl())
                        <a href="{{ $notification->getDetailUrl() }}" class="notification-action">View Details &rarr;</a>
                    @endif
                </div>

                <div class="notification-item-side">
                    @if ($notification->isUnread())
                        <form action="{{ route('notifications.read', $notification) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-dark" title="Mark as read">Mark Read</button>
                        </form>
                    @else
                        <span class="notification-status-label">Read</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <x-pagination-wrapper :paginator="$notifications" />
@else
    <x-card>
        <x-empty-state
            title="No notifications."
            message="You're all caught up. New notifications will appear here." />
    </x-card>
@endif
@endsection