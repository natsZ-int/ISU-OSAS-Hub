<?php

namespace App\Http\Requests;

use App\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $isSystemAdmin = (bool) $this->user()?->isSystemAdmin();
        $currentRoleId = $this->route('user')?->role_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId),
                function ($attribute, $value, $fail) {
                    $target = $this->route('user');

                    if ($target?->isSystemAdmin() && strcasecmp((string) $value, (string) $target->email) !== 0) {
                        $fail('The System Administrator e-mail cannot be changed.');
                    }
                },
            ],
            // Only the seeded System Administrator may change roles. For other
            // admins the role can be omitted or resubmitted unchanged.
            'role_id' => $isSystemAdmin
                ? ['required', 'integer', Rule::exists('roles', 'id')]
                : ['nullable', 'integer', Rule::exists('roles', 'id'), Rule::in(array_filter([$currentRoleId]))],
            'is_active' => ['sometimes', 'boolean'],

            'student_number' => ['nullable', 'string', 'max:20', Rule::unique('student_profiles', 'student_number')->ignore($userId, 'user_id')],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'college' => ['nullable', 'string', 'max:150'],
            'program' => ['nullable', 'string', 'max:150'],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:6'],
            'gwa' => ['nullable', 'numeric', 'min:1.00', 'max:5.00'],
            'family_income' => ['nullable', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:1000'],
            'enrollment_status' => ['nullable', Rule::in(EnrollmentStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.in' => 'Only the System Administrator can change a user\'s role.',
        ];
    }
}
