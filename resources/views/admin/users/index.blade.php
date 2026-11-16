@extends('layouts.app')
@section('title', 'User Management — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>User Management</h2>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ Create User</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->has('error'))
    <div class="alert alert-error">{{ $errors->first('error') }}</div>
@endif

{{-- Filters --}}
<div class="card filter-card">
    <form method="GET" action="{{ route('admin.users.index') }}" class="filter-form"
          data-live-filter
          data-live-filter-target="#user-results">
        <div class="filter-row">
            <div class="form-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                       placeholder="Name, email, or student number"
                       data-live-search
                       data-live-search-endpoint="{{ route('admin.quick-search', ['module' => 'users']) }}">
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="">All Roles</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->name }}" {{ request('role') === $r->name ? 'selected' : '' }}>
                            {{ $r->label ?? ucfirst($r->name) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-dark">Clear</a>
            </div>
        </div>
    </form>
</div>

{{-- User Table --}}
<div class="card table-card">
    <div id="user-results" class="live-results">
        @include('admin.users._results', ['users' => $users])
    </div>

    <p class="sr-only" role="status" aria-live="polite" data-live-filter-status></p>
</div>
@endsection
