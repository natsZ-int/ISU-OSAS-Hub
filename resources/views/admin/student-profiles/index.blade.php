@extends('layouts.app')

@section('title', 'Student Profiles — ISU OSAS Hub')

@section('content')
    <x-page-header
        title="Student Profiles"
        subtitle="View and maintain student academic, contact, and financial information.">
        <x-slot:actions>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-dark">Manage Accounts</a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->has('error'))
        <x-alert type="error">{{ $errors->first('error') }}</x-alert>
    @endif

    <x-card class="filter-card">
        <form method="GET" action="{{ route('admin.student-profiles.index') }}" class="filter-form">
            <div class="filter-row">
                <div class="form-group" style="flex:2;">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                           placeholder="Student number, name, or email">
                </div>

                <div class="form-group">
                    <label for="college">College</label>
                    <select id="college" name="college">
                        <option value="">All Colleges</option>
                        @foreach ($colleges as $college)
                            <option value="{{ $college }}" @selected(request('college') === $college)>
                                {{ $college }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select id="year_level" name="year_level">
                        <option value="">All Levels</option>
                        @foreach ($yearLevels as $level)
                            <option value="{{ $level }}" @selected((string) request('year_level') === (string) $level)>
                                Year {{ $level }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="enrollment_status">Enrollment</label>
                    <select id="enrollment_status" name="enrollment_status">
                        <option value="">All Statuses</option>
                        @foreach ($enrollmentStatuses as $status)
                            <option value="{{ $status->value }}" @selected(request('enrollment_status') === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.student-profiles.index') }}" class="btn btn-outline-dark">Reset</a>
                </div>
            </div>
        </form>
    </x-card>

    <x-card class="table-card">
        @if ($profiles->count() > 0)
            <x-table-wrapper label="Student profiles">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student No.</th>
                            <th>Name</th>
                            <th>College / Program</th>
                            <th>Year</th>
                            <th>Enrollment</th>
                            <th>Account</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($profiles as $profile)
                            @php
                                $enrollment = \App\Enums\EnrollmentStatus::tryFrom($profile->enrollment_status ?? 'unknown');
                            @endphp
                            <tr>
                                <td class="font-monospace">{{ $profile->student_number }}</td>
                                <td>
                                    <a href="{{ route('admin.student-profiles.show', $profile) }}">
                                        {{ trim($profile->first_name.' '.$profile->last_name) }}
                                    </a>
                                    <br>
                                    <small class="muted">{{ $profile->user?->email ?? '—' }}</small>
                                </td>
                                <td>
                                    {{ $profile->program ?: '—' }}
                                    @if ($profile->college)
                                        <br><small class="muted">{{ $profile->college }}</small>
                                    @endif
                                </td>
                                <td>{{ $profile->year_level ? 'Year '.$profile->year_level : '—' }}</td>
                                <td>
                                    <span class="badge badge-{{ $enrollment?->value ?? 'unknown' }}">
                                        {{ $enrollment?->label() ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($profile->user?->is_active)
                                        <span class="badge badge-active">Active</span>
                                    @else
                                        <span class="badge badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    <a href="{{ route('admin.student-profiles.show', $profile) }}" class="btn btn-sm btn-outline">View</a>
                                    <a href="{{ route('admin.student-profiles.edit', $profile) }}" class="btn btn-sm btn-outline">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-wrapper>

            <div class="pagination-wrapper">
                {{ $profiles->links() }}
            </div>
        @else
            <p class="empty-state">No student profiles found matching your criteria.</p>
        @endif
    </x-card>
@endsection
