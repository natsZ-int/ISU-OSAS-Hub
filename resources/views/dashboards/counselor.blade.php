@extends('layouts.app')

@section('title', 'Counselor Dashboard')

@section('content')
<div class="container py-4">

    {{-- Unread Notifications Banner --}}
    @if($unreadNotificationCount > 0)
        <div class="alert alert-primary d-flex justify-content-between align-items-center mb-4" role="alert">
            <div>
                <i class="bi bi-bell-fill me-2"></i>
                You have <strong>{{ $unreadNotificationCount }}</strong> unread {{ Str::plural('notification', $unreadNotificationCount) }}.
            </div>
            <a href="{{ route('notifications.index') }}" class="btn btn-primary btn-sm">View All</a>
        </div>
    @endif

    {{-- Welcome Section --}}
    <div class="card mb-4 bg-light shadow-sm">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="text-uppercase tracking-wider text-muted font-monospace small">Guidance Office</span>
                <h2 class="h3 fw-bold mb-1 text-primary">Welcome, {{ $counselor['name'] }}</h2>
                <p class="text-muted mb-0">
                    Counselor Dashboard &bull; {{ now()->format('l, F j, Y') }}
                </p>
            </div>
            <div class="bg-white border p-3 rounded shadow-sm">
                <div class="row g-2">
                    <div class="col-6 font-semibold text-secondary small">Role:</div>
                    <div class="col-6 text-dark small text-end">{{ $counselor['role_label'] }}</div>
                    <div class="col-6 font-semibold text-secondary small">Active Slots:</div>
                    <div class="col-6 text-dark small text-end">{{ $guidance['active_availability_count'] }} windows</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Core Metrics Grid --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100 border-start border-primary border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase text-secondary font-semibold small">Today's Schedule</span>
                        <i class="bi bi-calendar-day text-primary fs-4"></i>
                    </div>
                    <h3 class="fw-bold mb-1">{{ $guidance['today_appointments']->count() }}</h3>
                    <p class="text-muted small mb-0">Appointments today</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-start border-warning border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase text-secondary font-semibold small">Pending Requests</span>
                        <i class="bi bi-hourglass-split text-warning fs-4"></i>
                    </div>
                    <h3 class="fw-bold mb-1">{{ $guidance['pending_request_count'] }}</h3>
                    <p class="text-muted small mb-0">Awaiting your action</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-start border-info border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase text-secondary font-semibold small">Upcoming</span>
                        <i class="bi bi-calendar-event text-info fs-4"></i>
                    </div>
                    <h3 class="fw-bold mb-1">{{ $guidance['upcoming_appointments']->count() }}</h3>
                    <p class="text-muted small mb-0">Next scheduled sessions</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 border-start border-success border-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase text-secondary font-semibold small">Completed</span>
                        <i class="bi bi-check-circle text-success fs-4"></i>
                    </div>
                    <h3 class="fw-bold mb-1">{{ $guidance['completed_count'] }}</h3>
                    <p class="text-muted small mb-0">Total completed sessions</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Area --}}
    <div class="row g-4">

        {{-- Left Column --}}
        <div class="col-lg-8">

            {{-- Today's Appointments --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-calendar-day text-primary me-2"></i>Today's Appointments
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($guidance['today_appointments']->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-2 text-secondary mb-2 d-block"></i>
                            <p class="mb-0">No appointments scheduled today.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Type</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($guidance['today_appointments'] as $appt)
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $appt->studentProfile?->first_name }} {{ $appt->studentProfile?->last_name }}
                                            </td>
                                            <td>{{ $appt->appointmentType?->name ?? $appt->appointment_type ?? 'General' }}</td>
                                            <td class="font-monospace small">
                                                {{ \Carbon\Carbon::parse($appt->start_time)->format('h:i A') }}
                                                –
                                                {{ \Carbon\Carbon::parse($appt->end_time)->format('h:i A') }}
                                            </td>
                                            <td>
                                                @if($appt->status === 'confirmed')
                                                    <span class="badge bg-success-subtle text-success border border-success">Confirmed</span>
                                                @elseif($appt->status === 'requested')
                                                    <span class="badge bg-warning-subtle text-warning border border-warning">Requested</span>
                                                @elseif($appt->status === 'completed')
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary">Completed</span>
                                                @else
                                                    <span class="badge bg-light text-dark border">{{ ucfirst($appt->status) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('counselor.guidance.appointments.show', $appt->id) }}" class="btn btn-outline-primary btn-sm">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Pending Appointment Requests --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-hourglass-split text-warning me-2"></i>Pending Requests
                        @if($guidance['pending_request_count'] > 0)
                            <span class="badge bg-warning text-dark ms-2">{{ $guidance['pending_request_count'] }}</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($guidance['pending_requests']->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check-all fs-2 text-secondary mb-2 d-block"></i>
                            <p class="mb-0">No pending appointment requests.</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($guidance['pending_requests'] as $req)
                                <div class="list-group-item p-3">
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-2">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-dark">
                                                {{ $req->studentProfile?->first_name }} {{ $req->studentProfile?->last_name }}
                                            </h6>
                                            <p class="text-muted small mb-1">
                                                {{ $req->appointmentType?->name ?? $req->appointment_type ?? 'General' }}
                                                &bull;
                                                {{ $req->appointment_date->format('M d, Y') }}
                                                &bull;
                                                {{ \Carbon\Carbon::parse($req->start_time)->format('h:i A') }}
                                                –
                                                {{ \Carbon\Carbon::parse($req->end_time)->format('h:i A') }}
                                            </p>
                                            @if($req->reason)
                                                <p class="text-secondary small mb-0">
                                                    <strong>Reason:</strong> {{ Str::limit($req->reason, 80) }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="d-flex gap-2 flex-shrink-0">
                                            <form action="{{ route('counselor.guidance.appointments.confirm', $req->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-success btn-sm">Confirm</button>
                                            </form>
                                            <a href="{{ route('counselor.guidance.appointments.show', $req->id) }}" class="btn btn-outline-secondary btn-sm">Review</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Upcoming Appointments --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-calendar-event text-info me-2"></i>Upcoming Appointments
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($guidance['upcoming_appointments']->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-2 text-secondary mb-2 d-block"></i>
                            <p class="mb-0">No upcoming appointments.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($guidance['upcoming_appointments'] as $appt)
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $appt->studentProfile?->first_name }} {{ $appt->studentProfile?->last_name }}
                                            </td>
                                            <td>{{ $appt->appointmentType?->name ?? $appt->appointment_type ?? 'General' }}</td>
                                            <td class="font-monospace small">{{ $appt->appointment_date->format('M d, Y') }}</td>
                                            <td class="font-monospace small">
                                                {{ \Carbon\Carbon::parse($appt->start_time)->format('h:i A') }}
                                                –
                                                {{ \Carbon\Carbon::parse($appt->end_time)->format('h:i A') }}
                                            </td>
                                            <td>
                                                @if($appt->status === 'confirmed')
                                                    <span class="badge bg-success-subtle text-success border border-success">Confirmed</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning border border-warning">Requested</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('counselor.guidance.appointments.show', $appt->id) }}" class="btn btn-outline-primary btn-sm">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recently Completed --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-check-circle text-success me-2"></i>Recently Completed
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($guidance['recent_completed']->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-journal-check fs-2 text-secondary mb-2 d-block"></i>
                            <p class="mb-0">No completed appointments.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($guidance['recent_completed'] as $appt)
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $appt->studentProfile?->first_name }} {{ $appt->studentProfile?->last_name }}
                                            </td>
                                            <td>{{ $appt->appointmentType?->name ?? $appt->appointment_type ?? 'General' }}</td>
                                            <td class="font-monospace small">{{ $appt->appointment_date->format('M d, Y') }}</td>
                                            <td class="font-monospace small">
                                                {{ \Carbon\Carbon::parse($appt->start_time)->format('h:i A') }}
                                                –
                                                {{ \Carbon\Carbon::parse($appt->end_time)->format('h:i A') }}
                                            </td>
                                            <td>
                                                <a href="{{ route('counselor.guidance.appointments.show', $appt->id) }}" class="btn btn-outline-secondary btn-sm">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Sidebar --}}
        <div class="col-lg-4">

            {{-- Quick Actions --}}
            <div class="card mb-4 shadow-sm border-0 bg-primary text-white">
                <div class="card-body py-4">
                    <h5 class="card-title fw-bold mb-3">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="{{ route('counselor.guidance.availability') }}" class="btn btn-white text-primary text-start font-semibold d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-clock me-2"></i>Manage Availability</span>
                            <i class="bi bi-arrow-right-short fs-5"></i>
                        </a>
                        <a href="{{ route('counselor.guidance.appointments') }}" class="btn btn-white text-primary text-start font-semibold d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-calendar3 me-2"></i>View All Appointments</span>
                            <i class="bi bi-arrow-right-short fs-5"></i>
                        </a>
                        <a href="{{ route('counselor.guidance.appointments', ['status' => 'requested']) }}" class="btn btn-white text-primary text-start font-semibold d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-hourglass me-2"></i>Pending Requests</span>
                            <i class="bi bi-arrow-right-short fs-5"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Weekly Availability Summary --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-clock text-primary me-2"></i>Weekly Availability
                    </h5>
                    <a href="{{ route('counselor.guidance.availability') }}" class="btn btn-outline-primary btn-sm">Edit</a>
                </div>
                <div class="card-body">
                    @php
                        $activeSlots = $guidance['availabilities']->where('is_available', true);
                    @endphp
                    @if($activeSlots->isEmpty())
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x fs-2 text-secondary mb-2 d-block"></i>
                            <p class="mb-2 small">No availability has been set.</p>
                            <a href="{{ route('counselor.guidance.availability') }}" class="btn btn-primary btn-sm">Set Availability</a>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($activeSlots as $slot)
                                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center border-0">
                                    <span class="fw-semibold text-dark small">{{ $slot->getDayName() }}</span>
                                    <span class="font-monospace text-secondary small">
                                        {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }}
                                        –
                                        {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <hr class="my-3">
                        <p class="text-muted small mb-0 text-center">
                            {{ $activeSlots->count() }} active {{ Str::plural('window', $activeSlots->count()) }}
                            across
                            {{ $activeSlots->pluck('day_of_week')->unique()->count() }}
                            {{ Str::plural('day', $activeSlots->pluck('day_of_week')->unique()->count()) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection