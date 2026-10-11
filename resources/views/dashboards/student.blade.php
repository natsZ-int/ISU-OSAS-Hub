@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="container py-4">

    {{-- Unread Notifications Callout Banner --}}
    @if($unreadNotificationCount > 0)
        <div class="alert alert-primary d-flex justify-content-between align-items-center mb-4" role="alert">
            <div>
                <i class="bi bi-bell-fill me-2"></i>
                You have <strong>{{ $unreadNotificationCount }}</strong> unread {{ Str::plural('notification', $unreadNotificationCount) }}.
            </div>
            <a href="{{ route('notifications.index') }}" class="btn btn-primary btn-sm">
                View All
            </a>
        </div>
    @endif

    {{-- Profile Setup Prompt (If Profile is Missing) --}}
    @if(!$profile)
        <div class="card border-warning mb-4 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-1 mb-3"></i>
                <h4 class="card-title text-warning fw-bold">Student Profile Required</h4>
                <p class="card-text text-muted max-w-lg mx-auto">
                    To access automated scholarship evaluations, custom recommendations, counseling bookings, and student request features, you must first complete your student profile.
                </p>
                <div class="mt-4">
                    <span class="text-secondary">Please contact your administrator to register your Student Profile.</span>
                </div>
            </div>
        </div>
    @else
        {{-- Welcome & Student Profile Header Section --}}
        <div class="card mb-4 bg-light shadow-sm">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="text-uppercase tracking-wider text-muted font-monospace small">Student Portal</span>
                    <h2 class="h3 fw-bold mb-1 text-primary">Welcome, {{ $profile->first_name }} {{ $profile->last_name }}</h2>
                    <p class="text-muted mb-0">
                        Campus System Overview &bull; Keep track of your applications and scheduler appointments.
                    </p>
                </div>
                <div class="bg-white border p-3 rounded shadow-sm">
                    <div class="row g-2">
                        <div class="col-6 font-semibold text-secondary small">Student No:</div>
                        <div class="col-6 text-dark font-monospace small text-end">{{ $profile->student_number }}</div>
                        <div class="col-6 font-semibold text-secondary small">Program & Yr:</div>
                        <div class="col-6 text-dark small text-end">{{ $profile->program }} - Yr {{ $profile->year_level }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Core Metrics Grid --}}
        <div class="row g-3 mb-4">
            {{-- Metric Box: Active Applications --}}
            <div class="col-md-3">
                <div class="card h-100 border-start border-primary border-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-uppercase text-secondary font-semibold small">Active Applications</span>
                            <i class="bi bi-file-earmark-text text-primary fs-4"></i>
                        </div>
                        <h3 class="fw-bold mb-1">{{ $scholarshipData['activeCount'] }}</h3>
                        <p class="text-muted small mb-0">Submitted & under review</p>
                    </div>
                </div>
            </div>

            {{-- Metric Box: Draft Applications --}}
            <div class="col-md-3">
                <div class="card h-100 border-start border-warning border-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-uppercase text-secondary font-semibold small">Draft Applications</span>
                            <i class="bi bi-pencil-square text-warning fs-4"></i>
                        </div>
                        <h3 class="fw-bold mb-1">{{ $scholarshipData['draftCount'] }}</h3>
                        <p class="text-muted small mb-0">Requires upload & submission</p>
                    </div>
                </div>
            </div>

            {{-- Metric Box: Active Service Requests --}}
            <div class="col-md-3">
                <div class="card h-100 border-start border-info border-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-uppercase text-secondary font-semibold small">Service Requests</span>
                            <i class="bi bi-gear text-info fs-4"></i>
                        </div>
                        <h3 class="fw-bold mb-1">{{ $servicesData['activeCount'] }}</h3>
                        <p class="text-muted small mb-0">Awaiting processing</p>
                    </div>
                </div>
            </div>

            {{-- Metric Box: Notifications Count --}}
            <div class="col-md-3">
                <div class="card h-100 border-start border-secondary border-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-uppercase text-secondary font-semibold small">Unread Alerts</span>
                            <i class="bi bi-bell text-secondary fs-4"></i>
                        </div>
                        <h3 class="fw-bold mb-1">{{ $unreadNotificationCount }}</h3>
                        <p class="text-muted small mb-0">Personal system notifications</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions & Details Area --}}
        <div class="row g-4">
            
            {{-- Left Area: Quick Actions & Live Dynamic Modules --}}
            <div class="col-lg-8">
                
                {{-- Module A: Recommended Scholarships --}}
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-award text-primary me-2"></i>Recommended Scholarships
                        </h5>
                        <a href="{{ route('student.scholarships.recommendations') }}" class="btn btn-outline-primary btn-sm">
                            View All Recommendation Details
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @if($scholarshipData['recommendations']->isEmpty())
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-2 text-secondary mb-2"></i>
                                <p class="mb-0">No eligible matched scholarships found matching your profile currently.</p>
                            </div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach($scholarshipData['recommendations'] as $rec)
                                    <div class="list-group-item p-4">
                                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <h6 class="mb-1 fw-bold text-dark">{{ $rec->scholarshipTitle }}</h6>
                                                <p class="text-muted small mb-0">Provider: {{ $rec->provider }}</p>
                                            </div>
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                {{ $rec->matchScore }}% Match Score
                                            </span>
                                        </div>
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 text-secondary small">
                                            <div>
                                                <i class="bi bi-calendar-event me-1"></i> Deadline: 
                                                <span class="text-dark font-monospace">{{ $rec->deadline }}</span>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('student.scholarships.show', $rec->scholarshipId) }}" class="btn btn-light btn-sm text-primary">
                                                    View Details
                                                </a>
                                                @if(!$rec->existingApplicationId)
                                                    <form action="{{ route('student.applications.store', $rec->scholarshipId) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-sm">Apply Now</button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small mt-1">Application Started</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Module B: Guidance Scheduling Overview --}}
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-calendar3-event text-primary me-2"></i>Upcoming Guidance Appointment
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(!$guidanceData['upcoming'])
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x fs-2 text-secondary mb-2"></i>
                                <p class="mb-2">No upcoming guidance appointment scheduled.</p>
                                <a href="{{ route('student.guidance.counselors') }}" class="btn btn-primary btn-sm mt-1">
                                    Book an Appointment
                                </a>
                            </div>
                        @else
                            @php
                                $appt = $guidanceData['upcoming'];
                            @endphp
                            <div class="row align-items-center">
                                <div class="col-md-7 mb-3 mb-md-0">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white px-3 py-2 rounded text-center me-3">
                                            <span class="d-block small text-uppercase">{{ $appt->appointment_date->format('M') }}</span>
                                            <span class="d-block h4 fw-bold mb-0">{{ $appt->appointment_date->format('d') }}</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-bold text-dark">{{ $appt->appointmentType->name ?? $appt->appointment_type }}</h6>
                                            <p class="text-muted small mb-0">Counselor: <strong>{{ $appt->counselor->name }}</strong></p>
                                        </div>
                                    </div>
                                    <div class="text-secondary small">
                                        <i class="bi bi-clock me-1"></i> Time: 
                                        <span class="text-dark font-monospace">
                                            {{ \Carbon\Carbon::parse($appt->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($appt->end_time)->format('h:i A') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-5 text-md-end">
                                    <div class="mb-3">
                                        @if($appt->status === 'confirmed')
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">Confirmed</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">Requested</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('student.guidance.appointments.show', $appt->id) }}" class="btn btn-outline-secondary btn-sm">
                                        View Appointment details
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Module C: Recent Completed Request --}}
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-check-circle text-primary me-2"></i>Completed Service Requests
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(!$servicesData['recentCompleted'])
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-journal-check fs-2 text-secondary mb-2"></i>
                                <p class="mb-0">No service requests have been completed yet.</p>
                            </div>
                        @else
                            @php $req = $servicesData['recentCompleted']; @endphp
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark">{{ $req->serviceType->name }}</h6>
                                    <p class="text-muted small mb-1">Request Ref: <span class="font-monospace">{{ $req->request_number }}</span></p>
                                    <p class="text-success small mb-0">
                                        <i class="bi bi-calendar-check me-1"></i> Completed on {{ $req->completed_at->format('M d, Y') }}
                                    </p>
                                </div>
                                <a href="{{ route('student.services.show', $req->id) }}" class="btn btn-light btn-sm text-primary">
                                    View Details
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Right Area: Sidebar for Quick Navigation & Active Trackers --}}
            <div class="col-lg-4">
                
                {{-- Navigation Block: Student Quick Links --}}
                <div class="card mb-4 shadow-sm border-0 bg-primary text-white">
                    <div class="card-body py-4">
                        <h5 class="card-title fw-bold mb-3">Quick Shortcuts</h5>
                        <div class="d-grid gap-2">
                            <a href="{{ route('student.scholarships.index') }}" class="btn btn-white text-primary text-start font-semibold d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-search me-2"></i>Find Scholarships</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                            <a href="{{ route('student.guidance.counselors') }}" class="btn btn-white text-primary text-start font-semibold d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-calendar-plus me-2"></i>Book Appointments</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                            <a href="{{ route('student.services.catalog') }}" class="btn btn-white text-primary text-start font-semibold d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-file-earmark-plus me-2"></i>Request Service</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Module D: Application FSM Track Status Card --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-hourglass-split text-primary me-2"></i>Active Application Track
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(!$scholarshipData['recent'])
                            <div class="text-center py-4 text-secondary small">
                                <i class="bi bi-inbox fs-3 text-muted mb-2 d-block"></i>
                                No active applications under FSM workflow currently.
                            </div>
                        @else
                            @php $app = $scholarshipData['recent']; @endphp
                            <div class="mb-3">
                                <span class="text-uppercase tracking-wider font-monospace text-muted small d-block mb-1">
                                    REF #{{ $app->application_number }}
                                </span>
                                <h6 class="fw-bold text-dark mb-1">{{ $app->scholarship->title }}</h6>
                                <p class="text-muted small mb-0">Last Updated: {{ $app->updated_at->diffForHumans() }}</p>
                            </div>
                            
                            <hr class="my-3">

                            {{-- Active Tracker Indicator Elements --}}
                            <div class="mb-4">
                                <div class="d-flex justify-content-between text-secondary small mb-2">
                                    <span>Current Status:</span>
                                    <span class="fw-bold text-primary">{{ is_object($app->status) ? $app->status->label() : ucfirst($app->status) }}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    @php
                                        $percent = 33;
                                        $statusStr = is_object($app->status) ? $app->status->value : $app->status;
                                        if($statusStr === 'under_review') $percent = 66;
                                        if($statusStr === 'document_verification') $percent = 100;
                                    @endphp
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <a href="{{ route('student.applications.show', $app->id) }}" class="btn btn-outline-primary btn-sm">
                                    Track Complete Progress
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>
    @endif
</div>
@endsection