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

    @auth
        <div class="app-shell">
            <x-navigation.sidebar :unread-count="isset($unreadNotificationCount) ? (int) $unreadNotificationCount : 0" />

            <div class="sidebar-overlay" id="sidebar-overlay" hidden></div>

            <div class="app-body">
                <x-navigation.topbar :unread-count="isset($unreadNotificationCount) ? (int) $unreadNotificationCount : 0" />

                <main class="site-main" id="main-content" tabindex="-1">
                    @if (session('status'))
                        <x-alert type="success">{{ session('status') }}</x-alert>
                    @endif
                    @if (session('error'))
                        <x-alert type="error">{{ session('error') }}</x-alert>
                    @endif

                    @yield('content')
                </main>

                <footer class="site-footer">
                    <small>&copy; {{ date('Y') }} Isabela State University &mdash; OSAS Hub. All rights reserved.</small>
                </footer>
            </div>
        </div>
    @else
        <div class="guest-shell">
            <header class="guest-header">
                <span class="brand-mark" aria-hidden="true">ISU</span>
                <span class="guest-brand-text">
                    <strong>ISU OSAS Hub</strong>
                    <span>Office of Student Affairs and Services</span>
                </span>
            </header>

            <main class="guest-main" id="main-content" tabindex="-1">
                @if (session('status'))
                    <div style="max-width:440px;margin:0 auto 1rem;">
                        <x-alert type="success">{{ session('status') }}</x-alert>
                    </div>
                @endif
                @if (session('error'))
                    <div style="max-width:440px;margin:0 auto 1rem;">
                        <x-alert type="error">{{ session('error') }}</x-alert>
                    </div>
                @endif

                @yield('content')
            </main>

            <footer class="site-footer">
                <small>&copy; {{ date('Y') }} Isabela State University &mdash; OSAS Hub. All rights reserved.</small>
            </footer>
        </div>
    @endauth

    <script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
