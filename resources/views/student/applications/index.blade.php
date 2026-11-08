@extends('layouts.app')
@section('title', 'My Applications — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>My Scholarship Applications</h2>
    <a href="{{ route('student.scholarships.index') }}" class="btn btn-outline-dark">Browse Scholarships</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card filter-card">
    <form method="GET" action="{{ route('student.applications.index') }}" class="filter-form" role="search">
        <div class="filter-row">
            <div class="form-group" style="flex:2;">
                <label for="application_search">Search</label>
                <input type="search" id="application_search" name="search"
                       value="{{ request('search') }}"
                       placeholder="Application number, scholarship, or provider..."
                       maxlength="200"
                       data-live-search
                       data-live-search-endpoint="{{ route('student.quick-search', ['module' => 'applications']) }}">
            </div>
            <div class="form-group">
                <label for="application_status">Status</label>
                <select id="application_status" name="status">
                    <option value="">All Statuses</option>
                    @foreach (\App\Models\ScholarshipApplication::ALLOWED_STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                @if (request()->hasAny(['search', 'status']))
                    <a href="{{ route('student.applications.index') }}" class="btn btn-outline-dark btn-sm">Clear</a>
                @endif
            </div>
        </div>
    </form>
</div>

@if ($applications->count() > 0)
<div class="card table-card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Application #</th>
                    <th>Scholarship</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($applications as $app)
                <tr>
                    <td>{{ $app->application_number }}</td>
                    <td>{{ $app->scholarship->title }}</td>
                    <td><x-status-badge :status="$app->status" /></td>
                    <td>{{ $app->submitted_at ? $app->submitted_at->format('M d, Y') : '—' }}</td>
                    <td><a href="{{ route('student.applications.show', $app) }}" class="btn btn-sm btn-outline">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">{{ $applications->links() }}</div>
</div>
@else
    <div class="card">
        <p class="empty-state">
            @if (request()->hasAny(['search', 'status']))
                No applications match your search or status filter.
            @else
                You have no scholarship applications yet.
            @endif
        </p>
    </div>
@endif
@endsection