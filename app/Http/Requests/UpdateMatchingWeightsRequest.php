<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchingWeightsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && $this->user()?->isActive();
    }

    public function rules(): array
    {
        return [
            'academic_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'financial_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'program_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'year_level_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'document_readiness_weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $total = (float) $this->input('academic_weight', 0)
                   + (float) $this->input('financial_weight', 0)
                   + (float) $this->input('program_weight', 0)
                   + (float) $this->input('year_level_weight', 0)
                   + (float) $this->input('document_readiness_weight', 0);

            if (abs($total - 100.0) > 0.01) {
                $validator->errors()->add('total', "Weights must total exactly 100%. Current total: {$total}%.");
            }
        });
    }
}
