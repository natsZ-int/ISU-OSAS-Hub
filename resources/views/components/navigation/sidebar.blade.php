@props(['unreadCount' => 0])

@php
    $user = auth()->user();
    $isStudent    = $user?->isStudent() ?? false;
    $isCounselor  = $user?->isCounselor() ?? false;
    $isAdmin      = $user?->isAdmin() ?? false;

    $homeRoute = $isAdmin
        ? 'admin.dashboard'
        : ($isCounselor ? 'counselor.dashboard' : 'student.dashboard');
@endphp

<aside class="sidebar" id="app-sidebar" aria-label="Main navigation">
    <a class="sidebar-brand" href="{{ route($homeRoute) }}">
        <span class="brand-mark" aria-hidden="true">ISU</span>
        <span class="brand-text">
            <strong>ISU OSAS Hub</strong>
            <span>Student Affairs &amp; Services</span>
        </span>
    </a>

    <nav class="sidebar-nav">
        @if ($isAdmin)
            <p class="nav-section-label" id="nav-main">Main</p>
            <x-navigation.link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">Dashboard</x-navigation.link>

            <p class="nav-section-label">Management</p>
            <x-navigation.link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">User Management</x-navigation.link>
            <x-navigation.link :href="route('admin.student-profiles.index')" :active="request()->routeIs('admin.student-profiles.*')">Student Profiles</x-navigation.link>
            <x-navigation.link :href="route('enrollment-verifications.index')" :active="request()->routeIs('enrollment-verifications.*')">Enrollment Verification</x-navigation.link>
            <x-navigation.link :href="route('admin.scholarships.index')" :active="request()->routeIs('admin.scholarships.*')">Scholarships</x-navigation.link>
            <x-navigation.link :href="route('admin.scholarship-applications.index')" :active="request()->routeIs('admin.scholarship-applications.*')">Scholarship Applications</x-navigation.link>
            <x-navigation.link :href="route('admin.scholarship-matching.weights.edit')" :active="request()->routeIs('admin.scholarship-matching.*')">Matching Weights</x-navigation.link>

            <p class="nav-section-label">Guidance &amp; Services</p>
            <x-navigation.link :href="route('admin.guidance.appointments')" :active="request()->routeIs('admin.guidance.*')">Guidance Management</x-navigation.link>
            <x-navigation.link :href="route('admin.student-service-requests.index')" :active="request()->routeIs('admin.student-service-requests.*')">Service Requests</x-navigation.link>
            <x-navigation.link :href="route('admin.service-types.index')" :active="request()->routeIs('admin.service-types.*')">Service Types</x-navigation.link>

            <p class="nav-section-label">Insights</p>
            <x-navigation.link :href="route('admin.analytics.index')" :active="request()->routeIs('admin.analytics.*')">Analytics &amp; Reports</x-navigation.link>

            <p class="nav-section-label">System</p>
            <x-navigation.link :href="route('admin.audit-logs.index')" :active="request()->routeIs('admin.audit-logs.*')">Audit Logs</x-navigation.link>
        @elseif ($isCounselor)
            <p class="nav-section-label">Main</p>
            <x-navigation.link :href="route('counselor.dashboard')" :active="request()->routeIs('counselor.dashboard')">Dashboard</x-navigation.link>

            <p class="nav-section-label">Guidance</p>
            <x-navigation.link :href="route('counselor.guidance.appointments')" :active="request()->routeIs('counselor.guidance.appointments*')">My Appointments</x-navigation.link>
            <x-navigation.link :href="route('counselor.guidance.availability')" :active="request()->routeIs('counselor.guidance.availability*')">Availability</x-navigation.link>

            <p class="nav-section-label">OSAS</p>
            <x-navigation.link :href="route('enrollment-verifications.index')" :active="request()->routeIs('enrollment-verifications.*')">Enrollment Verification</x-navigation.link>
        @else
            <p class="nav-section-label">Main</p>
            <x-navigation.link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">Dashboard</x-navigation.link>

            <p class="nav-section-label">Scholarships</p>
            <x-navigation.link
                :href="route('student.scholarships.index')"
                :active="request()->routeIs('student.scholarships.index|student.scholarships.show|student.scholarships.eligibility')"
            >Browse Scholarships</x-navigation.link>
            <x-navigation.link :href="route('student.scholarships.recommendations')" :active="request()->routeIs('student.scholarships.recommendations')">Recommended Scholarships</x-navigation.link>
            <x-navigation.link :href="route('student.applications.index')" :active="request()->routeIs('student.applications.*')">My Applications</x-navigation.link>

            <p class="nav-section-label">Services</p>
            <x-navigation.link :href="route('student.guidance.appointments')" :active="request()->routeIs('student.guidance.*')">Guidance Appointments</x-navigation.link>
            <x-navigation.link :href="route('student.services.catalog')" :active="request()->routeIs('student.services.*')">OSAS Services</x-navigation.link>
        @endif

        <p class="nav-section-label">Account</p>
        @if ($isStudent)
            <x-navigation.link :href="route('student.profile.show')" :active="request()->routeIs('student.profile.*')">My Profile</x-navigation.link>
        @endif
        <x-navigation.link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
            Notifications
            @if ($unreadCount > 0)
                <span class="notification-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                <span class="sr-only">{{ $unreadCount }} unread</span>
            @endif
        </x-navigation.link>
    </nav>

    <div class="sidebar-footer">
        &copy; {{ date('Y') }} Isabela State University<br>
        Office of Student Affairs and Services
    </div>
</aside>
