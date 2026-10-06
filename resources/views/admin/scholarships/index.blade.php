@extends('layouts.app')
@section('title', 'Scholarship Management — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Scholarship Management</h2>
    <a href="{{ route('admin.scholarships.create') }}" class="btn btn-primary">+ Create Scholarship</a>
</div>

@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card filter-card">
    <form method="GET" action="{{ route('admin.scholarships.index') }}" class="filter-form">
        <div class="filter-row">
            <div class="form-group">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Title or provider">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    @foreach (\App\Models\Scholarship::ALLOWED_STATUSES as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.scholarships.index') }}" class="btn btn-outline-dark">Clear</a>
            </div>
        </div>
    </form>
</div>

<div class="card table-card">
    @if ($scholarships->count() > 0)
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th><th>Provider</th><th>Type</th><th>Deadline</th><th>Status</th><th>Apps</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($scholarships as $s)
                <tr>
                    <td>{{ $s->title }}</td>
                    <td>{{ $s->provider }}</td>
                    <td>{{ $s->scholarship_type }}</td>
                    <td>{{ $s->application_deadline->format('M d, Y') }}</td>
                    <td><span class="badge badge-scholarship-{{ $s->status }}">{{ ucfirst($s->status) }}</span></td>
                    <td>{{ $s->applications_count }}</td>
                    <td class="actions-cell">
                        <a href="{{ route('admin.scholarships.edit', $s) }}" class="btn btn-sm btn-outline">Edit</a>
                        <a href="{{ route('admin.scholarships.requirements.index', $s) }}" class="btn btn-sm btn-outline">Requirements</a>
                        @if ($s->status !== 'open')
                            <form method="POST" action="{{ route('admin.scholarships.activate', $s) }}" class="inline-form" onsubmit="return confirm('Activate this scholarship?')">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-success">Activate</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.scholarships.deactivate', $s) }}" class="inline-form