@extends('layouts.app')
@section('title', 'Review ' . $scholarshipApplication->application_number)

@section('content')
<div class="page-header">
    <h2>Review: {{ $scholarshipApplication->application_number }}</h2>
    <a href="{{ route('admin.scholarship-applications.index') }}" class="btn btn-outline-dark">← Back</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->has('transition'))
    <div class="alert alert-error">{{ $errors->first('transition') }}</div>
@endif

{{-- ── Visual FSM Progress Indicator ──────────────── --}}
@include('partials.fsm-progress', ['currentStatus' => $fsmStatus])

{{-- ── Student Information ────────────────────────── --}}
<div class="card">
    <h3>Student Information</h3>
    <p><strong>Name:</strong> {{ $scholarshipApplication->studentProfile->first_name }} {{ $scholarshipApplication->studentProfile->last_name }}</p>
    <p><strong>Student No.:</strong> {{ $scholarshipApplication->studentProfile->student_number }}</p>
    <p><strong>Program:</strong> {{ $scholarshipApplication->studentProfile->program ?? 'N/A' }}</p>
    <p><strong>GWA:</strong> {{ $scholarshipApplication->studentProfile->gwa ?? 'N/A' }}</p>
</div>

{{-- ── Scholarship & Status ───────────────────────── --}}
<div class="card">
    <h3>Scholarship: {{ $scholarshipApplication->scholarship->title }}</h3>
    <p><strong>Current Status:</strong>
        @if ($fsmStatus)
            <x-status-badge :status="$fsmStatus" />
        @else
            <x-status-badge status="draft" /> <small class="muted">(Pre-Submission)</small>
        @endif
    </p>
    <p><strong>Submitted:</strong> {{ $scholarshipApplication->submitted_at?->format('M d, Y H:i') ?? 'Not submitted' }}</p>
</div>

{{-- ── Documents & Verification ───────────────────── --}}
<div class="card">
    <h3>Documents</h3>
    <table class="data-table">
        <thead><tr><th>Requirement</th><th>Required</th><th>File</th><th>Verification</th><th>Remarks</th><th>Action</th></tr></thead>
        <tbody>
            @foreach ($scholarshipApplication->scholarship->requirements as $req)
                @php $doc = $scholarshipApplication->documents->where('scholarship_requirement_id', $req->id)->first(); @endphp
                <tr>
                    <td>{{ $req->requirement_name }}</td>
                    <td>{{ $req->is_required ? 'Yes' : 'No' }}</td>
                    <td>
                        @if ($doc)
                            <a href="{{ route('admin.application-documents.download', $doc) }}">{{ $doc->original_filename }}</a>
                        @else
                            <span class="muted">Not uploaded</span>
                        @endif
                    </td>
                    <td>
                        @if ($doc)
                            <span class="badge badge-verification-{{ $doc->verification_status }}">{{ ucfirst($doc->verification_status) }}</span>
                        @else — @endif
                    </td>
                    <td>{{ $doc?->verification_remarks ?? '—' }}</td>
                    <td>
                        @if ($doc)
                        <form method="POST" action="{{ route('admin.application-documents.update-verification', $doc) }}" class="inline-form">
                            @csrf @method('PATCH')
                            <select name="verification_status" style="width:auto;display:inline;">
                                <option value="pending" {{ $doc->verification_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="verified" {{ $doc->verification_status === 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="rejected" {{ $doc->verification_status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                            <input type="text" name="verification_remarks" placeholder="Remarks" style="width:140px;display:inline;">
                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- ── FSM Transition Controls ────────────────────── --}}
@if ($fsmStatus && count($allowedTransitions) > 0)
<div class="card">
    <h3>Transition Application Status</h3>

    @if (! $approvalReadiness['canApprove'] && in_array(\App\Enums\ScholarshipApplicationStatus::APPROVED, $allowedTransitions))
        <div class="alert alert-error">
            <strong>Approval blocked:</strong>
            <ul>
                @foreach ($approvalReadiness['reasons'] as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.scholarship-applications.transition', $scholarshipApplication) }}"
          onsubmit="return confirm('Confirm this status transition?')">
        @csrf @method('PATCH')
        <div class="form-row">
            <div class="form-group">
                <label>New Status *</label>
                <select name="status" required>
                    @foreach ($allowedTransitions as $target)
                        <option value="{{ $target->value }}">{{ $target->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Remarks <small class="muted">(required for rejection)</small></label>
                <textarea name="remarks" rows="2" placeholder="Enter remarks..."></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Apply Transition</button>
    </form>
</div>
@elseif ($fsmStatus && $fsmStatus->isTerminal())
<div class="card">
    <p class="muted">This application has reached a final decision (<strong>{{ $fsmStatus->label() }}</strong>). No further transitions are allowed.</p>
</div>
@endif

{{-- ── Status History Timeline ────────────────────── --}}
<div class="card">
    <h3>Status History</h3>
    <ul class="status-timeline">
        @forelse ($scholarshipApplication->statusHistories as $h)
        <li>
            <strong>{{ ucfirst(str_replace('_', ' ', $h->new_status)) }}</strong>
            @if ($h->previous_status)
                <span class="muted">(from {{ ucfirst(str_replace('_', ' ', $h->previous_status)) }})</span>
            @endif
            by {{ $h->changedBy?->name ?? 'System' }}
            · {{ $h->created_at->format('M d, Y H:i') }}
            @if ($h->remarks)<br><small>{{ $h->remarks }}</small>@endif
        </li>
        @empty
            <li class="muted">No status history yet.</li>
        @endforelse
    </ul>
</div>
@endsection