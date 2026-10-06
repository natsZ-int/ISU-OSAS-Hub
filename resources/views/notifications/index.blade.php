@extends('layouts.app')
@section('title', 'Notifications - ISU OSAS Hub')

@section('content')
<div class="container py-4" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;">My Notifications</h2>
            <p style="color: #6c757d; margin: 0;">
                {{ $unreadCount }} unread notification(s)
            </p>
        </div>

        @if($unreadCount > 0)
            <form action="{{ route('notifications.read-all') }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                    Mark All as Read
                </button>
            </form>
        @endif
    </div>

    @if (session('status'))
        <div style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; background: #d4edda; color: #155724; border-radius: 6px;">{{ session('status') }}</div>
    @endif

    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        @forelse($notifications as $notification)
            <div style="background: {{ $notification->isUnread() ? '#eff6ff' : '#fff' }}; border: 1px solid {{ $notification->isUnread() ? '#93c5fd' : '#e2e8f0' }}; border-radius: 8px; padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        @if($notification->isUnread())
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; display: inline-block; flex-shrink: 0;" aria-label="Unread"></span>
                        @endif
                        <strong style="font-size: 0.95rem; color: #1e293b;">{{ $notification->title }}</strong>
                        <span style="font-size: 0.7rem; padding: 0.15rem 0.45rem; border-radius: 9999px; background: #f1f5f9; color: #475569; font-weight: 600;">
                            {{ $notification->getCategoryLabel() }}
                        </span>
                    </div>
                    <p style="margin: 0 0 0.35rem 0; font-size: 0.85rem; color: #4b5563; line-height: 1.4;">{{ $notification->message }}</p>
                    <small style="color: #94a3b8; font-size: 0.75rem;">{{ $notification->created_at->diffForHumans() }}</small>

                    @if($notification->getActionUrl())
                        <div style="margin-top: 0.5rem;">
                            <a href="{{ $notification->getActionUrl() }}" style="font-size: 0.8rem; color: #2563eb; text-decoration: none;">View Details &rarr;</a>
                        </div>
                    @endif
                </div>

                @if($notification->isUnread())
                    <form action="{{ route('notifications.read', $notification) }}" method="POST" style="flex-shrink: 0;">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" title="Mark as read">
                            Mark Read
                        </button>
                    </form>
                @else
                    <span style="font-size: 0.75rem; color: #94a3b8; flex-shrink: 0;">Read</span>
                @endif
            </div>
        @empty
            <div style="background: #fff; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 3rem; text-align: center; color: #64748b;">
                You have no notifications at this time.
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div style="margin-top: 1.5rem;">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection