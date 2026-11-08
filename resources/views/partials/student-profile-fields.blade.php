@php
    $isAdmin = $isAdmin ?? false;
@endphp

<fieldset>
    <legend>Personal Information</legend>

    <div class="form-row">
        <div class="form-group">
            <label for="first_name">First Name <span class="field-required">*</span></label>
            <input type="text" id="first_name" name="first_name" maxlength="100"
                   value="{{ old('first_name', $profile->first_name) }}" required>
            <x-form-error field="first_name" />
        </div>

        <div class="form-group">
            <label for="middle_name">Middle Name</label>
            <input type="text" id="middle_name" name="middle_name" maxlength="100"
                   value="{{ old('middle_name', $profile->middle_name) }}">
            <x-form-error field="middle_name" />
        </div>

        <div class="form-group">
            <label for="last_name">Last Name <span class="field-required">*</span></label>
            <input type="text" id="last_name" name="last_name" maxlength="100"
                   value="{{ old('last_name', $profile->last_name) }}" required>
            <x-form-error field="last_name" />
        </div>
    </div>
</fieldset>

<fieldset>
    <legend>Academic Information</legend>

    <div class="form-row">
        <div class="form-group">
            <label for="student_number">Student Number @if ($isAdmin)<span class="field-required">*</span>@endif</label>
            <input type="text" id="student_number" name="student_number" maxlength="20"
                   value="{{ old('student_number', $profile->student_number) }}"
                   @if ($isAdmin)
                       required
                   @else
                       readonly aria-describedby="student-number-hint"
                   @endif>
            @unless ($isAdmin)
                <small class="field-hint" id="student-number-hint">Managed by OSAS. Contact the office to correct it.</small>
            @endunless
            <x-form-error field="student_number" />
        </div>

        <div class="form-group">
            <label for="year_level">Year Level @if ($isAdmin)<span class="field-required">*</span>@endif</label>
            @if ($isAdmin)
                <select id="year_level" name="year_level" required>
                    @for ($level = 1; $level <= 6; $level++)
                        <option value="{{ $level }}" @selected((int) old('year_level', $profile->year_level) === $level)>
                            Year {{ $level }}
                        </option>
                    @endfor
                </select>
            @else
                <input type="text" id="year_level" value="Year {{ $profile->year_level }}"
                       readonly aria-describedby="year-level-hint">
                <small class="field-hint" id="year-level-hint">Managed by OSAS. Contact the office to correct it.</small>
            @endif
            <x-form-error field="year_level" />
        </div>

        @if ($isAdmin)
            <div class="form-group">
                <label for="enrollment_status">Enrollment Status <span class="field-required">*</span></label>
                <select id="enrollment_status" name="enrollment_status" required>
                    @foreach ($enrollmentStatuses as $status)
                        <option value="{{ $status->value }}"
                                @selected(old('enrollment_status', $profile->enrollment_status ?? 'unknown') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                <x-form-error field="enrollment_status" />
            </div>
        @endif
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="college">College</label>
            @if ($isAdmin)
                <input type="text" id="college" name="college" maxlength="150"
                       value="{{ old('college', $profile->college) }}">
            @else
                <input type="text" id="college" value="{{ $profile->college ?: '—' }}"
                       readonly aria-describedby="college-hint">
                <small class="field-hint" id="college-hint">Managed by OSAS. Contact the office to correct it.</small>
            @endif
            <x-form-error field="college" />
        </div>

        <div class="form-group">
            <label for="program">Program</label>
            @if ($isAdmin)
                <input type="text" id="program" name="program" maxlength="150"
                       value="{{ old('program', $profile->program) }}">
            @else
                <input type="text" id="program" value="{{ $profile->program ?: '—' }}"
                       readonly aria-describedby="program-hint">
                <small class="field-hint" id="program-hint">Managed by OSAS. Contact the office to correct it.</small>
            @endif
            <x-form-error field="program" />
        </div>
    </div>
</fieldset>

<fieldset>
    <legend>Contact &amp; Financial Information</legend>

    <div class="form-row">
        <div class="form-group">
            <label for="contact_number">Contact Number</label>
            <input type="text" id="contact_number" name="contact_number" maxlength="20"
                   value="{{ old('contact_number', $profile->contact_number) }}">
            <x-form-error field="contact_number" />
        </div>

        <div class="form-group">
            <label for="gwa">General Weighted Average (GWA)</label>
            <input type="number" id="gwa" name="gwa" step="0.01" min="1" max="5"
                   value="{{ old('gwa', $profile->gwa) }}">
            <small class="field-hint">Use the ISU scale: 1.00 is the highest, 5.00 is the lowest.</small>
            <x-form-error field="gwa" />
        </div>

        <div class="form-group">
            <label for="family_income">Monthly Family Income</label>
            <input type="number" id="family_income" name="family_income" step="0.01" min="0"
                   value="{{ old('family_income', $profile->family_income) }}">
            <x-form-error field="family_income" />
        </div>
    </div>

    <div class="form-group">
        <label for="address">Address</label>
        <textarea id="address" name="address" rows="3" maxlength="1000">{{ old('address', $profile->address) }}</textarea>
        <x-form-error field="address" />
    </div>
</fieldset>
