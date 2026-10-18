@extends('layouts.app')
@section('title', $scholarship->title . ' — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>{{ $scholarship->title }}</h2>
    <a href="{{ route('student.scholarships.index') }}" class="btn btn-outline-dark">← Back to Catalog</a>
</div>

{{-- Flash messages --}}
@if (session('info'))
    <div class="alert alert-success">{{ session('info') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <p><strong>Provider:</strong> {{ $scholarship->provider }}</p>
    <p><strong>Type:</strong> {{ $scholarship->scholarship_type }}</p>
    <p><strong>Application Period:</strong> {{ $scholarship->application_start_date->format('M d, Y') }} — {{ $scholarship->application_deadline->format('M d, Y') }}</p>
    <p><strong>Availability Status:</strong>
        @if ($scholarship->isOpenForApplication())
            <span class="badge badge-scholarship-open">Open</span>
        @else
            <span class="badge badge-scholarship-closed">Closed</span>
        @endif
    </p>

    <h3 style="margin-top:1.5rem;">Description</h3>
    <p style="white-space: pre-line;">{{ $scholarship->description }}</p>

    <h3>General Eligibility Criteria</h3>
    <ul>
        @if ($scholarship->minimum_gwa)
            <li>Academic Standings: GWA of {{ $scholarship->minimum_gwa }} or better (lower value)</li>
        @endif
        @if ($scholarship->maximum_family_income)
            <li>Financial Standing: Annual Family Income of ₱{{ number_format($scholarship->maximum_family_income, 2) }} or lower</li>
        @endif
        @if ($scholarship->eligible_programs)
            <li>Eligible Programs: {{ implode(', ', $scholarship->eligible_programs) }}</li>
        @endif
        @if ($scholarship->eligible_year_levels)
            <li>Eligible Year Levels: {{ implode(', ', $scholarship->eligible_year_levels) }}</li>
        @endif
        @if ($scholarship->required_enrollment_status)
            <li>Required Enrollment Status: {{ ucfirst($scholarship->required_enrollment_status) }}</li>
        @endif
        @if (!$scholarship->minimum_gwa && !$scholarship->maximum_family_income && !$scholarship->eligible_programs && !$scholarship->eligible_year_levels && !$scholarship->required_enrollment_status)
            <li>Open to all active undergraduate students.</li>
        @endif
    </ul>

    <h3>Required Document Checklist</h3>
    <ul>
        @forelse ($scholarship->requirements as $req)
            <li>
                {{ $req->requirement_name }} 
                @if ($req->is_required)
                    <strong>(Required)</strong>
                @else
                    <em>(Optional)</em>
                @endif
                @if ($req->description)
                    <br><small class="muted" style="margin-left: 1rem;">{{ $req->description }}</small>
                @endif
            </li>
        @empty
            <li>No document upload requirements are configured for this program.</li>
        @endforelse
    </ul>
</div>

{{-- Action Row block with unified layout buttons --}}
<div class="card">
    <div class="button-row">
        <a href="{{ route('student.scholarships.eligibility', $scholarship) }}" class="btn btn-outline-dark">
            Check My Eligibility
        </a>

        @if ($application)
            <a href="{{ route('student.applications.show', $application) }}" class="btn btn-primary">
                View My Application
            </a>
        @elseif ($scholarship->isOpenForApplication())
            <form method="POST" action="{{ route('student.applications.store', $scholarship) }}"
                  onsubmit="return confirm('Start a new application for this scholarship?')" class="inline-form">
                @csrf
                <button type="submit" class="btn btn-primary">Start Application</button>
            </form>
        @endif
    </div>
</div>
@endsection