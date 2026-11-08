@extends('layouts.app')
@section('title', 'Appointment Details — ISU OSAS Hub')

@section('content')
<x-page-header
    title="Guidance Appointment"
    subtitle="Read-only oversight view of a scheduled guidance session.">
    <x-slot:actions>
        <a href="{{ route('admin.guidance.appointments') }}" class="btn btn-outline-dark">&larr; Back to Appointments</a>
    </x-slot:actions>
</x-page-header>

<x-card>
    <div class="form-row">
        <div class="form-group">
            <span class="field-label">Student</span>
            <p>
                @if ($appointment->studentProfile)
                    <a href="{{ route('admin.student-profiles.show', $appointment->studentProfile) }}">
                        {{ trim($appointment->studentProfile->first_name.' '.$appointment->studentProfile->last_name) }}
                    </a>
                    <br><small class="muted">{{ $appointment->studentProfile->student_number }}</small>
                @else
                    N/A
                @endif
            </p>
        </div>
        <div class="form-group">
            <span class="field-label">Counselor</span>
            <p>{{ $appointment->counselor?->name ?? 'N/A' }}</p>
        </div>
        <div class="form-group">
            <span class="field-label">Status</span>
            <p><x-status-badge :status="$appointment->status" /></p>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <span class="field-label">Appointment Type</span>
            <p>{{ $appointment->appointmentType?->name ?? $appointment->appointment_type ?? 'N/A' }}</p>
        </div>
        <div class="form-group">
            <span class="field-label">Schedule</span>
            <p>
                {{ $appointment->appointment_date?->format('M d, Y') }}
                &middot;
                {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}
                &ndash;
                {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
            </p>
        </div>
    </div>

    @if ($appointment->reason)
        <div class="form-group">
            <span class="field-label">Student's Reason</span>
            <p>{{ $appointment->reason }}</p>
        </div>
    @endif

    @if ($appointment->counselor_notes)
        <div class="form-group">
            <span class="field-label">Counselor Notes</span>
            <p>{{ $appointment->counselor_notes }}</p>
        </div>
    @endif

    @if ($appointment->rejection_reason)
        <div class="form-group">
            <span class="field-label">Rejection Reason</span>
            <p>{{ $appointment->rejection_reason }}</p>
        </div>
    @endif
</x-card>
@endsection
