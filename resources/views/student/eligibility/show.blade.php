@extends('layouts.app')
@section('title', 'Eligibility Check — ' . $scholarship->title)

@section('content')
<div class="page-header">
    <h2>Eligibility Check</h2>
    <a href="{{ route('student.scholarships.show', $scholarship) }}" class="btn btn-outline-dark">← Back to Scholarship</a>
</div>

<div class="card">
    <h3>{{ $scholarship->title }}</h3>
    <p class="muted">{{ $scholarship->provider }} · {{ $scholarship->scholarship_type }}</p>
</div>

<div class="card eligibility-summary eligibility-{{ $evaluation->overall->value }}">
    <div class="eligibility-headline">
        @if ($evaluation->isEligible())
            <span class="eligibility-icon">✓</span>
            <div>
                <h2>You are Eligible</h2>
                <p class="muted">Based on the currently configured scholarship criteria and your student profile.</p>
            </div>
        @else
            <span class="eligibility-icon eligibility-icon-fail">✗</span>
            <div>
                <h2>Not Eligible</h2>
                <p class="muted">{{ $evaluation->reasonSummary }}</p>
            </div>
        @endif
    </div>
    <div class="disclaimer">
        <strong>Disclaimer:</strong> This result reflects only the currently configured scholarship criteria and the data
        in your student profile. Final approval depends on the complete application review process, including document
        verification by an administrator.
    </div>
</div>

<div class="card">
    <h3>Criterion-by-Criterion Results</h3>
    <div class="criteria-list">
        @foreach ($evaluation->criteria as $c)
        <div class="criterion-row criterion-{{ $c->status->value }}">
            <div class="criterion-status" aria-label="{{ $c->status->label() }}">
                <span class="criterion-icon">{{ $c->status->icon() }}</span>
                <span class="criterion-label">{{ $c->status->label() }}</span>
            </div>
            <div class="criterion-body">
                <h4>{{ $c->criterionLabel }} @if ($c->isMandatory)<small class="mandatory-tag">Mandatory</small>@endif</h4>
                <p>{{ $c->explanation }}</p>
                @if ($c->studentValueDisplay || $c->requiredValueDisplay)
                <div class="criterion-values">
                    @if ($c->studentValueDisplay)
                        <span class="value-chip"><strong>Your value:</strong> {{ $c->studentValueDisplay }}</span>
                    @endif
                    @if ($c->requiredValueDisplay)
                        <span class="value-chip"><strong>Required:</strong> {{ $c->requiredValueDisplay }}</span>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Application Readiness (documents) — informational only --}}
@if ($application && !empty($evaluation->applicationReadiness))
<div class="card">
    <h3>Application Document Readiness</h3>
    <p class="muted">These are application requirements — separate from profile eligibility.</p>

    @if ($evaluation->applicationReadiness['has_required_documents'])
        <div class="alert alert-success">All required documents have been uploaded.</div>
    @else
        <div class="alert alert-error">
            <strong>Missing required documents:</strong>
            <ul>
                @foreach ($evaluation->applicationReadiness['missing'] as $m)
                    <li>{{ $m }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!empty($evaluation->applicationReadiness['pending']))
        <div class="alert alert-error">
            <strong>Pending verification:</strong>
            <ul>@foreach ($evaluation->applicationReadiness['pending'] as $p)<li>{{ $p }}</li>@endforeach</ul>
        </div>
    @endif

    @if (!empty($evaluation->applicationReadiness['rejected']))
        <div class="alert alert-error">
            <strong>Rejected documents (need re-upload):</strong>
            <ul>@foreach ($evaluation->applicationReadiness['rejected'] as $r)<li>{{ $r }}</li>@endforeach</ul>
        </div>
    @endif
</div>
@endif

<div class="card">
    <h3>Next Steps</h3>
    <div class="button-row">
        <a href="{{ route('student.scholarships.show', $scholarship) }}" class="btn btn-outline-dark">Back to Scholarship Details</a>
        @if ($evaluation->isEligible() && $scholarship->isOpenForApplication() && ! $application)
            <form method="POST" action="{{ route('student.applications.store', $scholarship) }}"
                  onsubmit="return confirm('Start a new application for this scholarship?')">
                @csrf
                <button type="submit" class="btn btn-primary">Start Application</button>
            </form>
        @elseif ($application)
            <a href="{{ route('student.applications.show', $application) }}" class="btn btn-primary">View My Application</a>
        @endif
    </div>
</div>

<p class="muted" style="text-align:center;font-size:.8rem;margin-top:1rem;">
    Evaluated on {{ $evaluation->evaluatedAt->format('M d, Y H:i') }}
</p>
@endsection