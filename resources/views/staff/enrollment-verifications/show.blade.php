@extends('layouts.app')
@section('title', 'Review Enrollment Verification — ISU OSAS Hub')

@section('content')
@php $profile = $verification->studentProfile; @endphp

<div class="page-header">
    <h2>Enrollment Verification: {{ $profile?->student_number ?? 'Unknown' }}</h2>
    <a href="{{ route('enrollment-verifications.index') }}" class="btn btn-outline-dark">← Back to Queue</a>
</div>

@if ($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="verification-layout">
    <div class="card">
        <h3 class="section-heading">Student Information</h3>
        <dl class="detail-list">
            <div><dt>Student Number</dt><dd>{{ $profile?->student_number ?? '—' }}</dd></div>
            <div><dt>Full Name</dt><dd>{{ $profile ? trim($profile->first_name.' '.$profile->last_name) : '—' }}</dd></div>
            <div><dt>Email</dt><dd>{{ $profile?->user?->email ?? '—' }}</dd></div>
            <div><dt>Program</dt><dd>{{ $profile?->program ?? '—' }}</dd></div>
            <div><dt>Year Level</dt><dd>{{ $profile?->year_level ?? '—' }}</dd></div>
            <div><dt>Current Enrollment Status</dt><dd>{{ $profile?->enrollment_status ?? '—' }}</dd></div>
        </dl>
    </div>

    <div class="card">
        <h3 class="section-heading">Verification Decision</h3>

        @if ($verification->status !== \App\Enums\EnrollmentVerificationStatus::PENDING->value)
            <div class="alert alert-info">
                This request was marked
                <strong>{{ \App\Enums\EnrollmentVerificationStatus::tryFrom($verification->status)?->label() }}</strong>
                @if ($verification->verified_at)
                    on {{ $verification->verified_at->format('M d, Y h:i A') }}
                @endif
                @if ($verification->verifier)
                    by {{ $verification->verifier->name }}
                @endif.
            </div>
        @endif

        @if ($verification->notes)
            <p><strong>Notes:</strong> {{ $verification->notes }}</p>
        @endif

        <form method="POST" action="{{ route('enrollment-verifications.update', $verification) }}">
            @csrf @method('PATCH')

            <div class="form-row">
                <div class="form-group">
                    <label for="academic_year">Academic Year <span class="field-required">*</span></label>
                    <input type="text" id="academic_year" name="academic_year" maxlength="20"
                           value="{{ old('academic_year', $verification->academic_year) }}" required>
                    <small class="field-hint">The academic year you checked against official records.</small>
                </div>
                <div class="form-group">
                    <label for="semester">Semester <span class="field-required">*</span></label>
                    <select id="semester" name="semester" required>
                        @foreach ($semesters as $sem)
                            <option value="{{ $sem }}" {{ old('semester', $verification->semester) === $sem ? 'selected' : '' }}>
                                {{ ucfirst($sem) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="status">Decision <span class="field-required">*</span></label>
                <select id="status" name="status" required>
                    <option value="">— Select a decision —</option>
                    <option value="{{ \App\Enums\EnrollmentVerificationStatus::VERIFIED->value }}"
                            {{ old('status', $verification->status) === \App\Enums\EnrollmentVerificationStatus::VERIFIED->value ? 'selected' : '' }}>
                        Verified — student is currently enrolled
                    </option>
                    <option value="{{ \App\Enums\EnrollmentVerificationStatus::REJECTED->value }}"
                            {{ old('status', $verification->status) === \App\Enums\EnrollmentVerificationStatus::REJECTED->value ? 'selected' : '' }}>
                        Rejected — student is not currently enrolled
                    </option>
                    <option value="{{ \App\Enums\EnrollmentVerificationStatus::UNABLE_TO_VERIFY->value }}"
                            {{ old('status', $verification->status) === \App\Enums\EnrollmentVerificationStatus::UNABLE_TO_VERIFY->value ? 'selected' : '' }}>
                        Unable to Verify — official records unavailable
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="notes">Notes / Reason</label>
                <textarea id="notes" name="notes" rows="3"
                          placeholder="Reference the official verification process. A reason is required for Rejected or Unable to Verify.">{{ old('notes', $verification->notes) }}</textarea>
                <small class="field-hint">Required when rejecting or marking unable to verify.</small>
            </div>

            <button type="submit" class="btn btn-primary">Record Decision</button>
        </form>
    </div>
</div>

<div class="card" style="margin-top:1.25rem;">
    <h3 class="section-heading">Verification History</h3>
    @if ($history->count() > 0)
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Academic Year</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Verified By</th>
                        <th>Verified At</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($history as $record)
                        <tr>
                            <td>{{ $record->academic_year }}</td>
                            <td>{{ ucfirst($record->semester) }}</td>
                            <td>
                                <span class="badge badge-{{ $record->status }}">
                                    {{ \App\Enums\EnrollmentVerificationStatus::tryFrom($record->status)?->label() ?? ucfirst($record->status) }}
                                </span>
                            </td>
                            <td>{{ $record->verifier?->name ?? '—' }}</td>
                            <td>{{ $record->verified_at?->format('M d, Y h:i A') ?? '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($record->notes, 80) ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="empty-state">No verification history for this student.</p>
    @endif
</div>
@endsection
