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

@if ($notifications->count() > 0)
    <div class="notification-list">
        @foreach ($notifications as $notification)
            <div class="notification-item {{ $notification->isUnread() ? 'is-unread' : '' }}">
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
                    <small class="notification-time">{{ $notification->created_at->diffForHumans() }}</small>

                    @if ($notification->getActionUrl())
                        <a href="{{ $notification->getActionUrl() }}" class="notification-action">View Details &rarr;</a>
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