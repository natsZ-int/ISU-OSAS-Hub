@extends('layouts.app')
@section('title', 'Manage Appointment — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Appointment Details</h2>
    <a href="{{ route('counselor.guidance.appointments') }}" class="btn btn-outline-dark">Back to Appointments</a>
</div>

@if ($errors->any())
    <x-alert type="error">{{ $errors->first() }}</x-alert>
@endif

<div class="card" style="max-width: 650px;">
    <h3>Student Information</h3>
    <p><strong>Name:</strong> {{ $appointment->studentProfile ? $appointment->studentProfile->first_name . ' ' . $appointment->studentProfile->last_name : 'N/A' }}</p>
    <p><strong>Program:</strong> {{ $appointment->studentProfile?->program ?? 'N/A' }} (Year {{ $appointment->studentProfile?->year_level ?? 'N/A' }})</p>

    <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #e5e7eb;">

    <h3>Schedule Details</h3>
    <p><strong>Type:</strong> {{ $appointment->appointment_type }}</p>
    <p><strong>Date:</strong> {{ $appointment->appointment_date->format('l, F j, Y') }}</p>
    <p><strong>Time:</strong> {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}</p>
    <p><strong>Status:</strong> 
        <span class="badge badge-{{ $appointment->status === 'confirmed' ? 'active' : ($appointment->status === 'requested' ? 'admin' : 'inactive') }}">
            {{ $appointment->getDisplayStatus() }}
        </span>
    </p>

    @if ($appointment->reason)
        <p><strong>Student Reason:</strong> {{ $appointment->reason }}</p>
    @endif

    @if ($appointment->rejection_reason)
        <p><strong>Rejection Remarks:</strong> {{ $appointment->rejection_reason }}</p>
    @endif

    {{-- Allowed Transitions --}}
    @if (!empty($allowedTransitions))
        <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #e5e7eb;">
        <h3>Actions</h3>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1rem;">
            
            {{-- Confirm Action --}}
            @if (in_array('confirmed', $allowedTransitions))
                <form method="POST" action="{{ route('counselor.guidance.appointments.confirm', $appointment) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary btn-sm">Confirm Appointment</button>
                </form>
            @endif

            {{-- Complete Action --}}
            @if (in_array('completed', $allowedTransitions))
                <form method="POST" action="{{ route('counselor.guidance.appointments.complete', $appointment) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-success btn-sm">Mark as Completed</button>
                </form>
            @endif

            {{-- Reject Form --}}
            @if (in_array('rejected', $allowedTransitions))
                <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('reject-form').style.display='block'; this.style.display='none';">
                    Reject Appointment
                </button>
            @endif
        </div>

        {{-- Reject Reason Form --}}
        @if (in_array('rejected', $allowedTransitions))
            <form id="reject-form" method="POST" action="{{ route('counselor.guidance.appointments.reject', $appointment) }}" style="display: none; margin-top: 1.5rem;">
                @csrf @method('PATCH')
                <div class="form-group">
                    <label for="rejection_reason"><strong>Rejection Reason (Required):</strong></label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="3" required minlength="5" placeholder="Explain why this appointment cannot be accepted..."></textarea>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-danger btn-sm">Submit Rejection</button>
                    <button type="button" class="btn btn-outline-dark btn-sm" onclick="document.getElementById('reject-form').style.display='none';">Cancel</button>
                </div>
            </form>
        @endif
    @endif
</div>
@endsection