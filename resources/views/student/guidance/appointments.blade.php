@extends('layouts.app')
@section('title', 'My Appointments - ISU OSAS Hub')

@section('content')
<x-page-header title="My Appointments">
    <x-slot:actions>
        <a href="{{ route('student.guidance.counselors') }}" class="btn btn-primary">Book New Appointment</a>
    </x-slot:actions>
</x-page-header>

@if ($errors->any())
    <x-alert type="error">{{ $errors->first() }}</x-alert>
@endif

@if ($appointments->count() > 0)
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Counselor</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($appointments as $a)
                <tr>
                    <td>{{ $a->appointment_date->format('M d, Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($a->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($a->end_time)->format('g:i A') }}</td>
                    <td>{{ $a->counselor->name }}</td>
                    <td>{{ $a->appointment_type }}</td>
                    <td><x-status-badge :status="$a->status" /></td>
                    <td>
                        <a href="{{ route('student.guidance.appointments.show', $a) }}" class="btn btn-outline-dark btn-sm">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-pagination-wrapper :paginator="$appointments" />
@else
    <x-card>
        <x-empty-state
            title="No appointments yet."
            message="You have not booked any guidance appointments."
            :action-url="route('student.guidance.counselors')"
            action-label="Book an Appointment" />
    </x-card>
@endif
@endsection