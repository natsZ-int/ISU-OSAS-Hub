<?php

namespace App\Http\Requests;

use App\Models\ServiceType;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    public function rules(): array
    {
        $serviceTypeId = $this->input('service_type_id');
        $serviceType = ServiceType::with('requirements')->find($serviceTypeId);

        $rules = [
            'service_type_id' => ['required', 'exists:service_types,id,is_active,1'],
            'description'     => ['required', 'string', 'min:10', 'max:5000'],
        ];

        if ($serviceType) {
            foreach ($serviceType->requirements as $req) {
                $field = "requirement_{$req->id}";
                $rules[$field] = [
                    $req->is_required ? 'required' : 'nullable',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:5120', // 5MB limit
                ];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'service_type_id.exists' => 'The selected service type is currently unavailable or inactive.',
            'description.min'        => 'Please provide a clear explanation of your request (at least 10 characters).',
            '*.mimes'                => 'Supporting documents must be in PDF, JPG, JPEG, or PNG format.',
            '*.max'                  => 'Supporting documents must not exceed 5 MB in size.',
        ];
    }
}