@extends('layouts.app')
@section('title', 'Appointment Details — ISU OSAS Hub')
@section('content')
<div class="page-header">
    <h2>Appointment Details</h2>
    <a href="{{ route('student.guidance.appointments') }}" class="btn btn-outline-dark">Back to Appointments</a>
</div>

<div class="card" style="max-width: 600px;">
    <p><strong>Counselor:</strong> {{ $appointment->counselor->name }}</p>
    <p><strong>Type:</strong> {{ $appointment->appointment_type }}</p>
    <p><strong>Date:</strong> {{ $appointment->appointment_date->format('l, F j, Y') }}</p>
    <p><strong>Time:</strong> {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}</p>
    <p><strong>Status:</strong> <span class="badge badge-{{ $appointment->status === 'confirmed' ? 'active' : 'admin' }}">{{ $appointment->getDisplayStatus() }}</span></p>
    @if ($appointment->reason)<p><strong>Reason:</strong> {{ $appointment->reason }}</p>@endif
    @if ($appointment->rejection_reason)<p><strong>Rejection Reason:</strong> {{ $appointment->rejection_reason }}</p>@endif

    @if (in_array($appointment->status, ['requested', 'confirmed']))
        <form method="POST" action="{{ route('student.guidance.appointments.cancel', $appointment) }}"
              onsubmit="return confirm('Are you sure you want to cancel this appointment?')">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-outline-dark" style="margin-top: 1rem;">Cancel Appointment</button>
        </form>
    @endif
</div>
@endsection