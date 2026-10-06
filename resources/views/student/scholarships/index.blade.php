@extends('layouts.app')
@section('title', 'Browse Scholarships — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Browse Scholarships</h2>
    <a href="{{ route('student.scholarships.recommendations') }}" class="btn btn-outline-dark">
        View Recommendations
    </a>
</div>

{{-- Search and Filter Form --}}
<div class="card filter-card">
    <form method="GET" action="{{ route('student.scholarships.index') }}" class="filter-form">

        {{-- Row 1 --}}
        <div class="filter-row">
            <div class="form-group" style="flex: 2;">
                <label for="search">Keyword Search</label>
                <input type="text" id="search" name="search"
                       value="{{ request('search') }}"
                       placeholder="Title, provider, type, program, description..."
                       maxlength="200">
            </div>
            <div class="form-group">
                <label for="type">Scholarship Type</label>
                <select id="type" name="type">
                    <option value="">All Types</option>
                    @foreach ($types as $t)
                        <option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="program">Program</label>
                <select id="program" name="program">
                    <option value="">All Programs</option>
                    @if ($profile && $profile->program)
                        <option value="{{ $profile->program }}"
                                @selected(request('program') === $profile->program)>
                            My Program ({{ $profile->program }})
                        </option>
                    @endif
                    @foreach ($programs as $p)
                        @if (!$profile || $p !== $profile->program)
                            <option value="{{ $p }}" @selected(request('program') === $p)>{{ $p }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Row 2 --}}
        <div class="filter-row">
            <div class="form-group">
                <label for="year_level">Year Level</label>
                <select id="year_level" name="year_level">
                    <option value="">All Year Levels</option>
                    @for ($i = 1; $i <= 6; $i++)
                        <option value="{{ $i }}"
                                @selected((string) request('year_level') === (string) $i)>
                            Year {{ $i }}{{ $profile && (int) $profile->year_level === $i ? ' (My Year)' : '' }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="form-group">
                <label for="deadline">Deadline</label>
                <select id="deadline" name="deadline">
                    <option value="">Any Deadline</option>
                    <option value="within_7_days"  @selected(request('deadline') === 'within_7_days')>Within 7 Days</option>
                    <option value="within_30_days" @selected(request('deadline') === 'within_30_days')>Within 30 Days</option>
                </select>
            </div>
            <div class="form-group">
                <label for="eligibility">Eligibility</label>
                <select id="eligibility" name="eligibility">
                    <option value="" @selected(!request('eligibility'))>All Scholarships</option>
                    <option value="eligible" @selected(request('eligibility') === 'eligible')>Eligible Only</option>
                    <option value="potentially_eligible" @selected(request('eligibility') === 'potentially_eligible')>Potentially Eligible</option>
                    <option value="not_eligible" @selected(request('eligibility') === 'not_eligible')>Not Eligible</option>
                </select>
            </div>
            <div class="form-group">
                <label for="sort">Sort By</label>
                <select id="sort" name="sort">
                    <option value="deadline"    @selected(request('sort', 'deadline') === 'deadline')>Deadline (Earliest)</option>
                    <option value="match_score" @selected(request('sort') === 'match_score')>Match Score (Highest)</option>
                    <option value="name"        @selected(request('sort') === 'name')>Name (A–Z)</option>
                    <option value="newest"      @selected(request('sort') === 'newest')>Newest</option>
                </select>
            </div>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ route('student.scholarships.index') }}" class="btn btn-outline-dark">Clear Filters</a>
        </div>
    </form>
</div>

{{-- Result Count and Active Filters --}}
<div class="results-summary">
    <span>
        <strong>{{ $scholarships->total() }}</strong>
        {{ $scholarships->total() === 1 ? 'scholarship' : 'scholarships' }} found
    </span>

    @php
        $activeFilters = array_filter([
            'search'      => request('search'),
            'type'        => request('type'),
            'program'     => request('program'),
            'year_level'  => request('year_level'),
            'deadline'    => request('deadline'),
            'eligibility' => request('eligibility'),
        ]);
    @endphp

    @if (!empty($activeFilters))
        <div class="active-filters">
            @if (request('search'))      <span class="filter-chip">Search: "{{ request('search') }}"</span> @endif
            @if (request('type'))        <span class="filter-chip">Type: {{ request('type') }}</span> @endif
            @if (request('program'))     <span class="filter-chip">Program: {{ request('program') }}</span> @endif
            @if (request('year_level'))  <span class="filter-chip">Year: {{ request('year_level') }}</span> @endif
            @if (request('deadline'))    <span class="filter-chip">Deadline: {{ str_replace('_', ' ', request('deadline')) }}</span> @endif
            @if (request('eligibility')) <span class="filter-chip">Eligibility: {{ str_replace('_', ' ', request('eligibility')) }}</span> @endif
        </div>
    @endif
