<?php

namespace App\Http\Requests;

use App\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for an administrator editing a student profile.
 */
class UpdateStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $profileId = $this->route('studentProfile')?->id;

        return [
            'student_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('student_profiles', 'student_number')->ignore($profileId),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'college' => ['nullable', 'string', 'max:150'],
            'program' => ['nullable', 'string', 'max:150'],
            'year_level' => ['required', 'integer', 'min:1', 'max:6'],
            'enrollment_status' => ['required', Rule::in(EnrollmentStatus::values())],
            'gwa' => ['nullable', 'numeric', 'min:1.00', 'max:5.00'],
            'family_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_number.regex' => 'The student number may only contain letters, numbers, and dashes.',
            'student_number.unique' => 'This student number is already assigned to another student.',
            'gwa.min' => 'The GWA must be between 1.00 and 5.00 (1.00 = highest).',
            'gwa.max' => 'The GWA must be between 1.00 and 5.00 (1.00 = highest).',
            'family_income.min' => 'The family income must be zero or greater.',
            'year_level.min' => 'The year level must be between 1 and 6.',
            'year_level.max' => 'The year level must be between 1 and 6.',
        ];
    }
}
