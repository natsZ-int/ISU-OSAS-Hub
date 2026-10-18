@extends('layouts.app')
@section('title', 'Administrator Dashboard — ISU OSAS Hub')

@section('content')
<div class="welcome-banner">
    <h1>Welcome, {{ Auth::user()->name }}!</h1>
    <p>Here is an operational overview of the ISU Office of Student Affairs and Services Hub.</p>
</div>

<div>
    <h3 class="section-heading">Quick Management Actions</h3>
    <div class="quick-actions-grid">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-dark quick-action-btn">Manage Users</a>
        <a href="{{ route('admin.scholarships.index') }}" class="btn btn-outline-dark quick-action-btn">Manage Scholarships</a>
        <a href="{{ route('admin.scholarship-applications.index') }}" class="btn btn-outline-dark quick-action-btn">Review Applications</a>
        <a href="{{ route('admin.student-service-requests.index') }}" class="btn btn-outline-dark quick-action-btn">Service Requests</a>
        <a href="{{ route('admin.guidance.appointments') }}" class="btn btn-outline-dark quick-action-btn">Guidance Management</a>
        <a href="{{ route('admin.analytics.index') }}" class="btn btn-primary quick-action-btn">Analytics &amp; Reports</a>
    </div>
</div>

<div class="metrics-grid">

    <div class="metric-card">
        <h4>Account Metrics</h4>
        <div class="metric-row">
            <span>Students:</span>
            <strong>{{ $metrics['users']['total_students'] }}</strong>
        </div>
        <div class="metric-row">
            <span>Counselors:</span>
            <strong>{{ $metrics['users']['total_counselors'] }}</strong>
        </div>
        <div class="metric-row">
            <span>Administrators:</span>
            <strong>{{ $metrics['users']['total_administrators'] }}</strong>
        </div>
        <div class="metric-row is-divider tone-primary">
            <span>Active Accounts:</span>
            <strong>{{ $metrics['users']['active_users'] }}</strong>
        </div>
    </div>

    <div class="metric-card">
        <h4>Scholarship Overview</h4>
        <div class="metric-row">
            <span>Active Grants:</span>
            <strong>{{ $metrics['scholarships']['active_count'] }}</strong>
        </div>
        <div class="metric-row tone-warning">
            <span>Pending Apps:</span>
            <strong>{{ $metrics['scholarships']['pending_applications'] }}</strong>
        </div>
        <div class="metric-row tone-success">
            <span>Approved:</span>
            <strong>{{ $metrics['scholarships']['approved_applications'] }}</strong>
        </div>
        <div class="metric-row tone-danger">
            <span>Rejected:</span>
            <strong>{{ $metrics['scholarships']['rejected_applications'] }}</strong>
        </div>
    </div>

    <div class="metric-card">
        <h4>Guidance Appointments</h4>
        <div class="metric-row tone-primary">
            <span>Upcoming:</span>
            <strong>{{ $metrics['guidance']['upcoming_count'] }}</strong>
        </div>
        <div class="metric-row">
            <span>Awaiting Counselor:</span>
            <strong>{{ $metrics['guidance']['pending_count'] }}</strong>
        </div>
        <div class="metric-row">
            <span>Sessions Completed:</span>
            <strong>{{ $metrics['guidance']['completed_count'] }}</strong>
        </div>
    </div>

    <div class="metric-card">
        <h4>Student Services Queue</h4>
        <div class="metric-row tone-warning">
            <span>Awaiting Action:</span>
            <strong>{{ $metrics['services']['pending_count'] }}</strong>
        </div>
        <div class="metric-row">
            <span>Processing Queue:</span>
            <strong>{{ $metrics['services']['processing_count'] }}</strong>
        </div>
        <div class="metric-row tone-danger">
            <span>Requires Info:</span>
            <strong>{{ $metrics['services']['requires_info_count'] }}</strong>
        </div>
        <div class="metric-row tone-success">
            <span>Completed Requests:</span>
            <strong>{{ $metrics['services']['completed_count'] }}</strong>
        </div>
    </div>

</div>

<div class="dashboard-columns">

    <div class="dashboard-panel">
        <div class="dashboard-panel-header">
            <h3>Recent Audit Actions</h3>
            <a href="{{ route('admin.audit-logs.index') }}">View All Logs &rarr;</a>
        </div>

        <x-table-wrapper label="Recent audit actions">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Timestamp</th>
                        <th scope="col">Actor</th>
                        <th scope="col">Module / Action</th>
                        <th scope="col">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($metrics['recent_activity']['audit_logs'] as $log)
                        <tr>
                            <td style="white-space:nowrap;">
                                {{ $log->created_at ? $log->created_at->format('M d, h:i A') : '—' }}
                            </td>
                            <td style="font-weight:600;">{{ $log->user?->name ?? 'System' }}</td>
                            <td style="white-space:nowrap;">
                                <span class="audit-action-chip">{{ $log->action }}</span>
                            </td>
                            <td class="text-ellipsis">{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-empty-state title="No recent system audit records." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table-wrapper>
    </div>

    <div class="dashboard-panel">
        <div class="dashboard-panel-header">
            <h3>Nearest Upcoming Appointments</h3>
            <a href="{{ route('admin.guidance.appointments') }}">View all &rarr;</a>
        </div>

        @forelse ($metrics['guidance']['upcoming_items'] as $appt)
            <div class="appointment-mini-card">
                <div class="appointment-mini-head">
                    <strong style="font-size:.9rem;">
                        {{ $appt->studentProfile?->first_name }} {{ $appt->studentProfile?->last_name }}
                    </strong>
                    <x-status-badge :status="$appt->status" />
                </div>
                <div class="appointment-mini-details">
                    <span>Date: <strong>{{ $appt->appointment_date ? $appt->appointment_date->format('M d, Y') : '—' }}</strong></span>
                    <span>Time: <strong>{{ \Carbon\Carbon::createFromFormat('H:i:s', $appt->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::createFromFormat('H:i:s', $appt->end_time)->format('h:i A') }}</strong></span>
                    <span>Counselor: <strong>{{ $appt->counselor?->name ?? '—' }}</strong></span>
                </div>
            </div>
        @empty
            <x-empty-state
                title="No upcoming appointments."
                message="There are no scheduled guidance sessions at this time." />
        @endforelse
    </div>

</div>
@endsection
