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