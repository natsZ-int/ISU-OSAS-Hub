@extends('layouts.app')
@section('title', 'Administrator Dashboard - OSAS Hub')

@section('content')
<div class="container py-4">
    <!-- Header Welcome Card -->
    <div style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #fff; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        <h2 style="font-size: 1.75rem; font-weight: 700; margin: 0 0 0.5rem 0;">Welcome, {{ Auth::user()->name }}!</h2>
        <p style="margin: 0; font-size: 0.95rem; opacity: 0.9;">Here is an operational overview of the ISU Office of Student Affairs and Services Hub.</p>
    </div>

    <!-- Quick Action Links -->
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; color: #1e293b;">Quick Management Actions</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline" style="text-decoration: none; padding: 0.75rem; text-align: center; font-size: 0.85rem; font-weight: 600;">Manage Users</a>
            <a href="{{ route('admin.scholarships.index') }}" class="btn btn-outline" style="text-decoration: none; padding: 0.75rem; text-align: center; font-size: 0.85rem; font-weight: 600;">Manage Scholarships</a>
            <a href="{{ route('admin.scholarship-applications.index') }}" class="btn btn-outline" style="text-decoration: none; padding: 0.75rem; text-align: center; font-size: 0.85rem; font-weight: 600;">Review Applications</a>
            <a href="{{ route('admin.student-service-requests.index') }}" class="btn btn-outline" style="text-decoration: none; padding: 0.75rem; text-align: center; font-size: 0.85rem; font-weight: 600;">Service Requests</a>
            <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline" style="text-decoration: none; padding: 0.75rem; text-align: center; font-size: 0.85rem; font-weight: 600;">System Audit Logs</a>
            <a href="{{ route('admin.analytics.index') }}" class="btn btn-outline" style="text-decoration: none; padding: 0.75rem; text-align: center; font-size: 0.85rem; font-weight: 600;">Analytics &amp; Reports</a>
        </div>
    </div>

    <!-- Operational Metrics Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- User Overview -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem;">
            <h4 style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">Account Metrics</h4>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #4b5563;">Students:</span>
                    <strong style="color: #1f2937;">{{ $metrics['users']['total_students'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #4b5563;">Counselors:</span>
                    <strong style="color: #1f2937;">{{ $metrics['users']['total_counselors'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #4b5563;">Administrators:</span>
                    <strong style="color: #1f2937;">{{ $metrics['users']['total_administrators'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0; margin-top: 0.25rem;">
                    <span style="color: #2563eb; font-weight: 600;">Active Accounts:</span>
                    <strong style="color: #2563eb;">{{ $metrics['users']['active_users'] }}</strong>
                </div>
            </div>
        </div>

        <!-- Scholarship Metrics -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem;">
            <h4 style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">Scholarship Overview</h4>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #4b5563;">Active Grants:</span>
                    <strong style="color: #1f2937;">{{ $metrics['scholarships']['active_count'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #ca8a04; font-weight: 600;">Pending Apps:</span>
                    <strong style="color: #ca8a04;">{{ $metrics['scholarships']['pending_applications'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #166534;">Approved:</span>
                    <strong style="color: #166534;">{{ $metrics['scholarships']['approved_applications'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #991b1b;">Rejected:</span>
                    <strong style="color: #991b1b;">{{ $metrics['scholarships']['rejected_applications'] }}</strong>
                </div>
            </div>
        </div>

        <!-- Guidance Metrics -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem;">
            <h4 style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">Guidance Appointments</h4>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #2563eb; font-weight: 600;">Upcoming:</span>
                    <strong style="color: #2563eb;">{{ $metrics['guidance']['upcoming_count'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #ca8a04;">Awaiting Counselor:</span>
                    <strong style="color: #1f2937;">{{ $metrics['guidance']['pending_count'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #1f2937;">Sessions Completed:</span>
                    <strong style="color: #1f2937;">{{ $metrics['guidance']['completed_count'] }}</strong>
                </div>
            </div>
        </div>

        <!-- Student Services Metrics -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem;">
            <h4 style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">Student Services Queue</h4>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #b45309; font-weight: 600;">Awaiting Action:</span>
                    <strong style="color: #b45309;">{{ $metrics['services']['pending_count'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #4b5563;">Processing Queue:</span>
                    <strong style="color: #1f2937;">{{ $metrics['services']['processing_count'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #991b1b;">Requires Info:</span>
                    <strong style="color: #991b1b;">{{ $metrics['services']['requires_info_count'] }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                    <span style="color: #166534;">Completed Requests:</span>
                    <strong style="color: #166534;">{{ $metrics['services']['completed_count'] }}</strong>
                </div>
            </div>
        </div>

    </div>

    <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 1.5rem;">
        
        <!-- Left: Recent System Audit Activity -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                <h3 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0;">Recent Audit Actions</h3>
                <a href="{{ route('admin.audit-logs.index') }}" style="font-size: 0.8rem; color: #2563eb; text-decoration: none;">View All Logs &rarr;</a>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid #f1f5f9; color: #475569;">
                            <th style="padding: 0.5rem 0.25rem;">Timestamp</th>
                            <th style="padding: 0.5rem 0.25rem;">Actor</th>
                            <th style="padding: 0.5rem 0.25rem;">Module / Action</th>
                            <th style="padding: 0.5rem 0.25rem;">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($metrics['recent_activity']['audit_logs'] as $log)
                            <tr style="border-bottom: 1px solid #f8fafc;">
                                <td style="padding: 0.5rem 0.25rem; white-space: nowrap; color: #64748b;">
                                    {{ $log->created_at ? $log->created_at->format('M d, h:i A') : '—' }}
                                </td>
                                <td style="padding: 0.5rem 0.25rem; font-weight: 600;">
                                    {{ $log->user?->name ?? 'System' }}
                                </td>
                                <td style="padding: 0.5rem 0.25rem; white-space: nowrap;">
                                    <span style="font-size: 0.75rem; background: #e0f2fe; color: #0369a1; padding: 0.15rem 0.45rem; border-radius: 4px; font-weight: 600;">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td style="padding: 0.5rem 0.25rem; color: #475569; max-width: 200px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                    {{ $log->description }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 2rem; color: #64748b;">No recent system audit records.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Upcoming Guidance Appointments -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">
                <h3 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0;">Nearest Upcoming Appointments</h3>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                @forelse($metrics['guidance']['upcoming_items'] as $appt)
                    <div style="border: 1px solid #f1f5f9; border-radius: 6px; padding: 0.75rem; background: #f9fafb;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                            <strong style="font-size: 0.85rem; color: #1e293b;">
                                {{ $appt->studentProfile?->first_name }} {{ $appt->studentProfile?->last_name }}
                            </strong>
                            <span style="font-size: 0.7rem; padding: 0.1rem 0.4rem; border-radius: 9999px; background: {{ $appt->status === 'confirmed' ? '#dcfce7; color: #166534;' : '#fef3c7; color: #92400e;' }}; font-weight: 600;">
                                {{ ucfirst($appt->status) }}
                            </span>
                        </div>
                        <div style="font-size: 0.75rem; color: #475569; display: flex; flex-direction: column; gap: 0.1rem;">
                            <span>Date: <strong>{{ $appt->appointment_date ? $appt->appointment_date->format('M d, Y') : '—' }}</strong></span>
                            <span>Time: <strong>{{ \Carbon\Carbon::createFromFormat('H:i:s', $appt->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::createFromFormat('H:i:s', $appt->end_time)->format('h:i A') }}</strong></span>
                            <span>Counselor: <strong>{{ $appt->counselor?->name ?? '—' }}</strong></span>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 2rem; color: #64748b; font-size: 0.85rem; border: 1px dashed #cbd5e1; border-radius: 6px;">
                        No upcoming guidance sessions scheduled.
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection