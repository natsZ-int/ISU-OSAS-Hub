@extends('layouts.app')
@section('title', 'Create Student Account — ISU OSAS Hub')

@section('content')
<div class="card auth-card">
    <h1>Create Student Account</h1>
    <p class="muted">Register with your student ID to get started.</p>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf
        <div class="form-group">
            <label for="student_number">University Student ID <span class="field-required">*</span></label>
            <input type="text" id="student_number" name="student_number" value="{{ old('student_number') }}"
                   maxlength="20" required autocomplete="off"
                   placeholder="e.g. 2024-0001">
        </div>

        <div class="form-group">
            <label for="name">Full Legal Name <span class="field-required">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}"
                   maxlength="255" required autocomplete="name">
        </div>

        <div class="form-group">
            <label for="email">Email Address <span class="field-required">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   maxlength="255" required autocomplete="email">
            <small class="field-hint">We will send a six-digit verification code to this address.</small>
        </div>

        <div class="form-group">
            <label for="college">College <span class="field-required">*</span></label>
            <input type="text" id="college" name="college" value="{{ old('college') }}"
                   maxlength="150" required autocomplete="organization"
                   placeholder="e.g. College of Engineering">
        </div>

        <div class="form-group">
            <label for="program">Program <span class="field-required">*</span></label>
            <input type="text" id="program" name="program" value="{{ old('program') }}"
                   maxlength="150" required
                   placeholder="e.g. Bachelor of Science in Computer Engineering">
        </div>

        <div class="form-group">
            <label for="contact_number">Contact Number <span class="field-required">*</span></label>
            <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}"
                   maxlength="20" required autocomplete="tel"
                   placeholder="e.g. 09171234567">
        </div>

        <div class="form-group">
            <label for="gwa">General Weighted Average (GWA) <span class="field-required">*</span></label>
            <input type="number" id="gwa" name="gwa" value="{{ old('gwa') }}"
                   min="1.00" max="5.00" step="0.01" required inputmode="decimal"
                   placeholder="e.g. 1.75">
            <small class="field-hint">Use the 1.00&ndash;5.00 scale (1.00 = highest).</small>
        </div>

        <div class="form-group">
            <label for="family_income">Annual Family Income (&#8369;) <span class="field-required">*</span></label>
            <input type="number" id="family_income" name="family_income" value="{{ old('family_income') }}"
                   min="0" step="0.01" required inputmode="decimal"
                   placeholder="e.g. 150000">
            <small class="field-hint">Total yearly household income in Philippine pesos. Use 0 if none.</small>
        </div>

        <div class="form-group">
            <label for="address">Home Address <span class="field-required">*</span></label>
            <textarea id="address" name="address" rows="3" maxlength="1000" required
                      placeholder="House/Street, Barangay, Municipality/City, Province">{{ old('address') }}</textarea>
        </div>

        <div class="form-group">
            <label for="password">Password <span class="field-required">*</span></label>
            <div class="password-field">
                <input type="password" id="password" name="password" required autocomplete="new-password">
                <button type="button" class="password-toggle" data-toggle-password="#password"
                        aria-label="Show password" aria-pressed="false">Show</button>
            </div>
            <small class="field-hint">Minimum 8 characters.</small>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm Password <span class="field-required">*</span></label>
            <div class="password-field">
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                <button type="button" class="password-toggle" data-toggle-password="#password_confirmation"
                        aria-label="Show password" aria-pressed="false">Show</button>
            </div>
        </div>

        <div class="form-check privacy-check">
            <label>
                <input type="checkbox" name="privacy_accepted" value="1" {{ old('privacy_accepted') ? 'checked' : '' }}>
                I accept the ISU OSAS Hub Privacy Notice. I understand that my information will be used
                solely for student affairs verification and scholarship processing, and that creating an
                account does not guarantee enrollment verification or scholarship approval.
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>

    <p class="auth-alt-link muted">
        Already have an account? <a href="{{ route('login') }}">Sign in</a>
    </p>
</div>
@endsection
