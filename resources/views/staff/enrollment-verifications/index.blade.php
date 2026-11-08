@extends('layouts.app')
@section('title', 'Enrollment Verification — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Enrollment Verification Queue</h2>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->has('error'))
    <div class="alert alert-error">{{ $errors->first('error') }}</div>
@endif

<div class="card filter-card">
    <form method="GET" action="{{ route('enrollment-verifications.index') }}" class="filter-form">
        <div class="filter-row">
            <div class="form-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                       placeholder="Student ID, name, or email">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>
                            {{ $s->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="academic_year">Academic Year</label>
                <select id="academic_year" name="academic_year">
                    <option value="">All Years</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year }}" {{ request('academic_year') === $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="semester">Semester</label>
                <select id="semester" name="semester">
                    <option value="">All Semesters</option>
                    @foreach ($semesters as $sem)
                        <option value="{{ $sem }}" {{ request('semester') === $sem ? 'selected' : '' }}>
                            {{ ucfirst($sem) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('enrollment-verifications.index') }}" class="btn btn-outline-dark">Clear</a>
            </div>
        </div>
    </form>
</div>

<div class="card table-card">
    @if ($verifications->count() > 0)
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student No.</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Academic Year</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($verifications as $verification)
                        @php $profile = $verification->studentProfile; @endphp
                        <tr>
                            <td>{{ $profile?->student_number ?? '—' }}</td>
                            <td>{{ $profile ? trim($profile->first_name.' '.$profile->last_name) : '—' }}</td>
                            <td>{{ $profile?->user?->email ?? '—' }}</td>
                            <td>{{ $verification->academic_year }}</td>
                            <td>{{ ucfirst($verification->semester) }}</td>
                            <td>
                                <span class="badge badge-{{ $verification->status }}">
                                    {{ \App\Enums\EnrollmentVerificationStatus::tryFrom($verification->status)?->label() ?? ucfirst($verification->status) }}
                                </span>
                            </td>
                            <td>{{ $verification->created_at->format('M d, Y') }}</td>
                            <td class="actions-cell">
                                <a href="{{ route('enrollment-verifications.show', $verification) }}" class="btn btn-sm btn-outline">
                                    {{ $verification->status === \App\Enums\EnrollmentVerificationStatus::PENDING->value ? 'Review' : 'View' }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">
            {{ $verifications->links() }}
        </div>
    @else
        <p class="empty-state">No enrollment verification requests found.</p>
    @endif
</div>
@endsection
