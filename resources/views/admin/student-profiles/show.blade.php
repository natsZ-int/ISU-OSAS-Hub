@extends('layouts.app')

@section('title', 'Student Profile — ISU OSAS Hub')

@section('content')
    @php
        $status = \App\Enums\EnrollmentStatus::tryFrom($profile->enrollment_status ?? 'unknown');
        $fullName = trim(implode(' ', array_filter([
            $profile->first_name,
            $profile->middle_name,
            $profile->last_name,
        ])));
        $latestVerification = $history->first();
    @endphp

    <x-page-header
        title="Student Profile: {{ $profile->student_number }}"
        subtitle="{{ $fullName }}">
        <x-slot:actions>
            <a href="{{ route('admin.student-profiles.edit', $profile) }}" class="btn btn-primary">Edit Profile</a>
            <a href="{{ route('admin.student-profiles.index') }}" class="btn btn-outline-dark">← Back to Profiles</a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->has('error'))
        <x-alert type="error">{{ $errors->first('error') }}</x-alert>
    @endif

    <div class="verification-layout">
        <x-card>
            <h3 class="section-heading">Student Information</h3>
            <dl class="detail-list">
                <div>
                    <dt>Student Number</dt>
                    <dd class="font-monospace">{{ $profile->student_number }}</dd>
                </div>
                <div>
                    <dt>Full Name</dt>
                    <dd>{{ $fullName }}</dd>
                </div>
                <div>
                    <dt>College</dt>
                    <dd>{{ $profile->college ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Program</dt>
                    <dd>{{ $profile->program ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Year Level</dt>
                    <dd>{{ $profile->year_level ? 'Year '.$profile->year_level : '—' }}</dd>
                </div>
                <div>
                    <dt>Enrollment Status</dt>
                    <dd>
                        <span class="badge badge-{{ $status?->value ?? 'unknown' }}">
                            {{ $status?->label() ?? 'Unknown' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <h3 class="section-heading">Contact &amp; Financial Information</h3>
            <dl class="detail-list">
                <div>
                    <dt>Email Address</dt>
                    <dd>{{ $profile->user?->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Account Status</dt>
                    <dd>
                        @if ($profile->user?->is_active)
                            <span class="badge badge-active">Active</span>
                        @else
                            <span class="badge badge-inactive">Inactive</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>Contact Number</dt>
                    <dd>{{ $profile->contact_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Address</dt>
                    <dd>{{ $profile->address ?: '—' }}</dd>
                </div>
                <div>
                    <dt>GWA</dt>
                    <dd>{{ $profile->gwa !== null ? number_format((float) $profile->gwa, 2) : '—' }}</dd>
                </div>
                <div>
                    <dt>Monthly Family Income</dt>
                    <dd>{{ $profile->family_income !== null ? '₱'.number_format((float) $profile->family_income, 2) : '—' }}</dd>
                </div>
            </dl>

            @if ($profile->user)
                <p style="margin-top:1rem;">
                    <a href="{{ route('admin.users.edit', $profile->user) }}" class="btn btn-sm btn-outline">
                        Manage Account Settings
                    </a>
                </p>
            @endif
        </x-card>
    </div>

    <x-card style="margin-top:1.25rem;">
        <h3 class="section-heading">Enrollment Verification</h3>

        @if ($latestVerification)
            <p class="muted" style="font-size:.9rem;">
                Latest verification for
                <strong>{{ $latestVerification->academic_year }} — {{ ucfirst($latestVerification->semester) }} semester</strong>
                was marked
                <span class="badge badge-{{ $latestVerification->status }}">
                    {{ \App\Enums\EnrollmentVerificationStatus::tryFrom($latestVerification->status)?->label() ?? ucfirst($latestVerification->status) }}
                </span>
                @if ($latestVerification->verified_at)
                    on {{ $latestVerification->verified_at->format('M d, Y h:i A') }}
                @endif.
            </p>

            <x-table-wrapper label="Enrollment verification history" :show-hint="false">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th>Status</th>
                            <th>Verified By</th>
                            <th>Recorded</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($history as $verification)
                            <tr>
                                <td>{{ $verification->academic_year }}</td>
                                <td>{{ ucfirst($verification->semester) }}</td>
                                <td>
                                    <span class="badge badge-{{ $verification->status }}">
                                        {{ \App\Enums\EnrollmentVerificationStatus::tryFrom($verification->status)?->label() ?? ucfirst($verification->status) }}
                                    </span>
                                </td>
                                <td>{{ $verification->verifier?->name ?? '—' }}</td>
                                <td>
                                    {{ $verification->verified_at?->format('M d, Y h:i A')
                                        ?? $verification->created_at?->format('M d, Y h:i A')
                                        ?? '—' }}
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($verification->notes, 100) ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-wrapper>
        @else
            <p class="empty-state">No enrollment verification records for this student.</p>
        @endif
    </x-card>
@endsection