</div>

{{-- Scholarship Results --}}
@if ($scholarships->count() > 0)
    <div class="scholarship-grid">
        @foreach ($scholarships as $s)
        <div class="card scholarship-card">
            <h3>{{ $s->title }}</h3>
            <p class="muted">{{ $s->provider }} · {{ $s->scholarship_type }}</p>

            {{-- Eligibility and Match Badges --}}
            @if (isset($s->match_result))
                <div class="scholarship-badges">
                    @if ($s->match_result->isEligible())
                        <span class="badge badge-active">Eligible</span>
                    @else
                        <span class="badge badge-inactive">Not Eligible</span>
                    @endif

                    @if ($s->match_score !== null)
                        <span class="badge badge-admin">Match: {{ number_format($s->match_score, 1) }}%</span>
                    @endif

                    @if ($s->match_result->existingApplicationStatus)
                        <span class="badge badge-info">
                            Applied: {{ ucfirst(str_replace('_', ' ', $s->match_result->existingApplicationStatus)) }}
                        </span>
                    @endif
                </div>
            @endif

            {{-- Quick Eligibility Tags --}}
            <div class="eligibility-tags">
                @if ($s->minimum_gwa)
                    <span class="tag">Min GWA: {{ $s->minimum_gwa }}</span>
                @endif
                @if (!empty($s->eligible_programs))
                    <span class="tag">Programs: {{ implode(', ', array_slice($s->eligible_programs, 0, 3)) }}{{ count($s->eligible_programs) > 3 ? '...' : '' }}</span>
                @endif
                @if (!empty($s->eligible_year_levels))
                    <span class="tag">Years: {{ implode(', ', $s->eligible_year_levels) }}</span>
                @endif
                @if ($s->maximum_family_income)
                    <span class="tag">Max Income: ₱{{ number_format($s->maximum_family_income, 0) }}</span>
                @endif
            </div>

            {{-- Deadline --}}
            <p class="deadline">
                <strong>Deadline:</strong> {{ $s->application_deadline->format('M d, Y') }}
                @if ($s->application_deadline->diffInDays(now()) <= 7)
                    <span class="badge badge-danger">Closing Soon</span>
                @endif
            </p>

            {{-- Brief Match Reasons --}}
            @if (isset($s->match_result) && !empty($s->match_result->reasons))
                <div class="match-reasons">
                    @foreach (array_slice($s->match_result->reasons, 0, 2) as $reason)
                        <small class="text-muted">• {{ $reason }}</small>
                    @endforeach
                    @if (count($s->match_result->reasons) > 2)
                        <small class="text-muted">
                            <a href="{{ route('student.scholarships.eligibility', $s) }}">+{{ count($s->match_result->reasons) - 2 }} more</a>
                        </small>
                    @endif
                </div>
            @endif

            {{-- Action Buttons --}}
            <div class="card-actions">
                <a href="{{ route('student.scholarships.show', $s) }}" class="btn btn-primary btn-sm">View Details</a>
                <a href="{{ route('student.scholarships.eligibility', $s) }}" class="btn btn-outline-dark btn-sm">Check Eligibility</a>

                @if (isset($s->match_result) && $s->match_result->isEligible() && !$s->match_result->existingApplicationStatus)
                    <a href="{{ route('student.scholarships.show', $s) }}" class="btn btn-success btn-sm">Apply Now</a>
                @elseif (isset($s->match_result) && $s->match_result->existingApplicationId)
                    <a href="{{ route('student.applications.show', $s->match_result->existingApplicationId) }}" class="btn btn-outline-dark btn-sm">View Application</a>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="pagination-wrapper">{{ $scholarships->links() }}</div>
@else
    <div class="card">
        <div class="empty-state" style="text-align: center; padding: 2rem;">
            <h3>No scholarships found</h3>
            <p>No scholarships match your current search criteria.</p>
            <p class="text-muted">Try adjusting your filters or broadening your search terms.</p>
            <a href="{{ route('student.scholarships.index') }}" class="btn btn-outline-dark" style="margin-top: 1rem;">Clear All Filters</a>
        </div>
    </div>
@endif
@endsection