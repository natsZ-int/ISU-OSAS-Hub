@extends('layouts.app')
@section('title', 'My Availability — ISU OSAS Hub')
@section('content')
<div class="page-header">
    <h2>My Availability</h2>
    <a href="{{ route('counselor.guidance.appointments') }}" class="btn btn-outline-dark">My Appointments</a>
</div>
<p class="muted">Office Hours: {{ \Carbon\Carbon::parse($settings->office_start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($settings->office_end_time)->format('g:i A') }}</p>

<div class="card" style="max-width: 500px; margin-bottom: 2rem;">
    <h3>Add Availability</h3>
    <form method="POST" action="{{ route('counselor.guidance.availability.store') }}">
        @csrf
        <div class="form-group">
            <label>Day of Week</label>
            <select name="day_of_week" required>
                @foreach (\App\Models\CounselorAvailability::DAYS as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Start Time</label>
            <input type="time" name="start_time" required>
        </div>
        <div class="form-group">
            <label>End Time</label>
            <input type="time" name="end_time" required>
        </div>
        <button type="submit" class="btn btn-primary">Add</button>
    </form>
</div>

@if ($availabilities->count() > 0)
    <table class="table">
        <thead><tr><th>Day</th><th>Start</th><th>End</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            @foreach ($availabilities as $a)
            <tr>
                <td>{{ $a->getDayName() }}</td>
                <td>{{ \Carbon\Carbon::parse($a->start_time)->format('g:i A') }}</td>
                <td>{{ \Carbon\Carbon::parse($a->end_time)->format('g:i A') }}</td>
                <td><span class="badge badge-{{ $a->is_available ? 'active' : 'inactive' }}">{{ $a->is_available ? 'Active' : 'Inactive' }}</span></td>
                <td>
                    @if ($a->is_available)
                    <form method="POST" action="{{ route('counselor.guidance.availability.deactivate', $a) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-outline-dark btn-sm">Deactivate</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p class="empty-state">No availability set yet.</p>
@endif
@endsection