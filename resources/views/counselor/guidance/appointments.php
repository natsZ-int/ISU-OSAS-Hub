@extends('layouts.app')
@section('title', 'My Appointments — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>My Appointments</h2>
    <a href="{{ route('counselor.guidance.availability') }}" class="btn btn-outline-dark">My Availability</a>
</div>

{{-- Filter by Status --}}
<div class="card filter-card">
    <form method="GET" action="{{ route('counselor.guidance.appointments') }}" class="filter-form">
        <div class="filter-row">
            <div class="form-group">
                <label for="status">Filter by Status</label>
                <select id="status" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="requested" @selected(request('status') === 'requested')>Requested</option>
                    <option value="confirmed" @selected(request('status') === 'confirmed')>Confirmed</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </select>
            </div>
            @if(request('status'))
                <div class="form-group" style="align-self: flex-end;">
                    <a href="{{ route('counselor.guidance.appointments') }}" class="btn btn-outline-dark btn-sm">Clear Filter</a>
                </div>
            @endif
        </div>
    </form>
</div>

@if ($appointments->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Student</th>
                <th>Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($appointments as $a)
            <tr>
                <td>{{ $a->appointment_date->format('M d, Y') }}</td>
                <td>
                    {{ \Carbon\Carbon::parse($a->start_time)->format('g:i A') }} – 
                    {{ \Carbon\Carbon::parse($a->end_time)->format('g:i A') }}
                </td>
                <td>
                    {{ $a->studentProfile ? $a->studentProfile->first_name . ' ' . $a->studentProfile->last_name : 'N/A' }}
                </td>
                <td>{{ $a->appointment_type }}</td>
                <td>
                    <span class="badge badge-{{ $a->status === 'confirmed' ? 'active' : ($a->status === 'requested' ? 'admin' : 'inactive') }}">
                        {{ $a->getDisplayStatus() }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('counselor.guidance.appointments.show', $a) }}" class="btn btn-outline-dark btn-sm">
                        Manage
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pagination-wrapper">{{ $appointments->links() }}</div>
@else
    <div class="card">
        <p class="empty-state">No appointments found.</p>
    </div>
@endif
@endsection