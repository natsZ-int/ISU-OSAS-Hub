<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent();
    }

    public function rules(): array
    {
        return [
            'scholarship_requirement_id' => ['required', 'integer', 'exists:scholarship_requirements,id'],
            'document'                   => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120', // 5 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document.mimes' => 'Allowed file types: PDF, JPG, JPEG, PNG.',
            'document.max'   => 'Maximum file size is 5 MB.',
        ];
    }
}