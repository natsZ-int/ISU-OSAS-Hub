@extends('layouts.app')
@section('title', 'Application Review — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Scholarship Application Review</h2>
</div>

<div class="card filter-card">
    <form method="GET" action="{{ route('admin.scholarship-applications.index') }}" class="filter-form">
        <div class="filter-row">
            <div class="form-group">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="App #, student, scholarship...">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    @foreach (\App\Models\ScholarshipApplication::ALLOWED_STATUSES as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.scholarship-applications.index') }}" class="btn btn-outline-dark">Clear</a>
            </div>
        </div>
    </form>
</div>

<div class="card table-card">
    @if ($applications->count() > 0)
    <table class="data-table">
        <thead><tr><th>App #</th><th>Student</th><th>Student No.</th><th>Scholarship</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead>
        <tbody>
            @foreach ($applications as $app)
            <tr>
                <td>{{ $app->application_number }}</td>
                <td>{{ $app->studentProfile->first_name }} {{ $app->studentProfile->last_name }}</td>
                <td>{{ $app->studentProfile->student_number }}</td>
                <td>{{ $app->scholarship->title }}</td>
                <td><span class="badge badge-status-{{ $app->status }}">{{ ucfirst(str_replace('_', ' ', $app->status)) }}</span></td>
                <td>{{ $app->submitted_at?->format('M d, Y') ?? '—' }}</td>
                <td><a href="{{ route('admin.scholarship-applications.show', $app) }}" class="btn btn-sm btn-primary">Review</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="pagination-wrapper">{{ $applications->links() }}</div>
    @else
        <p class="empty-state">No applications found.</p>
    @endif
</div>
@endsection