@extends('layouts.app')
@section('title', 'Available Slots — ISU OSAS Hub')
@section('content')
<div class="page-header">
    <h2>Available Slots — {{ $counselor->name }}</h2>
    <a href="{{ route('student.guidance.counselors') }}" class="btn btn-outline-dark">Back to Counselors</a>
</div>

@if ($errors->any())
    <x-alert type="error">{{ $errors->first() }}</x-alert>
@endif

<div class="card filter-card">
    <form method="GET" action="{{ route('student.guidance.slots', $counselor) }}" class="filter-form">
        <div class="filter-row">
            <div class="form-group">
                <label for="date">Select Date</label>
                <input type="date" id="date" name="date" value="{{ $date->toDateString() }}"
                       min="{{ now()->toDateString() }}" max="{{ now()->addDays($maxBookingDays)->toDateString() }}">
            </div>
            <div class="form-group">
                <label for="type">Appointment Type</label>
                <select id="type" name="type">
                    <option value="">Default Duration</option>
                    @foreach ($types as $t)
                        <option value="{{ $t->id }}" @selected($appointmentType?->id === $t->id)>
                            {{ $t->name }} ({{ $t->duration_minutes ?? 'Default' }} min)
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary">Check Availability</button>
            </div>
        </div>
    </form>
</div>

<h3>{{ $date->format('l, F j, Y') }}</h3>

@if ($types->isEmpty())
    <div class="card">
        <p class="empty-state">No appointment types are currently available. Please contact the guidance office.</p>
    </div>
@elseif ($slots->count() > 0)
    <div class="scholarship-grid">
        @foreach ($slots as $slot)
        <div class="card scholarship-card" style="text-align: center;">
            <h3>{{ $slot['display'] }}</h3>
            <form method="POST" action="{{ route('student.guidance.appointments.store') }}">
                @csrf
                <input type="hidden" name="counselor_id" value="{{ $counselor->id }}">
                <input type="hidden" name="appointment_type_id" value="{{ $appointmentType?->id ?? $types->first()?->id }}">
                <input type="hidden" name="appointment_date" value="{{ $date->toDateString() }}">
                <input type="hidden" name="start_time" value="{{ \Carbon\Carbon::parse($slot['start'])->format('H:i') }}">
                <div class="form-group">
                    <label for="reason">Reason (optional)</label>
                    <textarea name="reason" rows="2" maxlength="500" placeholder="Briefly describe your concern..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Book This Slot</button>
            </form>
        </div>
        @endforeach
    </div>
@else
    <div class="card"><p class="empty-state">No available slots for this date. Try another date or counselor.</p></div>
@endif
@endsection