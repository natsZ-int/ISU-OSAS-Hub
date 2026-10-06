<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'                        => ['required', 'string', 'max:150', 'unique:service_types,name'],
            'description'                 => ['nullable', 'string', 'max:2000'],
            'instructions'                => ['nullable', 'string', 'max:3000'],
            'is_active'                   => ['required', 'boolean'],
            'display_order'               => ['required', 'integer', 'min:0'],
            'requirements'                => ['nullable', 'array'],
            'requirements.*.name'         => ['required', 'string', 'max:150'],
            'requirements.*.description'  => ['nullable', 'string', 'max:500'],
            'requirements.*.is_required'  => ['required', 'boolean'],
            'requirements.*.display_order'=> ['required', 'integer', 'min:0'],
        ];
    }
}