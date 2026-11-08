<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['sometimes', 'boolean'],

            // Student profile fields (required only when role is student)
            'student_number' => [
                Rule::requiredIf(fn () => $this->isStudentRole()),
                'nullable', 'string', 'max:20', 'unique:student_profiles,student_number',
            ],
            'first_name' => [Rule::requiredIf(fn () => $this->isStudentRole()), 'nullable', 'string', 'max:100'],
            'last_name' => [Rule::requiredIf(fn () => $this->isStudentRole()), 'nullable', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'college' => ['nullable', 'string', 'max:150'],
            'program' => ['nullable', 'string', 'max:150'],
            'year_level' => ['nullable', 'integer', 'min:1', 'max:6'],
            'gwa' => ['nullable', 'numeric', 'min:1.00', 'max:5.00'],
            'family_income' => ['nullable', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function isStudentRole(): bool
    {
        $studentRole = Role::where('name', Role::STUDENT)->first();

        return $studentRole && (int) $this->input('role_id') === $studentRole->id;
    }

    public function messages(): array
    {
        return [
            'student_number.required' => 'Student number is required when creating a student account.',
            'first_name.required' => 'First name is required when creating a student account.',
            'last_name.required' => 'Last name is required when creating a student account.',
        ];
    }
}
