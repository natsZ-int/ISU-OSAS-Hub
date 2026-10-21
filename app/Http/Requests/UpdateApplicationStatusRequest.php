<?php

namespace App\Http\Requests;

use App\Models\ScholarshipApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ScholarshipApplication::ALLOWED_STATUSES)],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
