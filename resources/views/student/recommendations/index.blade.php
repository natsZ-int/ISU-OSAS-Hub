@extends('layouts.app')
@section('title', 'Recommended Scholarships — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Recommended Scholarships</h2>
    <a href="{{ route('student.scholarships.index') }}" class="btn btn-outline-dark">Browse All Scholarships</a>
</div>

<div class="disclaimer card">
    <strong>Disclaimer:</strong> Match scores are based on your current profile information and the scholarship criteria configured in the system. A high score is not a guarantee of scholarship approval. Final decisions depend on complete requirements, document verification, and administrator review.
</div>

@if ($eligible->count() > 0)
    @foreach ($eligible as $rec)
    <div class="card recommendation-card">
        <div class="rec-header">
            <div>
                <h3>#{{ $rec->rank }} — {{ $rec->scholarshipTitle }}</h3>
                <p class="muted">{{ $rec->provider }} · {{ $rec->scholarshipType }}</p>
            </div>
            <div class="rec-score">
                <span class="score-value">{{ $rec->matchScore !== null ? number_format($rec->matchScore, 2) . '%' : 'N/A' }}</span>
                <span class="score-label">Match Score</span>
            </div>
        </div>

        <p><strong>Deadline:</strong> {{ $rec->deadline }}</p>
        <p><strong>Eligibility:</strong> <span class="badge badge-active">{{ $rec->eligibilityLabel }}</span></p>

        @if ($rec->existingApplicationStatus)
            <p><strong>Your Application:</strong> <span class="badge badge-status-{{ $rec->existingApplicationStatus }}">{{ ucfirst(str_replace('_', ' ', $rec->existingApplicationStatus)) }}</span></p>
        @endif

        <div class="rec-factors">
            @foreach ($rec->factors as $f)
            <div class="factor-chip factor-{{ $f->status }}">
                <span class="factor-icon">
                    @if ($f->status === 'met') ✓
                    @elseif ($f->status === 'not_met') ✗
                    @elseif ($f->status === 'partial') ◐
                    @else — @endif
                </span>
                <span>{{ $f->label }}: <strong>{{ $f->isApplicable && $f->normalizedScore !== null ? round($f->normalizedScore * 100) . '%' : 'N/A' }}</strong></span>
            </div>
            @endforeach
        </div>

        <details class="rec-reasons">
            <summary>View Reasons</summary>
            <ul>@foreach ($rec->reasons as $reason)<li>{{ $reason }}</li>@endforeach</ul>
        </details>

        <div class="button-row" style="margin-top:.75rem;">
            <a href="{{ route('student.scholarships.show', $rec->scholarshipId) }}" class="btn btn-outline-dark btn-sm">View Details</a>
            @if ($rec->hasExistingActiveApplication())
                <a href="{{ route('student.applications.show', $rec->existingApplicationId) }}" class="btn btn-primary btn-sm">View Application</a>
            @elseif (! $rec->existingApplicationStatus)
                <form method="POST" action="{{ route('student.applications.store', $rec->scholarshipId) }}" class="inline-form" onsubmit="return confirm('Start application?')">
                    @csrf
                    <button class="btn btn-primary btn-sm">Apply</button>
                </form>
            @endif
        </div>
    </div>
    @endforeach
@else
    <div class="card"><p class="empty-state">No matching scholarships found based on your current profile.</p></div>
@endif

@if ($ineligible->count() > 0)
<h3 style="margin-top:2rem;">Other Available Scholarships (Not Currently Eligible)</h3>
@foreach ($ineligible as $rec)
<div class="card recommendation-card rec-ineligible">
    <h3>{{ $rec->scholarshipTitle }}</h3>
    <p class="muted">{{ $rec->provider }} · Deadline: {{ $rec->deadline }}</p>
    <span class="badge badge-inactive">{{ $rec->eligibilityLabel }}</span>
    <details class="rec-reasons">
        <summary>View Reasons</summary>
        <ul>@foreach ($rec->reasons as $r)<li>{{ $r }}</li>@endforeach</ul>
    </details>
</div>
@endforeach
@endif
@endsection