@extends('layouts.app')
@section('title', 'Application ' . $scholarshipApplication->application_number)

@section('content')
<div class="page-header">
    <h2>Application {{ $scholarshipApplication->application_number }}</h2>
    <a href="{{ route('student.applications.index') }}" class="btn btn-outline-dark">← Back</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-error">
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

{{-- FSM Progress Indicator (read-only for students) --}}
@include('partials.fsm-progress', ['currentStatus' => $scholarshipApplication->fsmStatus()])

<div class="card">
    <h3>Scholarship: {{ $scholarshipApplication->scholarship->title }}</h3>
    <p><strong>Provider:</strong> {{ $scholarshipApplication->scholarship->provider }}</p>
    <p><strong>Status:</strong>
        @php $fsm = $scholarshipApplication->fsmStatus(); @endphp
        @if ($fsm)
            <span class="badge badge-status-{{ $fsm->value }}">{{ $fsm->label() }}</span>
        @else
            <span class="badge badge-status-draft">Draft</span>
        @endif
    </p>
    <p><strong>Submitted:</strong> {{ $scholarshipApplication->submitted_at ? $scholarshipApplication->submitted_at->format('M d, Y H:i') : 'Not yet submitted' }}</p>
</div>

{{-- Documents --}}
<div class="card">
    <h3>Documents</h3>
    <table class="data-table">
        <thead>
            <tr><th>Requirement</th><th>File</th><th>Verification</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @foreach ($scholarshipApplication->scholarship->requirements as $req)
            @php $doc = $scholarshipApplication->documents->where('scholarship_requirement_id', $req->id)->first(); @endphp
            <tr>
                <td>{{ $req->requirement_name }} @if ($req->is_required)<strong>*</strong>@endif</td>
                <td>
                    @if ($doc)
                        <a href="{{ route('student.documents.download', $doc) }}">{{ $doc->original_filename }}</a>
                    @else
                        <span class="muted">Not uploaded</span>
                    @endif
                </td>
                <td>
                    @if ($doc)
                        <span class="badge badge-verification-{{ $doc->verification_status }}">{{ ucfirst($doc->verification_status) }}</span>
                        @if ($doc->verification_remarks)
                            <br><small class="muted">{{ $doc->verification_remarks }}</small>
                        @endif
                    @else — @endif
                </td>
                <td>
                    @if ($scholarshipApplication->isDraft())
                        <form method="POST" action="{{ route('student.documents.store', $scholarshipApplication) }}" enctype="multipart/form-data" class="inline-form">
                            @csrf
                            <input type="hidden" name="scholarship_requirement_id" value="{{ $req->id }}">
                            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required style="width:auto;display:inline;">
                            <button type="submit" class="btn btn-sm btn-primary">{{ $doc ? 'Replace' : 'Upload' }}</button>
                        </form>
                        @if ($doc)
                            <form method="POST" action="{{ route('student.documents.destroy', [$scholarshipApplication, $doc]) }}" class="inline-form" onsubmit="return confirm('Remove?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        @endif
                    @else
                        <span class="muted">Locked</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Submit (only for drafts) --}}
@if ($scholarshipApplication->isDraft())
<div class="card">
    <form method="POST" action="{{ route('student.applications.submit', $scholarshipApplication) }}"
          onsubmit="return confirm('Submit this application? You cannot modify documents after submission.')">
        @csrf
        <button type="submit" class="btn btn-primary">Submit Application</button>
    </form>
</div>
@endif

{{-- Status History --}}
<div class="card">
    <h3>Status History</h3>
    <ul class="status-timeline">
        @forelse ($scholarshipApplication->statusHistories as $history)
        <li>
            <strong>{{ ucfirst(str_replace('_', ' ', $history->new_status)) }}</strong>
            <span class="muted">{{ $history->created_at->format('M d, Y H:i') }}</span>
            @if ($history->remarks)<br><small>{{ $history->remarks }}</small>@endif
        </li>
        @empty
            <li class="muted">No status history yet.</li>
        @endforelse
    </ul>
</div>
@endsection