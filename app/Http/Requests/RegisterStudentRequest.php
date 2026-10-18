<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_number'   => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/', 'unique:student_profiles,student_number'],
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'college'          => ['required', 'string', 'max:150'],
            'program'          => ['required', 'string', 'max:150'],
            'contact_number'   => ['required', 'string', 'max:20'],
            'gwa'              => ['required', 'numeric', 'min:1.00', 'max:5.00'],
            'family_income'    => ['required', 'numeric', 'min:0'],
            'address'          => ['required', 'string', 'max:1000'],
            'privacy_accepted' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_number.regex'     => 'The student ID may only contain letters, numbers, and dashes.',
            'student_number.unique'    => 'This student ID is already registered.',
            'email.unique'             => 'An account with this email address already exists.',
            'password.min'             => 'The password must be at least 8 characters.',
            'password.confirmed'       => 'The password confirmation does not match.',
            'gwa.min'                  => 'The GWA must be between 1.00 and 5.00 (1.00 = highest).',
            'gwa.max'                  => 'The GWA must be between 1.00 and 5.00 (1.00 = highest).',
            'family_income.min'        => 'The family income must be zero or greater.',
            'address.max'              => 'The address may not exceed 1000 characters.',
            'privacy_accepted.accepted' => 'You must accept the privacy notice to create an account.',
        ];
    }
}
