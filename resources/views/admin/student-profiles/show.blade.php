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
        $hasPasscode = $hasPasscode ?? $profile->hasProfilePasscode();
        $sensitiveUnlocked = $sensitiveUnlocked ?? ! $hasPasscode;
        $showSensitive = ! $hasPasscode || $sensitiveUnlocked;
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
    @if ($errors->has('passcode'))
        <x-alert type="error">{{ $errors->first('passcode') }}</x-alert>
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
                    <dd>{{ $showSensitive ? ($profile->contact_number ?: '—') : '••••••• (protected)' }}</dd>
                </div>
                <div>
                    <dt>Address</dt>
                    <dd>{{ $showSensitive ? ($profile->address ?: '—') : '••••••• (protected)' }}</dd>
                </div>
                <div>
                    <dt>GWA</dt>
                    <dd>{{ $showSensitive ? ($profile->gwa !== null ? number_format((float) $profile->gwa, 2) : '—') : '•••• (protected)' }}</dd>
                </div>
                <div>
                    <dt>Monthly Family Income</dt>
                    <dd>{{ $showSensitive ? ($profile->family_income !== null ? '₱'.number_format((float) $profile->family_income, 2) : '—') : '••••••• (protected)' }}</dd>
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

    {{-- ── Passcode Security Layer ───────────────────────────── --}}
    <x-card style="margin-top:1.25rem;">
        <h3 class="section-heading">Protected Profile Details</h3>

        @if ($hasPasscode && ! $sensitiveUnlocked)
            <p class="muted" style="font-size:.9rem;">
                This student protected their profile with a passcode. Enter the passcode provided by the
                student to reveal contact, address, GWA, and family income details.
            </p>

            <form method="POST" action="{{ route('admin.student-profiles.unlock', $profile) }}" class="filter-form" style="max-width:420px;">
                @csrf
                <div class="form-group">
                    <label for="passcode">Profile Passcode</label>
                    <input type="password" id="passcode" name="passcode" autocomplete="off"
                           inputmode="numeric" maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                    <x-form-error field="passcode" />
                </div>
                <button type="submit" class="btn btn-primary">Unlock Protected Details</button>
            </form>
        @elseif ($hasPasscode)
            <p class="muted" style="font-size:.9rem;">
                Protected details are unlocked for this session.
            </p>

            <form method="POST" action="{{ route('admin.student-profiles.lock', $profile) }}" class="inline-form">
                @csrf
                <button type="submit" class="btn btn-outline-dark">Lock Protected Details</button>
            </form>
        @else
            <p class="muted" style="font-size:.9rem;">
                This profile does not currently have a passcode configured. Authorized OSAS staff can view
                the information above. The student can enable passcode protection from their own profile.
            </p>
        @endif

        <details style="margin-top:1rem;">
            <summary style="cursor:pointer;font-weight:600;color:#2563eb;">Reset passcode</summary>
            <form method="POST" action="{{ route('admin.student-profiles.passcode.reset', $profile) }}" class="filter-form" style="max-width:420px;margin-top:.75rem;">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="reset_passcode">New Passcode</label>
                    <input type="password" id="reset_passcode" name="passcode" autocomplete="new-password"
                           minlength="{{ \App\Services\StudentProfilePasscodeService::MIN_LENGTH }}"
                           maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                </div>
                <div class="form-group">
                    <label for="reset_passcode_confirmation">Confirm New Passcode</label>
                    <input type="password" id="reset_passcode_confirmation" name="passcode_confirmation"
                           autocomplete="new-password" maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Reset Passcode</button>
            </form>
        </details>
    </x-card>

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
