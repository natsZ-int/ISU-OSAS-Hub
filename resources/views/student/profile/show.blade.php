@extends('layouts.app')

@section('title', 'My Profile — ISU OSAS Hub')

@section('content')
    @php
        $status = \App\Enums\EnrollmentStatus::tryFrom($profile->enrollment_status ?? 'unknown');
        $fullName = trim(implode(' ', array_filter([
            $profile->first_name,
            $profile->middle_name,
            $profile->last_name,
        ])));
        $latestVerification = $profile->enrollmentVerifications->first();
        $hasPasscode = $hasPasscode ?? $profile->hasProfilePasscode();
        $sensitiveUnlocked = $sensitiveUnlocked ?? ! $hasPasscode;
        $showSensitive = ! $hasPasscode || $sensitiveUnlocked;
    @endphp

    <x-page-header
        title="My Profile"
        subtitle="Keep your personal, academic, and financial information up to date.">
        <x-slot:actions>
            <a href="{{ route('student.profile.edit') }}" class="btn btn-primary">Edit Profile</a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <x-alert type="error">
            <ul style="margin:0;padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
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

            <p class="field-hint" style="margin-top:1rem;">
                Your student number and enrollment status are managed by OSAS. Keep your GWA and family
                income updated because they are used for scholarship eligibility and recommendations.
                Contact, address, GWA, and family income are protected while the profile is locked.
            </p>
        </x-card>
    </div>

    {{-- ── Passcode Security Layer ───────────────────────────── --}}
    <x-card style="margin-top:1.25rem;">
        <h3 class="section-heading">Profile Security</h3>

        @if ($hasPasscode && ! $sensitiveUnlocked)
            <p class="muted" style="font-size:.9rem;">
                This profile is protected. Enter your passcode to view the contact, address, GWA, and
                family income details.
            </p>

            <form method="POST" action="{{ route('student.profile.unlock') }}" class="filter-form" style="max-width:420px;">
                @csrf
                <div class="form-group">
                    <label for="passcode">Profile Passcode</label>
                    <input type="password" id="passcode" name="passcode" autocomplete="current-password"
                           inputmode="numeric" maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                    <x-form-error field="passcode" />
                </div>
                <button type="submit" class="btn btn-primary">Unlock Profile</button>
            </form>
        @elseif ($hasPasscode)
            <p class="muted" style="font-size:.9rem;">
                Protected details are unlocked for this session. Lock the profile when you are finished,
                or change the passcode below.
            </p>

            <div class="button-row" style="margin-bottom:1rem;">
                <form method="POST" action="{{ route('student.profile.lock') }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-outline-dark">Lock Profile</button>
                </form>
            </div>

            <details>
                <summary style="cursor:pointer;font-weight:600;color:#2563eb;">Change passcode</summary>
                <form method="POST" action="{{ route('student.profile.passcode.update') }}" class="filter-form" style="max-width:420px;margin-top:.75rem;">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="new_passcode">New Passcode</label>
                        <input type="password" id="new_passcode" name="passcode" autocomplete="new-password"
                               minlength="{{ \App\Services\StudentProfilePasscodeService::MIN_LENGTH }}"
                               maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                    </div>
                    <div class="form-group">
                        <label for="new_passcode_confirmation">Confirm New Passcode</label>
                        <input type="password" id="new_passcode_confirmation" name="passcode_confirmation"
                               autocomplete="new-password" maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                        <x-form-error field="passcode_confirmation" />
                    </div>
                    <button type="submit" class="btn btn-primary">Update Passcode</button>
                </form>
            </details>

            <form method="POST" action="{{ route('student.profile.passcode.destroy') }}" style="margin-top:1rem;"
                  onsubmit="return confirm('Remove passcode protection from your profile?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Remove Passcode Protection</button>
            </form>
        @else
            <p class="muted" style="font-size:.9rem;">
                Add a passcode to protect your contact, address, GWA, and family income from being shown
                until it is entered.
            </p>

            <form method="POST" action="{{ route('student.profile.passcode.store') }}" class="filter-form" style="max-width:420px;">
                @csrf
                <div class="form-group">
                    <label for="profile_passcode">New Passcode</label>
                    <input type="password" id="profile_passcode" name="passcode" autocomplete="new-password"
                           minlength="{{ \App\Services\StudentProfilePasscodeService::MIN_LENGTH }}"
                           maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                    <x-form-error field="passcode" />
                </div>
                <div class="form-group">
                    <label for="profile_passcode_confirmation">Confirm Passcode</label>
                    <input type="password" id="profile_passcode_confirmation" name="passcode_confirmation"
                           autocomplete="new-password" maxlength="{{ \App\Services\StudentProfilePasscodeService::MAX_LENGTH }}" required>
                    <x-form-error field="passcode_confirmation" />
                </div>
                <button type="submit" class="btn btn-primary">Save Passcode</button>
            </form>
        @endif
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
                            <th>Recorded</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($profile->enrollmentVerifications as $verification)
                            <tr>
                                <td>{{ $verification->academic_year }}</td>
                                <td>{{ ucfirst($verification->semester) }}</td>
                                <td>
                                    <span class="badge badge-{{ $verification->status }}">
                                        {{ \App\Enums\EnrollmentVerificationStatus::tryFrom($verification->status)?->label() ?? ucfirst($verification->status) }}
                                    </span>
                                </td>
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
            <p class="empty-state">
                No enrollment verification records yet. OSAS will review your enrollment for the current
                academic term.
            </p>
        @endif
    </x-card>
@endsection
