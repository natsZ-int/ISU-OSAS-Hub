<?php

namespace App\Http\Requests;

use App\Enums\EnrollmentStatus;
use App\Models\Scholarship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScholarshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title'                      => ['required', 'string', 'max:255'],
            'description'                => ['required', 'string', 'max:5000'],
            'provider'                   => ['required', 'string', 'max:255'],
            'scholarship_type'           => ['required', 'string', 'max:100'],
            'minimum_gwa'                => ['nullable', 'numeric', 'min:1.00', 'max:5.00'],
            'maximum_family_income'      => ['nullable', 'numeric', 'min:0'],
            'eligible_programs'          => ['nullable', 'array'],
            'eligible_programs.*'        => ['string', 'max:100'],
            'eligible_year_levels'       => ['nullable', 'array'],
            'eligible_year_levels.*'     => ['integer', 'min:1', 'max:6'],
            'required_enrollment_status' => ['nullable', Rule::in(EnrollmentStatus::values())],
            'application_start_date'     => ['required', 'date'],
            'application_deadline'       => ['required', 'date', 'after_or_equal:application_start_date'],
            'status'                     => ['required', Rule::in(Scholarship::ALLOWED_STATUSES)],
        ];
    }
}