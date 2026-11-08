@extends('layouts.app')
@section('title', 'Available Counselors — ISU OSAS Hub')
@section('content')
<div class="page-header">
    <h2>Available Counselors</h2>
    <a href="{{ route('student.guidance.appointments') }}" class="btn btn-outline-dark">My Appointments</a>
</div>

@if ($counselors->count() > 0)
    <div class="scholarship-grid">
        @foreach ($counselors as $c)
        <div class="card scholarship-card">
            <h3>{{ $c->name }}</h3>
            <p class="muted">Counselor</p>
            <div class="eligibility-tags">
                @foreach ($c->availabilities->where('is_available', true)->groupBy('day_of_week') as $day => $avails)
                    <span class="tag">{{ \App\Models\CounselorAvailability::DAYS[$day] }}</span>
                @endforeach
            </div>
            <div class="card-actions">
                <a href="{{ route('student.guidance.slots', $c) }}" class="btn btn-primary btn-sm">View Available Slots</a>
            </div>
        </div>
        @endforeach
    </div>
@else
    <div class="card">
        <p class="empty-state">
            @if (request('search'))
                No counselors match "{{ request('search') }}".
            @else
                No counselors are currently available.
            @endif
        </p>
    </div>
@endif
@endsection