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
    @if ($users->count() > 0)
    <div class="table-responsive users-table-wrap">
        <table class="data-table users-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Student No.</th>
                    <th>Enrollment</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="user-actions-header">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td class="user-email">
                        @php
                            $emailChunks = preg_split('/(?<=[@.])/', $user->email, -1, PREG_SPLIT_NO_EMPTY) ?: [$user->email];
                            $emailHtml = implode('<wbr>', array_map(fn ($chunk) => e($chunk), $emailChunks));
                        @endphp
                        {!! $emailHtml !!}
                    </td>
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
                    <td class="user-actions-cell">
                        <div class="user-action-buttons">
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="btn btn-ua btn-ua-edit"
                               aria-label="Edit {{ $user->name }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                     aria-hidden="true" focusable="false">
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                </svg>
                                <span>Edit</span>
                            </a>

                            @if ($user->is_active)
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.deactivate', $user) }}"
                                          class="inline-form"
                                          data-action-form
                                          onsubmit="return confirm(@js('Deactivate ' . $user->name . '?'))">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-ua btn-ua-danger"
                                                data-loading-label="Deactivating…"
                                                aria-label="Deactivate {{ $user->name }}">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                 aria-hidden="true" focusable="false">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <line x1="17" y1="8" x2="22" y2="13"></line>
                                                <line x1="22" y1="8" x2="17" y2="13"></line>
                                            </svg>
                                            <span>Deactivate</span>
                                        </button>
                                    </form>
                                @endif
                            @else
                                <form method="POST" action="{{ route('admin.users.activate', $user) }}"
                                      class="inline-form"
                                      data-action-form
                                      onsubmit="return confirm(@js('Activate ' . $user->name . '?'))">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-ua btn-ua-success"
                                            data-loading-label="Activating…"
                                            aria-label="Activate {{ $user->name }}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                             aria-hidden="true" focusable="false">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <polyline points="16 11 18 13 22 9"></polyline>
                                        </svg>
                                        <span>Activate</span>
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('admin.users.reset-password', $user) }}"
                               class="btn btn-ua btn-ua-neutral"
                               aria-label="Reset password for {{ $user->name }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                     aria-hidden="true" focusable="false">
                                    <circle cx="7.5" cy="15.5" r="5.5"></circle>
                                    <path d="m21 2-9.6 9.6"></path>
                                    <path d="m15.5 7.5 3 3L22 7l-3-3"></path>
                                </svg>
                                <span>Reset PW</span>
                            </a>
                        </div>
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