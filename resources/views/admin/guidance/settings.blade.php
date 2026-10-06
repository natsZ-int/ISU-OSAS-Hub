@extends('layouts.app')
@section('title', 'Scheduling Settings — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Guidance Scheduling Settings</h2>
    <a href="{{ route('admin.guidance.types') }}" class="btn btn-outline-dark">Appointment Types</a>
</div>

<div class="card" style="max-width: 600px;">
    <form method="POST" action="{{ route('admin.guidance.settings.update') }}">
        @csrf @method('PATCH')

        <div class="form-group">
            <label for="office_start_time">Office Start Time</label>
            <input type="time" id="office_start_time" name="office_start_time" 
                   value="{{ \Carbon\Carbon::parse($settings->office_start_time)->format('H:i') }}" required>
        </div>

        <div class="form-group">
            <label for="office_end_time">Office End Time</label>
            <input type="time" id="office_end_time" name="office_end_time" 
                   value="{{ \Carbon\Carbon::parse($settings->office_end_time)->format('H:i') }}" required>
        </div>

        <div class="form-group">
            <label for="default_appointment_duration_minutes">Default Duration (Minutes)</label>
            <input type="number" id="default_appointment_duration_minutes" name="default_appointment_duration_minutes" 
                   value="{{ $settings->default_appointment_duration_minutes }}" min="5" max="480" required>
        </div>

        <div class="form-group">
            <label for="minimum_booking_notice_minutes">Minimum Booking Notice (Minutes)</label>
            <input type="number" id="minimum_booking_notice_minutes" name="minimum_booking_notice_minutes" 
                   value="{{ $settings->minimum_booking_notice_minutes }}" min="0" required>
            <small class="muted">How many minutes in advance a student must book.</small>
        </div>

        <div class="form-group">
            <label for="student_cancellation_notice_minutes">Student Cancellation Notice (Minutes)</label>
            <input type="number" id="student_cancellation_notice_minutes" name="student_cancellation_notice_minutes" 
                   value="{{ $settings->student_cancellation_notice_minutes }}" min="0" required>
            <small class="muted">Minimum notice required before a student can cancel.</small>
        </div>

        <div class="form-group">
            <label for="maximum_future_booking_days">Maximum Future Booking Period (Days)</label>
            <input type="number" id="maximum_future_booking_days" name="maximum_future_booking_days" 
                   value="{{ $settings->maximum_future_booking_days }}" min="1" max="365" required>
            <small class="muted">How far in advance appointments can be booked.</small>
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>
@endsection