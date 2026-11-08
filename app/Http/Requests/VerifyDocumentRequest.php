<?php

namespace App\Http\Requests;

use App\Models\ApplicationDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'verification_status' => ['required', Rule::in([
                ApplicationDocument::VERIFICATION_PENDING,
                ApplicationDocument::VERIFICATION_VERIFIED,
                ApplicationDocument::VERIFICATION_REJECTED,
            ])],
            'verification_remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
