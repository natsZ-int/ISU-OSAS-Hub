<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ISU OSAS Hub')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <header class="site-header">
        <div class="brand">
            <h1>ISU OSAS Hub</h1>
            <p>Office of Student Affairs and Services Hub</p>
        </div>
        @auth
        <nav class="site-nav" aria-label="Main navigation">
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="nav-link">Dashboard</a>
                <a href="{{ route('admin.users.index') }}" class="nav-link">Users</a>
                <a href="{{ route('admin.scholarships.index') }}" class="nav-link">Scholarships</a>
                <a href="{{ route('admin.scholarship-applications.index') }}" class="nav-link">Applications</a>
                <a href="{{ route('admin.scholarship-matching.weights.edit') }}" class="nav-link">Weights</a>
                <a href="{{ route('admin.guidance.appointments') }}" class="nav-link">Guidance</a>
                <a href="{{ route('admin.student-service-requests.index') }}" class="nav-link">Service Requests</a>
                <a href="{{ route('admin.service-types.index') }}" class="nav-link">Service Types</a>
                <a href="{{ route('admin.audit-logs.index') }}" class="nav-link">Audit Logs</a>
                <a href="{{ route('admin.analytics.index') }}" class="nav-link">Analytics</a>
            @elseif (auth()->user()->isCounselor())
                <a href="{{ route('counselor.dashboard') }}" class="nav-link">Dashboard</a>
                <a href="{{ route('counselor.guidance.appointments') }}" class="nav-link">My Appointments</a>
                <a href="{{ route('counselor.guidance.availability') }}" class="nav-link">Availability</a>
            @elseif (auth()->user()->isStudent())
                <a href="{{ route('student.dashboard') }}" class="nav-link">Dashboard</a>
                <a href="{{ route('student.scholarships.index') }}" class="nav-link">Scholarships</a>
                <a href="{{ route('student.scholarships.recommendations') }}" class="nav-link">Recommended</a>
                <a href="{{ route('student.applications.index') }}" class="nav-link">My Applications</a>
                <a href="{{ route('student.guidance.appointments') }}" class="nav-link">Guidance Appointments</a>
                <a href="{{ route('student.services.catalog') }}" class="nav-link">OSAS Services</a>
            @endif

            <a href="{{ route('notifications.index') }}" class="nav-link notification-bell" aria-label="Notifications">
                <span aria-hidden="true">&#128276;</span> Notifications
                @if (isset($unreadNotificationCount) && $unreadNotificationCount > 0)
                    <span class="notification-count">
                        {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                    </span>
                @endif
            </a>

            <span class="user-chip">
                {{ Auth::user()->name }}
                <small>({{ Auth::user()->role?->label ?? Auth::user()->role?->name }})</small>
            </span>
            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="btn btn-outline">Logout</button>
            </form>
        </nav>
        @endauth
    </header>

    <main class="site-main" id="main-content">
        @if (session('status'))
            <x-alert type="success">{{ session('status') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert type="error">{{ session('error') }}</x-alert>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <small>&copy; {{ date('Y') }} Isabela State University - OSAS Hub. All rights reserved.</small>
    </footer>
</body>
</html>