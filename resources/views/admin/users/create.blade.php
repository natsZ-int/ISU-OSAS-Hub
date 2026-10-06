@extends('layouts.app')
@section('title', 'Create User — ISU OSAS Hub')

@section('content')
<div class="page-header">
    <h2>Create New User</h2>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-dark">← Back to Users</a>
</div>

@if ($errors->any())
    <div class="alert alert-error">
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="card">
    <form method="POST" action="{{ route('admin.users.store') }}" id="createUserForm">
        @csrf

        <fieldset>
            <legend>Account Information</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="role_id">Role *</label>
                    <select id="role_id" name="role_id" required onchange="toggleStudentFields()">
                        <option value="">Select Role</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->id }}" {{ old('role_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->label ?? ucfirst($r->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        Active Account
                    </label>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required>
                </div>
            </div>
        </fieldset>

        <fieldset id="studentFields" style="display:none;">
            <legend>Student Profile</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="student_number">Student Number *</label>
                    <input type="text" id="student_number" name="student_number" value="{{ old('student_number') }}">
                </div>
                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select id="year_level" name="year_level">
                        <option value="">Select</option>
                        @for ($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}" {{ old('year_level') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}">
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="college">College</label>
                    <input type="text" id="college" name="college" value="{{ old('college') }}">
                </div>
                <div class="form-group">
                    <label for="program">Program</label>
                    <input type="text" id="program" name="program" value="{{ old('program') }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}">
                </div>
                <div class="form-group">
                    <label for="gwa">GWA</label>
                    <input type="number" id="gwa" name="gwa" step="0.01" min="1" max="5" value="{{ old('gwa') }}">
                </div>
                <div class="form-group">
                    <label for="family_income">Family Income</label>
                    <input type="number" id="family_income" name="family_income" step="0.01" min="0" value="{{ old('family_income') }}">
                </div>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="2">{{ old('address') }}</textarea>
            </div>
        </fieldset>

        <button type="submit" class="btn btn-primary">Create User</button>
    </form>
</div>

<script>
function toggleStudentFields() {
    const roleId = document.getElementById('role_id').value;
    const studentRoleId = '{{ $roles->where("name", "student")->first()?->id }}';
    document.getElementById('studentFields').style.display =
        (roleId === studentRoleId) ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleStudentFields);
</script>
@endsection