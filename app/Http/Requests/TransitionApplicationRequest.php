<?php

namespace App\Http\Requests;

use App\Enums\ScholarshipApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionApplicationRequest extends FormRequest
{
    /**
     * Accept the common alternate field names used by API-style clients while
     * keeping one canonical validation path.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('status') && $this->filled('to_status')) {
            $this->merge(['status' => $this->input('to_status')]);
        }

        if (! $this->filled('remarks') && $this->filled('reason')) {
            $this->merge(['remarks' => $this->input('reason')]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && $this->user()?->isActive();
    }

    public function rules(): array
    {
        $validStates = array_column(ScholarshipApplicationStatus::cases(), 'value');

        return [
            'status' => ['required', Rule::in($validStates)],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
