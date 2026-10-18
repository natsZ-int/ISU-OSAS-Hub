@extends('layouts.app')

@section('title', 'Counselor Dashboard — ISU OSAS Hub')

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

    <div class="welcome-banner">
        <h1>Welcome, {{ $counselor['name'] }}!</h1>
        <p>
            Guidance Office &bull; {{ now()->format('l, F j, Y') }} &bull;
            Role: {{ $counselor['role_label'] }}
        </p>
    </div>

    <div class="stat-grid">
        <x-metric-card
            label="Today's Appointments"
            :value="$guidance['today_appointments']->count()"
            :href="route('counselor.guidance.appointments')"
            hint="Scheduled for today"
        />
        <x-metric-card
            label="Pending Requests"
            :value="$guidance['pending_request_count']"
            tone="warning"
            :href="route('counselor.guidance.appointments', ['status' => 'requested'])"
            hint="Awaiting your confirmation"
        />
        <x-metric-card
            label="Upcoming"
            :value="$guidance['upcoming_appointments']->count()"
            tone="info"
            :href="route('counselor.guidance.appointments')"
            hint="Next scheduled sessions"
        />
        <x-metric-card
            label="Completed Sessions"
            :value="$guidance['completed_count']"
            tone="success"
            hint="Total completed sessions"
        />
    </div>

    <div class="dashboard-columns">

        <div>
            {{-- Today's Appointments --}}
            <div class="dashboard-panel" style="margin-bottom:1.35rem;">
                <div class="dashboard-panel-header">
                    <h3>Today's Appointments</h3>
                    <a href="{{ route('counselor.guidance.appointments') }}">View all &rarr;</a>
                </div>

                @if ($guidance['today_appointments']->isEmpty())
                    <x-empty-state
                        title="No appointments scheduled today."
                        message="Your schedule for today is clear."
                    />
                @else
                    <x-table-wrapper label="Today's appointments" :showHint="true">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">Student</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Time</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($guidance['today_appointments'] as $appt)
                                    <tr>
                                        <td style="font-weight:600;">
                                            {{ $appt->studentProfile?->first_name }} {{ $appt->studentProfile?->last_name }}
                                        </td>
                                        <td>{{ $appt->appointmentType?->name ?? $appt->appointment_type ?? 'General' }}</td>
                                        <td class="font-monospace" style="font-size:.82rem;white-space:nowrap;">
                                            {{ \Carbon\Carbon::parse($appt->start_time)->format('h:i A') }}
                                            &ndash;
                                            {{ \Carbon\Carbon::parse($appt->end_time)->format('h:i A') }}
                                        </td>
                                        <td><x-status-badge :status="$appt->status" /></td>
                                        <td class="actions-cell">
                                            <a href="{{ route('counselor.guidance.appointments.show', $appt->id) }}" class="btn btn-outline-primary btn-sm">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-table-wrapper>
                @endif
            </div>

            {{-- Pending Requests --}}
            <div class="dashboard-panel" style="margin-bottom:1.35rem;">
                <div class="dashboard-panel-header">
                    <h3>
                        Pending Requests
                        @if ($guidance['pending_request_count'] > 0)
                            <span class="badge badge-warning badge-plain">{{ $guidance['pending_request_count'] }}</span>
                        @endif
                    </h3>
                    <a href="{{ route('counselor.guidance.appointments', ['status' => 'requested']) }}">View all &rarr;</a>
                </div>

                @if ($guidance['pending_requests']->isEmpty())
                    <x-empty-state
                        title="No pending appointment requests."
                        message="There are no requests waiting for your confirmation."
                    />
                @else
                    @foreach ($guidance['pending_requests'] as $req)
                        <div class="appointment-mini-card">
                            <div class="appointment-mini-head">
                                <strong style="font-size:.9rem;">
                                    {{ $req->studentProfile?->first_name }} {{ $req->studentProfile?->last_name }}
                                </strong>
                                <x-status-badge :status="$req->status" />
                            </div>
                            <div class="appointment-mini-details" style="margin-bottom:.5rem;">
                                <span>
                                    {{ $req->appointmentType?->name ?? $req->appointment_type ?? 'General' }}
                                    &bull; {{ $req->appointment_date->format('M d, Y') }}
                                    &bull; {{ \Carbon\Carbon::parse($req->start_time)->format('h:i A') }}
                                    &ndash; {{ \Carbon\Carbon::parse($req->end_time)->format('h:i A') }}
                                </span>
                                @if ($req->reason)
                                    <span><strong>Reason:</strong> {{ \Illuminate\Support\Str::limit($req->reason, 90) }}</span>
                                @endif
                            </div>
                            <div class="button-row">
                                <form action="{{ route('counselor.guidance.appointments.confirm', $req->id) }}" method="POST" class="inline-form">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success btn-sm">Confirm</button>
                                </form>
                                <a href="{{ route('counselor.guidance.appointments.show', $req->id) }}" class="btn btn-outline-dark btn-sm">Review</a>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Upcoming Appointments --}}
            <div class="dashboard-panel" style="margin-bottom:1.35rem;">
                <div class="dashboard-panel-header">
                    <h3>Upcoming Appointments</h3>
                    <a href="{{ route('counselor.guidance.appointments') }}">View all &rarr;</a>
                </div>

                @if ($guidance['upcoming_appointments']->isEmpty())
                    <x-empty-state
                        title="No upcoming appointments."
                        message="Newly confirmed sessions will appear here."
                    />
                @else
                    <x-table-wrapper label="Upcoming appointments">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">Student</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Time</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($guidance['upcoming_appointments'] as $appt)
                                    <tr>
                                        <td style="font-weight:600;">
                                            {{ $appt->studentProfile?->first_name }} {{ $appt->studentProfile?->last_name }}
                                        </td>
                                        <td>{{ $appt->appointmentType?->name ?? $appt->appointment_type ?? 'General' }}</td>
                                        <td style="font-size:.82rem;white-space:nowrap;">{{ $appt->appointment_date->format('M d, Y') }}</td>
                                        <td class="font-monospace" style="font-size:.82rem;white-space:nowrap;">
                                            {{ \Carbon\Carbon::parse($appt->start_time)->format('h:i A') }}
                                            &ndash;
                                            {{ \Carbon\Carbon::parse($appt->end_time)->format('h:i A') }}
                                        </td>
                                        <td><x-status-badge :status="$appt->status" /></td>
                                        <td class="actions-cell">
                                            <a href="{{ route('counselor.guidance.appointments.show', $appt->id) }}" class="btn btn-outline-primary btn-sm">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-table-wrapper>
                @endif
            </div>

            {{-- Recently Completed --}}
            <div class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h3>Recently Completed</h3>
                </div>

                @if ($guidance['recent_completed']->isEmpty())
                    <x-empty-state
                        title="No completed appointments."
                        message="Completed counselling sessions will be listed here."
                    />
                @else
                    <x-table-wrapper label="Recently completed appointments">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">Student</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Time</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($guidance['recent_completed'] as $appt)
                                    <tr>
                                        <td style="font-weight:600;">
                                            {{ $appt->studentProfile?->first_name }} {{ $appt->studentProfile?->last_name }}
                                        </td>
                                        <td>{{ $appt->appointmentType?->name ?? $appt->appointment_type ?? 'General' }}</td>
                                        <td style="font-size:.82rem;white-space:nowrap;">{{ $appt->appointment_date->format('M d, Y') }}</td>
                                        <td class="font-monospace" style="font-size:.82rem;white-space:nowrap;">
                                            {{ \Carbon\Carbon::parse($appt->start_time)->format('h:i A') }}
                                            &ndash;
                                            {{ \Carbon\Carbon::parse($appt->end_time)->format('h:i A') }}
                                        </td>
                                        <td class="actions-cell">
                                            <a href="{{ route('counselor.guidance.appointments.show', $appt->id) }}" class="btn btn-outline-dark btn-sm">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-table-wrapper>
                @endif
            </div>
        </div>

        <div>
            {{-- Quick Actions --}}
            <div class="welcome-banner" style="padding:1.35rem;margin-bottom:1.35rem;">
                <h2 style="font-size:1.05rem;">Quick Actions</h2>
                <div style="display:flex;flex-direction:column;gap:.55rem;margin-top:.85rem;">
                    <a href="{{ route('counselor.guidance.availability') }}" class="btn btn-white btn-sm" style="justify-content:flex-start;">Manage Availability</a>
                    <a href="{{ route('counselor.guidance.appointments') }}" class="btn btn-white btn-sm" style="justify-content:flex-start;">View All Appointments</a>
                    <a href="{{ route('counselor.guidance.appointments', ['status' => 'requested']) }}" class="btn btn-white btn-sm" style="justify-content:flex-start;">Pending Requests</a>
                </div>
            </div>

            {{-- Weekly Availability --}}
            <div class="dashboard-panel">
                <div class="dashboard-panel-header">
                    <h3>Weekly Availability</h3>
                    <a href="{{ route('counselor.guidance.availability') }}">Edit</a>
                </div>

                @php
                    $activeSlots = $guidance['availabilities']->where('is_available', true);
                @endphp

                @if ($activeSlots->isEmpty())
                    <x-empty-state
                        title="No availability has been set."
                        message="Set your weekly counselling windows so students can book."
                        :actionUrl="route('counselor.guidance.availability')"
                        actionLabel="Set Availability"
                    />
                @else
                    <ul class="module-list">
                        @foreach ($activeSlots as $slot)
                            <li style="display:flex;justify-content:space-between;gap:.75rem;">
                                <strong style="font-size:.88rem;">{{ $slot->getDayName() }}</strong>
                                <span class="font-monospace" style="font-size:.8rem;color:var(--muted);">
                                    {{ \Carbon\Carbon::parse($slot->start_time)->format('h:i A') }}
                                    &ndash;
                                    {{ \Carbon\Carbon::parse($slot->end_time)->format('h:i A') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="muted" style="margin:.9rem 0 0;text-align:center;font-size:.8rem;">
                        {{ $activeSlots->count() }} active {{ Str::plural('window', $activeSlots->count()) }}
                        across {{ $activeSlots->pluck('day_of_week')->unique()->count() }}
                        {{ Str::plural('day', $activeSlots->pluck('day_of_week')->unique()->count()) }}
                    </p>
                @endif
            </div>
        </div>

    </div>

@endsection
