@extends('layouts.app')
@section('title', 'Edit User — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Edit User: {{ $user->name }}</h2>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-dark">← Back to Users</a>
</div>

@if ($errors->any())
    <div class="alert alert-error">
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="card">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf @method('PUT')

        <fieldset>
            <legend>Account Information</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    @if ($user->isSystemAdmin())
                        <input type="text" id="email" value="{{ $user->email }}" disabled>
                        <input type="hidden" name="email" value="{{ old('email', $user->email) }}">
                        <span class="field-hint">The System Administrator e-mail is locked to keep role management secure.</span>
                    @else
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @endif
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="role_id">Role {{ auth()->user()->isSystemAdmin() ? '*' : '' }}</label>
                    @if (auth()->user()->isSystemAdmin())
                        <select id="role_id" name="role_id" required>
                            @foreach ($roles as $r)
                                <option value="{{ $r->id }}" {{ old('role_id', $user->role_id) == $r->id ? 'selected' : '' }}>
                                    {{ $r->label ?? ucfirst($r->name) }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" id="role_id"
                               value="{{ $user->role?->label ?? ucfirst($user->role?->name ?? 'N/A') }}"
                               disabled>
                        <span class="field-hint">Only the System Administrator can change user roles.</span>
                    @endif
                </div>
            </div>
        </fieldset>

        @if ($user->studentProfile)
        <fieldset>
            <legend>Student Profile</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="student_number">Student Number</label>
                    <input type="text" id="student_number" name="student_number"
                           value="{{ old('student_number', $user->studentProfile->student_number) }}">
                </div>
                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select id="year_level" name="year_level">
                        @for ($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}" {{ old('year_level', $user->studentProfile->year_level) == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group">
                    <label for="enrollment_status">Enrollment Status</label>
                    <select id="enrollment_status" name="enrollment_status">
                        @foreach (\App\Enums\EnrollmentStatus::cases() as $es)
                            <option value="{{ $es->value }}" {{ old('enrollment_status', $user->studentProfile->enrollment_status ?? 'unknown') === $es->value ? 'selected' : '' }}>
                                {{ $es->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name"
                           value="{{ old('first_name', $user->studentProfile->first_name) }}">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name"
                           value="{{ old('last_name', $user->studentProfile->last_name) }}">
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name"
                           value="{{ old('middle_name', $user->studentProfile->middle_name) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="college">College</label>
                    <input type="text" id="college" name="college"
                           value="{{ old('college', $user->studentProfile->college) }}">
                </div>
                <div class="form-group">
                    <label for="program">Program</label>
                    <input type="text" id="program" name="program"
                           value="{{ old('program', $user->studentProfile->program) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number"
                           value="{{ old('contact_number', $user->studentProfile->contact_number) }}">
                </div>
                <div class="form-group">
                    <label for="gwa">GWA</label>
                    <input type="number" id="gwa" name="gwa" step="0.01" min="1" max="5"
                           value="{{ old('gwa', $user->studentProfile->gwa) }}">
                </div>
                <div class="form-group">
                    <label for="family_income">Family Income</label>
                    <input type="number" id="family_income" name="family_income" step="0.01" min="0"
                           value="{{ old('family_income', $user->studentProfile->family_income) }}">
                </div>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="2">{{ old('address', $user->studentProfile->address) }}</textarea>
            </div>
        </fieldset>
        @endif

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>
@endsection