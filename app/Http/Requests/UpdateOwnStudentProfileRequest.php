<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for a student editing their own profile.
 *
 * Academic and identity fields that are system-managed (student number,
 * college, program, year level, and enrollment status) are intentionally
 * excluded. They are updated only by OSAS staff, either through the admin
 * profile manager or the enrollment verification workflow.
 */
class UpdateOwnStudentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isStudent();
    }

    /**
     * The edit form renders system-managed fields as readonly inputs. Readonly
     * inputs are still submitted alongside the rest of the form, so the stored
     * value would otherwise trip the "prohibited" rule on every save, even when
     * the student only edited their name. Drop the field when it was submitted
     * unchanged; a real attempt to modify it is still rejected.
     */
    protected function prepareForValidation(): void
    {
        $profile = $this->user()?->studentProfile;

        if (! $profile) {
            return;
        }

        foreach (['student_number', 'college', 'program', 'year_level', 'enrollment_status'] as $field) {
            if (! $this->exists($field)) {
                continue;
            }

            $submitted = $this->input($field);
            $current = $profile->getAttribute($field);

            if (
                is_scalar($submitted) && is_scalar($current)
                && trim((string) $submitted) === trim((string) $current)
            ) {
                $this->request->remove($field);
            }
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'gwa' => ['nullable', 'numeric', 'min:1.00', 'max:5.00'],
            'family_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'address' => ['nullable', 'string', 'max:1000'],

            // System-managed fields must not be changed through this form.
            'student_number' => ['prohibited'],
            'college' => ['prohibited'],
            'program' => ['prohibited'],
            'year_level' => ['prohibited'],
            'enrollment_status' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Please enter your first name.',
            'last_name.required' => 'Please enter your last name.',
            'gwa.min' => 'The GWA must be between 1.00 and 5.00 (1.00 = highest).',
            'gwa.max' => 'The GWA must be between 1.00 and 5.00 (1.00 = highest).',
            'family_income.min' => 'The family income must be zero or greater.',
            'address.max' => 'The address may not exceed 1000 characters.',
            'student_number.prohibited' => 'Your student number cannot be changed here. Please contact OSAS.',
            'college.prohibited' => 'Your college cannot be changed here. Please contact OSAS.',
            'program.prohibited' => 'Your program cannot be changed here. Please contact OSAS.',
            'year_level.prohibited' => 'Your year level cannot be changed here. Please contact OSAS.',
            'enrollment_status.prohibited' => 'Your enrollment status cannot be changed here.',
        ];
    }
}
