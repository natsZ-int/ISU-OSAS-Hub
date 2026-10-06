@extends('layouts.app')
@section('title', 'All Appointments — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Guidance Appointments Oversight</h2>
    <div>
        <a href="{{ route('admin.guidance.types') }}" class="btn btn-outline-dark">Appointment Types</a>
        <a href="{{ route('admin.guidance.settings') }}" class="btn btn-outline-dark">Settings</a>
    </div>
</div>

{{-- Filters --}}
<div class="card filter-card">
    <form method="GET" action="{{ route('admin.guidance.appointments') }}" class="filter-form">
        <div class="filter-row">
            <div class="form-group">
                <label for="counselor_id">Counselor</label>
                <select id="counselor_id" name="counselor_id">
                    <option value="">All Counselors</option>
                    @foreach ($counselors as $c)
                        <option value="{{ $c->id }}" @selected((string) request('counselor_id') === (string) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="requested" @selected(request('status') === 'requested')>Requested</option>
                    <option value="confirmed" @selected(request('status') === 'confirmed')>Confirmed</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </select>
            </div>
            <div class="form-group" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.guidance.appointments') }}" class="btn btn-outline-dark btn-sm">Reset</a>
            </div>
        </div>
    </form>
</div>

@if ($appointments->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Counselor</th>
                <th>Student</th>
                <th>Type</th>
                <th>Status</th>
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
                <td>{{ $a->counselor->name }}</td>
                <td>
                    {{ $a->studentProfile ? $a->studentProfile->first_name . ' ' . $a->studentProfile->last_name : 'N/A' }}
                </td>
                <td>{{ $a->appointment_type }}</td>
                <td>
                    <span class="badge badge-{{ $a->status === 'confirmed' ? 'active' : ($a->status === 'requested' ? 'admin' : 'inactive') }}">
                        {{ $a->getDisplayStatus() }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pagination-wrapper">{{ $appointments->links() }}</div>
@else
    <div class="card"><p class="empty-state">No appointments found.</p></div>
@endif
@endsection