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
    <form method="GET" action="{{ route('admin.users.index') }}" class="filter-form">
        <div class="filter-row">
            <div class="form-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                       placeholder="Name, email, or student number">
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
    @if ($users->count() > 0)
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Student No.</th>
                    <th>Enrollment</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge badge-{{ $user->role?->name }}">
                            {{ $user->role?->label ?? ucfirst($user->role?->name ?? 'N/A') }}
                        </span>
                    </td>
                    <td>{{ $user->studentProfile?->student_number ?? '—' }}</td>
                    <td>
                        @if ($user->studentProfile)
                            @php $es = \App\Enums\EnrollmentStatus::tryFrom($user->studentProfile->enrollment_status ?? 'unknown'); @endphp
                            <span class="badge badge-{{ $es?->value ?? 'unknown' }}">
                                {{ $es?->label() ?? 'Unknown' }}
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if ($user->is_active)
                            <span class="badge badge-active">Active</span>
                        @else
                            <span class="badge badge-inactive">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                    <td class="actions-cell">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline">Edit</a>

                        @if ($user->is_active)
                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.deactivate', $user) }}"
                                      class="inline-form"
                                      onsubmit="return confirm('Deactivate {{ $user->name }}?')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-danger">Deactivate</button>
                                </form>
                            @endif
                        @else
                            <form method="POST" action="{{ route('admin.users.activate', $user) }}"
                                  class="inline-form"
                                  onsubmit="return confirm('Activate {{ $user->name }}?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success">Activate</button>
                            </form>
                        @endif

                        <a href="{{ route('admin.users.reset-password', $user) }}" class="btn btn-sm btn-outline">
                            Reset PW
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        {{ $users->links() }}
    </div>
    @else
        <p class="empty-state">No users found matching your criteria.</p>
    @endif
</div>
@endsection