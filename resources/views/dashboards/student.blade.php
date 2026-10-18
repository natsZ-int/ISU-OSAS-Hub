@extends('layouts.app')

@section('title', 'Student Dashboard — ISU OSAS Hub')

@section('content')

    @if ($unreadNotificationCount > 0)
        <x-alert type="info">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;width:100%;">
                <span>
                    You have <strong>{{ $unreadNotificationCount }}</strong>
                    unread {{ Str::plural('notification', $unreadNotificationCount) }}.
                </span>
                <a href="{{ route('notifications.index') }}" class="btn btn-primary btn-sm">View Notifications</a>
            </div>
        </x-alert>
    @endif

    @if (! $profile)
        <x-card>
            <div class="empty-state-block">
                <div class="empty-state-icon" style="background:var(--warning-bg);color:var(--warning);" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                        <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path>
                        <path d="M12 9v4"></path>
                        <path d="M12 17h.01"></path>
                    </svg>
                </div>
                <p class="empty-state-title" style="color:var(--warning);">Student Profile Required</p>
                <p class="empty-state-message">
                    To access automated scholarship evaluations, custom recommendations, counselling bookings,
                    and student request features, you must first complete your student profile.
                </p>
                <p style="font-size:.85rem;color:var(--muted);">
                    Please contact your administrator to register your student profile.
                </p>
            </div>
        </x-card>
    @else

        <div class="welcome-banner">
            <h1>Welcome, {{ $profile->first_name }} {{ $profile->last_name }}!</h1>
            <p>
                Student Portal &bull; Keep track of your applications, appointments, and service requests.
            </p>
            <p style="margin-top:.65rem;font-size:.85rem;opacity:.95;">
                Student No: <strong class="font-monospace">{{ $profile->student_number }}</strong>
                &nbsp;&bull;&nbsp;
                Program: <strong>{{ $profile->program }} - Yr {{ $profile->year_level }}</strong>
            </p>
        </div>

        <div class="stat-grid">
            <x-metric-card
                label="Active Applications"
                :value="$scholarshipData['activeCount']"
                :href="route('student.applications.index')"
                hint="Submitted &amp; under review"
            />
            <x-metric-card
                label="Draft Applications"
                :value="$scholarshipData['draftCount']"
                tone="warning"
                :href="route('student.applications.index')"
                hint="Requires upload &amp; submission"
            />
            <x-metric-card
                label="Service Requests"
                :value="$servicesData['activeCount']"
                tone="info"
                :href="route('student.services.index')"
                hint="Awaiting processing"
            />
            <x-metric-card
                label="Unread Alerts"
                :value="$unreadNotificationCount"
                tone="neutral"
                :href="route('notifications.index')"
                hint="Personal system notifications"
            />
        </div>

        <div class="dashboard-columns">

            <div>
                {{-- Recommended Scholarships --}}
                <div class="dashboard-panel" style="margin-bottom:1.35rem;">
                    <div class="dashboard-panel-header">
                        <h3>Recommended Scholarships</h3>
                        <a href="{{ route('student.scholarships.recommendations') }}">View all &rarr;</a>
                    </div>

                    @if ($scholarshipData['recommendations']->isEmpty())
                        <x-empty-state
                            title="No recommended scholarships yet."
                            message="Complete your student profile to receive scholarship matches that fit your qualifications."
                            :actionUrl="route('student.scholarships.recommendations')"
                            actionLabel="View Recommendations"
                        />
                    @else
                        @foreach ($scholarshipData['recommendations'] as $rec)
                            <div class="appointment-mini-card">
                                <div class="appointment-mini-head">
                                    <strong style="font-size:.92rem;">{{ $rec->scholarshipTitle }}</strong>
                                    <span class="badge badge-success">{{ $rec->matchScore }}% Match</span>
                                </div>
                                <div class="appointment-mini-details" style="margin-bottom:.55rem;">
                                    <span>Provider: {{ $rec->provider }}</span>
                                    <span>Deadline: <strong>{{ $rec->deadline }}</strong></span>
                                </div>
                                <div class="button-row">
                                    <a href="{{ route('student.scholarships.show', $rec->scholarshipId) }}" class="btn btn-light btn-sm">View Details</a>
                                    @if (! $rec->existingApplicationId)
                                        <form action="{{ route('student.applications.store', $rec->scholarshipId) }}" method="POST" class="inline-form">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">Apply Now</button>
                                        </form>
                                    @else
                                        <span class="muted" style="font-size:.8rem;margin:0;">Application started</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Upcoming Guidance Appointment --}}
                <div class="dashboard-panel" style="margin-bottom:1.35rem;">
                    <div class="dashboard-panel-header">
                        <h3>Upcoming Guidance Appointment</h3>
                        <a href="{{ route('student.guidance.appointments') }}">All appointments &rarr;</a>
                    </div>

                    @if (! $guidanceData['upcoming'])
                        <x-empty-state
                            title="No upcoming guidance appointment."
                            message="Book a counselling session when you need guidance support."
                            :actionUrl="route('student.guidance.counselors')"
                            actionLabel="Book an Appointment"
                        />
                    @else
                        @php $appt = $guidanceData['upcoming']; @endphp
                        <div style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
                            <div class="date-tile" aria-hidden="true">
                                <span class="date-tile-month">{{ $appt->appointment_date->format('M') }}</span>
                                <span class="date-tile-day">{{ $appt->appointment_date->format('d') }}</span>
                            </div>
                            <div style="flex:1;min-width:200px;">
                                <h4 style="margin-bottom:.25rem;">{{ $appt->appointmentType?->name ?? $appt->appointment_type ?? 'General Counselling' }}</h4>
                                <p class="muted" style="font-size:.85rem;margin-bottom:.35rem;">
                                    Counselor: <strong>{{ $appt->counselor?->name }}</strong>
                                </p>
                                <p style="font-size:.85rem;color:var(--text);margin-bottom:.65rem;">
                                    <strong class="font-monospace">
                                        {{ \Carbon\Carbon::parse($appt->start_time)->format('h:i A') }}
                                        &ndash;
                                        {{ \Carbon\Carbon::parse($appt->end_time)->format('h:i A') }}
                                    </strong>
                                </p>
                                <div class="button-row">
                                    <x-status-badge :status="$appt->status" />
                                    <a href="{{ route('student.guidance.appointments.show', $appt->id) }}" class="btn btn-outline-dark btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Completed Service Requests --}}
                <div class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <h3>Completed Service Requests</h3>
                        <a href="{{ route('student.services.index') }}">All requests &rarr;</a>
                    </div>

                    @if (! $servicesData['recentCompleted'])
                        <x-empty-state
                            title="No completed service requests."
                            message="Service requests you have submitted will appear here."
                            :actionUrl="route('student.services.catalog')"
                            actionLabel="Browse OSAS Services"
                        />
                    @else
                        @php $req = $servicesData['recentCompleted']; @endphp
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
                            <div>
                                <h4 style="margin-bottom:.2rem;">{{ $req->serviceType?->name }}</h4>
                                <p class="muted" style="font-size:.82rem;margin-bottom:.3rem;">
                                    Request ref: <span class="font-monospace">{{ $req->request_number }}</span>
                                </p>
                                <p style="font-size:.84rem;color:var(--success);font-weight:600;margin:0;">
                                    Completed on {{ $req->completed_at?->format('M d, Y') }}
                                </p>
                            </div>
                            <a href="{{ route('student.services.show', $req->id) }}" class="btn btn-light btn-sm">View Details</a>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                {{-- Quick Shortcuts --}}
                <div class="welcome-banner" style="padding:1.35rem;margin-bottom:1.35rem;">
                    <h2 style="font-size:1.05rem;">Quick Shortcuts</h2>
                    <div style="display:flex;flex-direction:column;gap:.55rem;margin-top:.85rem;">
                        <a href="{{ route('student.scholarships.index') }}" class="btn btn-white btn-sm" style="justify-content:flex-start;">Find Scholarships</a>
                        <a href="{{ route('student.guidance.counselors') }}" class="btn btn-white btn-sm" style="justify-content:flex-start;">Book Appointments</a>
                        <a href="{{ route('student.services.catalog') }}" class="btn btn-white btn-sm" style="justify-content:flex-start;">Request OSAS Service</a>
                    </div>
                </div>

                {{-- Active Application Track --}}
                <div class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <h3>Active Application Track</h3>
                    </div>

                    @if (! $scholarshipData['recent'])
                        <x-empty-state
                            title="No active applications."
                            message="Applications under review will appear here with their progress."
                            :actionUrl="route('student.scholarships.index')"
                            actionLabel="Browse Scholarships"
                        />
                    @else
                        @php $app = $scholarshipData['recent']; @endphp
                        <p class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem;">
                            Ref #<span class="font-monospace">{{ $app->application_number }}</span>
                        </p>
                        <h4 style="margin-bottom:.25rem;">{{ $app->scholarship?->title }}</h4>
                        <p class="muted" style="font-size:.82rem;">
                            Last updated {{ $app->updated_at?->diffForHumans() }}
                        </p>

                        <div style="margin:1rem 0 .5rem;">
                            <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:.35rem;">
                                <span class="muted">Current status:</span>
                                <x-status-badge :status="$app->status" />
                            </div>
                            @php
                                $percent = 33;
                                $statusStr = is_object($app->status) ? $app->status->value : (string) $app->status;
                                if ($statusStr === 'under_review') {
                                    $percent = 66;
                                }
                                if ($statusStr === 'document_verification') {
                                    $percent = 90;
                                }
                                if ($statusStr === 'approved') {
                                    $percent = 100;
                                }
                            @endphp
                            <div class="progress" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Application progress">
                                <div class="progress-bar" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        <a href="{{ route('student.applications.show', $app->id) }}" class="btn btn-outline-primary btn-sm" style="width:100%;margin-top:.75rem;">
                            Track Complete Progress
                        </a>
                    @endif
                </div>
            </div>

        </div>

    @endif

@endsection
