@extends('layouts.app')
@section('title', 'Edit Scholarship — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Edit: {{ $scholarship->title }}</h2>
    <a href="{{ route('admin.scholarships.index') }}" class="btn btn-outline-dark">← Back</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <form method="POST" action="{{ route('admin.scholarships.update', $scholarship) }}">
        @csrf
        @method('PUT')
        
        <fieldset>
            <legend>Basic Information</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $scholarship->title) }}" required>
                </div>
                <div class="form-group">
                    <label for="provider">Provider *</label>
                    <input type="text" id="provider" name="provider" value="{{ old('provider', $scholarship->provider) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="scholarship_type">Type *</label>
                    <input type="text" id="scholarship_type" name="scholarship_type" value="{{ old('scholarship_type', $scholarship->scholarship_type) }}" required>
                </div>
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        @foreach (\App\Models\Scholarship::ALLOWED_STATUSES as $s)
                            <option value="{{ $s }}" {{ old('status', $scholarship->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" rows="4" required>{{ old('description', $scholarship->description) }}</textarea>
            </div>
        </fieldset>

        <fieldset>
            <legend>Eligibility Criteria (Engine Rules)</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="minimum_gwa">Minimum Academic GWA</label>
                    <input type="number" id="minimum_gwa" name="minimum_gwa" step="0.01" min="1" max="5" value="{{ old('minimum_gwa', $scholarship->minimum_gwa) }}" placeholder="e.g. 1.75">
                    <small class="muted">GWA must be equal to or better (lower) than this value.</small>
                </div>
                <div class="form-group">
                    <label for="maximum_family_income">Maximum Family Income (₱)</label>
                    <input type="number" id="maximum_family_income" name="maximum_family_income" step="0.01" min="0" value="{{ old('maximum_family_income', $scholarship->maximum_family_income) }}" placeholder="e.g. 300000">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="eligible_programs_text">Eligible Programs (comma-separated)</label>
                    <input type="text" id="eligible_programs_text" name="eligible_programs_text"
                           value="{{ old('eligible_programs_text', is_array($scholarship->eligible_programs) ? implode(', ', $scholarship->eligible_programs) : '') }}"
                           placeholder="e.g. BSIT, BSCS, BSCE">
                    <small class="muted">Leave blank to open to all programs.</small>
                </div>
                <div class="form-group">
                    <label for="eligible_year_levels_text">Eligible Year Levels (comma-separated)</label>
                    <input type="text" id="eligible_year_levels_text" name="eligible_year_levels_text"
                           value="{{ old('eligible_year_levels_text', is_array($scholarship->eligible_year_levels) ? implode(', ', $scholarship->eligible_year_levels) : '') }}"
                           placeholder="e.g. 2, 3, 4">
                    <small class="muted">Leave blank to open to all year levels.</small>
                </div>
                <div class="form-group">
                    <label for="required_enrollment_status">Required Enrollment Status</label>
                    <select id="required_enrollment_status" name="required_enrollment_status">
                        <option value="">— Not Required —</option>
                        @foreach (\App\Enums\EnrollmentStatus::cases() as $es)
                            <option value="{{ $es->value }}" {{ old('required_enrollment_status', $scholarship->required_enrollment_status) === $es->value ? 'selected' : '' }}>
                                {{ $es->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Application Settings</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="application_start_date">Start Date *</label>
                    <input type="date" id="application_start_date" name="application_start_date" value="{{ old('application_start_date', $scholarship->application_start_date?->format('Y-m-d')) }}" required>
                </div>
                <div class="form-group">
                    <label for="application_deadline">Deadline *</label>
                    <input type="date" id="application_deadline" name="application_deadline" value="{{ old('application_deadline', $scholarship->application_deadline?->format('Y-m-d')) }}" required>
                </div>
            </div>
        </fieldset>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>
@endsection