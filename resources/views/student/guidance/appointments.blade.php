@extends('layouts.app')
@section('title', 'My Appointments — ISU OSAS Hub')
@section('content')
<div class="page-header">
    <h2>My Appointments</h2>
    <a href="{{ route('student.guidance.counselors') }}" class="btn btn-primary">Book New Appointment</a>
</div>

@if ($appointments->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th>Date</th><th>Time</th><th>Counselor</th><th>Type</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($appointments as $a)
            <tr>
                <td>{{ $a->appointment_date->format('M d, Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($a->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($a->end_time)->format('g:i A') }}</td>
                <td>{{ $a->counselor->name }}</td>
                <td>{{ $a->appointment_type }}</td>
                <td><span class="badge badge-{{ $a->status === 'confirmed' ? 'active' : ($a->status === 'requested' ? 'admin' : 'inactive') }}">{{ $a->getDisplayStatus() }}</span></td>
                <td>
                    <a href="{{ route('student.guidance.appointments.show', $a) }}" class="btn btn-outline-dark btn-sm">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pagination-wrapper">{{ $appointments->links() }}</div>
@else
    <div class="card"><p class="empty-state">You have no appointments yet.</p></div>
@endif
@endsection