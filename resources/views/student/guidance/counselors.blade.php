@extends('layouts.app')
@section('title', 'Available Counselors — ISU OSAS Hub')
@section('content')
<div class="page-header">
    <h2>Available Counselors</h2>
    <a href="{{ route('student.guidance.appointments') }}" class="btn btn-outline-dark">My Appointments</a>
</div>

<div class="card filter-card">
    <form method="GET" action="{{ route('student.guidance.counselors') }}" class="filter-form" role="search">
        <div class="filter-row">
            <div class="form-group" style="flex:2;">
                <label for="counselor_search">Search Counselors</label>
                <input type="search" id="counselor_search" name="search"
                       value="{{ request('search') }}"
                       placeholder="Counselor name or email..."
                       maxlength="200"
                       data-live-search
                       data-live-search-endpoint="{{ route('student.quick-search', ['module' => 'counselors']) }}">
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                @if (request('search'))
                    <a href="{{ route('student.guidance.counselors') }}" class="btn btn-outline-dark btn-sm">Clear</a>
                @endif
            </div>
        </div>
    </form>
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