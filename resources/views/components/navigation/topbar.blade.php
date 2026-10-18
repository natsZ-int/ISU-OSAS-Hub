@props(['unreadCount' => 0])

@php
    $user         = auth()->user();
    $isStudent    = $user?->isStudent() ?? false;
    $isCounselor  = $user?->isCounselor() ?? false;
    $isAdmin      = $user?->isAdmin() ?? false;

    $homeRoute = $isAdmin
        ? 'admin.dashboard'
        : ($isCounselor ? 'counselor.dashboard' : 'student.dashboard');

    $roleLabel = $user?->role?->label ?? $user?->role?->name ?? '';
@endphp

<header class="topbar">
    <div class="topbar-left">
        <button
            type="button"
            class="nav-toggle"
            id="nav-toggle"
            aria-controls="app-sidebar"
            aria-expanded="false"
            aria-label="Open navigation menu"
        >
            <span class="nav-toggle-bars" aria-hidden="true"></span>
        </button>

        <a href="{{ route($homeRoute) }}" class="topbar-mobile-brand">
            <span class="brand-mark" style="width:30px;height:30px;font-size:.65rem;" aria-hidden="true">ISU</span>
            ISU OSAS Hub
        </a>
    </div>

    <div class="topbar-right">
        <a
            href="{{ route('notifications.index') }}"
            class="topbar-icon-link notification-bell"
            aria-label="Notifications{{ $unreadCount > 0 ? ' (' . $unreadCount . ' unread)' : '' }}"
        >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.7 21a2 2 0 0 1-3.4 0"></path>
            </svg>
            @if ($unreadCount > 0)
                <span class="notification-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
            @endif
        </a>

        <span class="user-chip">
            {{ $user?->name }}
            @if ($roleLabel !== '')
                <small>({{ $roleLabel }})</small>
            @endif
        </span>

        <form method="POST" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">Logout</button>
        </form>
    </div>
</header>
