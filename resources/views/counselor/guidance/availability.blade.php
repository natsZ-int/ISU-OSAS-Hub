@extends('layouts.app')
@section('title', 'My Availability - ISU OSAS Hub')

@section('content')
<x-page-header title="My Availability">
    <x-slot:actions>
        <a href="{{ route('counselor.guidance.appointments') }}" class="btn btn-outline-dark">My Appointments</a>
    </x-slot:actions>
</x-page-header>

<p class="muted">
    Office Hours: {{ \Carbon\Carbon::parse($settings->office_start_time)->format('g:i A') }}
    - {{ \Carbon\Carbon::parse($settings->office_end_time)->format('g:i A') }}
</p>

@if (session('success'))
    <x-alert type="success">{{ session('success') }}</x-alert>
@endif

@if ($errors->any())
    <x-alert type="error">
        <ul style="margin:0; padding-left:1.1rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif

<x-card title="Add Availability" style="max-width: 500px; margin-bottom: 2rem;">
    <form method="POST" action="{{ route('counselor.guidance.availability.store') }}">
        @csrf
        <div class="form-group">
            <label for="day_of_week">Day of Week <span aria-hidden="true">*</span></label>
            <select id="day_of_week" name="day_of_week" required>
                @foreach (\App\Models\CounselorAvailability::DAYS as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="start_time">Start Time <span aria-hidden="true">*</span></label>
                <input type="time" id="start_time" name="start_time" required>
            </div>
            <div class="form-group">
                <label for="end_time">End Time <span aria-hidden="true">*</span></label>
                <input type="time" id="end_time" name="end_time" required>
            </div>
        </div>
        <p class="muted" style="font-size:.8rem;">
            Availability must fall within office hours and must not overlap an existing window.
        </p>
        <button type="submit" class="btn btn-primary">Add Availability</button>
    </form>
</x-card>

@if ($availabilities->count() > 0)
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($availabilities as $a)
                <tr>
                    <td>{{ $a->getDayName() }}</td>
                    <td>{{ \Carbon\Carbon::parse($a->start_time)->format('g:i A') }}</td>
                    <td>{{ \Carbon\Carbon::parse($a->end_time)->format('g:i A') }}</td>
                    <td><x-status-badge :status="$a->is_available ? 'active' : 'inactive'" /></td>
                    <td>
                        @if ($a->is_available)
                            <x-confirm-button
                                :action="route('counselor.guidance.availability.deactivate', $a)"
                                method="PATCH"
                                :confirm="'Deactivate this availability window (' . $a->getDayName() . ', ' . \Carbon\Carbon::parse($a->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($a->end_time)->format('g:i A') . ')?'"
                                label="Deactivate"
                                variant="outline-dark"
                                size="sm" />
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <x-card>
        <x-empty-state
            title="No availability set yet."
            message="Add a weekly time window above so students can book appointments with you." />
    </x-card>
@endif
@endsection