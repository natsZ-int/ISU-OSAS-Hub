<?php

namespace App\Http\Requests;

use App\Enums\ScholarshipApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && $this->user()?->isActive();
    }

    public function rules(): array
    {
        $validStates = array_column(ScholarshipApplicationStatus::cases(), 'value');

        return [
            'status'  => ['required', Rule::in($validStates)],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}