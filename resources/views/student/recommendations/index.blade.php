@extends('layouts.app')
@section('title', 'Recommended Scholarships — ISU OSAS Hub')

@section('content')
<div style="max-width: 900px; margin: 0 auto; padding: 1.5rem;">

    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;">Recommended Scholarships</h2>
        <a href="{{ route('student.scholarships.index') }}" style="padding: 0.4rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; font-size: 0.85rem; color: #475569;">Browse All Scholarships</a>
    </div>

    {{-- Disclaimer --}}
    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 0.75rem 1rem; margin-bottom: 1.5rem; font-size: 0.8rem; color: #1e40af; line-height: 1.5;">
        <strong>Disclaimer:</strong> Recommendations are based on your current profile information, scholarship criteria, application requirements, and matching configuration. A high match score is not a guarantee of scholarship approval. Final decisions depend on complete requirements, document verification, and administrator review.
    </div>

    @php
        $hasAny = $eligible->count() > 0 || $potential->count() > 0;
    @endphp

    {{-- ── ELIGIBLE RECOMMENDATIONS ── --}}
    @if ($eligible->count() > 0)
        <h3 style="font-size: 1.1rem; font-weight: 700; color: #16a34a; margin: 1.5rem 0 0.75rem 0;">
            &#10003; Eligible Recommendations ({{ $eligible->count() }})
        </h3>

        @foreach ($eligible as $rec)
        <div style="background: #fff; border: 1px solid #bbf7d0; border-left: 4px solid #16a34a; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span style="background: #dcfce7; color: #16a34a; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 9999px;">{{ $rec->category->label() }}</span>
                        <span style="font-size: 0.75rem; color: #64748b;">Rank #{{ $rec->rank }}</span>
                    </div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0;">{{ $rec->scholarshipTitle }}</h3>
                    <p style="font-size: 0.8rem; color: #64748b; margin: 0.15rem 0 0 0;">{{ $rec->provider }} &middot; {{ $rec->scholarshipType }}</p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: #16a34a;">{{ $rec->matchScore !== null ? number_format($rec->matchScore, 1) . '%' : 'N/A' }}</div>
                    <div style="font-size: 0.65rem; color: #64748b; text-transform: uppercase; font-weight: 600;">Match Score</div>
                </div>
            </div>

            <div style="margin-top: 0.75rem; font-size: 0.85rem; color: #475569;">
                <strong>Deadline:</strong> {{ $rec->deadline }}
            </div>

            {{-- Factors --}}
            <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.75rem;">
                @foreach ($rec->factors as $f)
                    <span style="font-size: 0.72rem; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600;
                        @if($f->status === 'met') background: #dcfce7; color: #166534;
                        @elseif($f->status === 'not_met') background: #fee2e2; color: #991b1b;
                        @elseif($f->status === 'partial') background: #fef3c7; color: #92400e;
                        @else background: #f1f5f9; color: #64748b; @endif">
                        @if($f->status === 'met') &#10003; @elseif($f->status === 'not_met') &#10007; @elseif($f->status === 'partial') &#9684; @else &mdash; @endif
                        {{ $f->label }}: {{ $f->isApplicable && $f->normalizedScore !== null ? round($f->normalizedScore * 100) . '%' : 'N/A' }}
                    </span>
                @endforeach
            </div>

            {{-- Reasons --}}
            <details style="margin-top: 0.75rem;">
                <summary style="font-size: 0.8rem; color: #2563eb; cursor: pointer; font-weight: 600;">View Reasons</summary>
                <ul style="margin: 0.5rem 0 0 1.25rem; font-size: 0.8rem; color: #475569; line-height: 1.6;">
                    @foreach ($rec->reasons as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            </details>

            {{-- Actions --}}
            <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem; flex-wrap: wrap;">
                <a href="{{ route('student.scholarships.show', $rec->scholarshipId) }}" style="padding: 0.35rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; font-size: 0.8rem; color: #475569;">View Details</a>
                @if ($rec->hasExistingActiveApplication())
                    <a href="{{ route('student.applications.show', $rec->existingApplicationId) }}" style="padding: 0.35rem 0.75rem; background: #2563eb; color: #fff; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600;">View Application</a>
                @elseif (! $rec->existingApplicationStatus)
                    <form method="POST" action="{{ route('student.applications.store', $rec->scholarshipId) }}" onsubmit="return confirm('Start application for this scholarship?')">
                        @csrf
                        <button type="submit" style="padding: 0.35rem 0.75rem; background: #16a34a; color: #fff; border: none; border-radius: 4px; font-size: 0.8rem; font-weight: 600; cursor: pointer;">Apply Now</button>
                    </form>
                @endif
            </div>
        </div>
        @endforeach
    @endif

    {{-- ── POTENTIAL MATCHES ── --}}
    @if ($potential->count() > 0)
        <h3 style="font-size: 1.1rem; font-weight: 700; color: #d97706; margin: 1.5rem 0 0.75rem 0;">
            &#9684; Potential Matches ({{ $potential->count() }})
        </h3>
        <p style="font-size: 0.8rem; color: #92400e; margin-bottom: 0.75rem;">These scholarships match your profile, but some application requirements need your attention.</p>

        @foreach ($potential as $rec)
        <div style="background: #fff; border: 1px solid #fde68a; border-left: 4px solid #d97706; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <span style="background: #fef3c7; color: #92400e; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 9999px;">{{ $rec->category->label() }}</span>
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0.25rem 0 0 0;">{{ $rec->scholarshipTitle }}</h3>
                    <p style="font-size: 0.8rem; color: #64748b; margin: 0.15rem 0 0 0;">{{ $rec->provider }} &middot; {{ $rec->scholarshipType }}</p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 1.3rem; font-weight: 800; color: #d97706;">{{ $rec->matchScore !== null ? number_format($rec->matchScore, 1) . '%' : 'N/A' }}</div>
                    <div style="font-size: 0.65rem; color: #64748b; text-transform: uppercase; font-weight: 600;">Match Score</div>
                </div>
            </div>

            <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #475569;">
                <strong>Deadline:</strong> {{ $rec->deadline }}
            </div>

            {{-- Required Action --}}
            @if (! empty($rec->requirementReadiness['missing']))
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; padding: 0.5rem 0.75rem; margin-top: 0.75rem; font-size: 0.8rem; color: #92400e;">
                    <strong>Required Action:</strong> Upload the following missing documents:
                    <ul style="margin: 0.25rem 0 0 1.25rem;">
                        @foreach ($rec->requirementReadiness['missing'] as $doc)
                            <li>{{ $doc }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Reasons --}}
            <details style="margin-top: 0.75rem;">
                <summary style="font-size: 0.8rem; color: #2563eb; cursor: pointer; font-weight: 600;">View Reasons</summary>
                <ul style="margin: 0.5rem 0 0 1.25rem; font-size: 0.8rem; color: #475569; line-height: 1.6;">
                    @foreach ($rec->reasons as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            </details>

            {{-- Actions --}}
            <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem; flex-wrap: wrap;">
                <a href="{{ route('student.scholarships.show', $rec->scholarshipId) }}" style="padding: 0.35rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; font-size: 0.8rem; color: #475569;">View Details</a>
                @if ($rec->hasExistingActiveApplication())
                    <a href="{{ route('student.applications.show', $rec->existingApplicationId) }}" style="padding: 0.35rem 0.75rem; background: #d97706; color: #fff; border-radius: 4px; text-decoration: none; font-size: 0.8rem; font-weight: 600;">Continue Application</a>
                @endif
            </div>
        </div>
        @endforeach
    @endif

    {{-- Empty State --}}
    @if (! $hasAny)
        <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 2rem; text-align: center; color: #64748b; font-size: 0.9rem;">
            No matching scholarships found based on your current profile. Please ensure your student profile is complete and check back later for new opportunities.
        </div>
    @endif

    {{-- ── NOT ELIGIBLE ── --}}
    @if ($not_eligible->count() > 0)
        <h3 style="font-size: 1.1rem; font-weight: 700; color: #64748b; margin: 2rem 0 0.75rem 0; border-top: 1px solid #e2e8f0; padding-top: 1.5rem;">
            Other Available Scholarships — Not Currently Eligible ({{ $not_eligible->count() }})
        </h3>
        <p style="font-size: 0.8rem; color: #94a3b8; margin-bottom: 0.75rem;">You do not currently meet the mandatory eligibility requirements for these scholarships.</p>

        @foreach ($not_eligible as $rec)
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-left: 4px solid #dc2626; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; opacity: 0.85;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <span style="background: #fee2e2; color: #991b1b; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 9999px;">{{ $rec->category->label() }}</span>
                    <h3 style="font-size: 0.95rem; font-weight: 700; color: #475569; margin: 0.25rem 0 0 0;">{{ $rec->scholarshipTitle }}</h3>
                    <p style="font-size: 0.75rem; color: #94a3b8; margin: 0.1rem 0 0 0;">{{ $rec->provider }} &middot; Deadline: {{ $rec->deadline }}</p>
                </div>
            </div>

            <details style="margin-top: 0.5rem;">
                <summary style="font-size: 0.78rem; color: #64748b; cursor: pointer; font-weight: 600;">Why am I not eligible?</summary>
                <ul style="margin: 0.5rem 0 0 1.25rem; font-size: 0.78rem; color: #64748b; line-height: 1.6;">
                    @foreach ($rec->reasons as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            </details>
        </div>
        @endforeach
    @endif

</div>
@endsection