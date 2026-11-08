@extends('layouts.app')
@section('title', 'Scholarship Matching Results — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Scholarship Matching Results</h2>
    <div class="button-row">
        <a href="{{ route('student.scholarships.recommendations') }}" class="btn btn-outline-dark">View Recommendations</a>
        <a href="{{ route('student.scholarships.index') }}" class="btn btn-outline-dark">Browse Scholarships</a>
    </div>
</div>

<p class="muted" style="font-size:.85rem;">
    These results come directly from the matching engine. Match scores reflect the currently configured
    weighting factors; eligibility still depends on the mandatory scholarship criteria.
</p>

<div class="card">
    <h3>Eligible Scholarships ({{ $eligible->count() }})</h3>
    @forelse ($eligible as $match)
        <div style="border-bottom:1px solid var(--border);padding:.85rem 0;">
            <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                <div>
                    <strong>{{ $match->scholarshipTitle }}</strong>
                    <div class="muted" style="font-size:.8rem;">
                        {{ $match->provider }} &middot; {{ $match->scholarshipType }} &middot; Deadline: {{ $match->deadline }}
                    </div>
                </div>
                <div style="text-align:right;">
                    <span class="badge badge-success">{{ $match->matchScore !== null ? number_format($match->matchScore, 1).'% Match' : 'Match N/A' }}</span>
                    @if ($match->rank)
                        <div class="muted" style="font-size:.75rem;">Rank #{{ $match->rank }}</div>
                    @endif
                </div>
            </div>

            @if (! empty($match->reasons))
                <ul class="muted" style="margin:.5rem 0 0 1.25rem;font-size:.8rem;line-height:1.5;">
                    @foreach (array_slice($match->reasons, 0, 4) as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="button-row" style="margin-top:.5rem;">
                <a href="{{ route('student.scholarships.show', $match->scholarshipId) }}" class="btn btn-sm btn-outline-dark">View Details</a>
                <a href="{{ route('student.scholarships.eligibility', $match->scholarshipId) }}" class="btn btn-sm btn-outline-dark">Check Eligibility</a>
            </div>
        </div>
    @empty
        <p class="empty-state">No scholarships currently match your profile.</p>
    @endforelse
</div>

<div class="card">
    <h3>Not Currently Eligible ({{ $ineligible->count() }})</h3>
    @forelse ($ineligible as $match)
        <div style="border-bottom:1px solid var(--border);padding:.85rem 0;">
            <strong>{{ $match->scholarshipTitle }}</strong>
            <div class="muted" style="font-size:.8rem;">
                {{ $match->provider }} &middot; Deadline: {{ $match->deadline }}
            </div>
            @if (! empty($match->reasons))
                <ul class="muted" style="margin:.5rem 0 0 1.25rem;font-size:.8rem;line-height:1.5;">
                    @foreach (array_slice($match->reasons, 0, 4) as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @empty
        <p class="empty-state">All available scholarships are currently eligible based on your profile.</p>
    @endforelse
</div>
@endsection
