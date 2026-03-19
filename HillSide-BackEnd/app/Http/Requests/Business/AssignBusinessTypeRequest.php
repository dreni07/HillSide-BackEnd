<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class AssignBusinessTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_type_id' => [
                'required',
                'integer',
                'exists:business_types,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'business_type_id.required' => 'You must select a business type.',
            'business_type_id.exists' => 'The selected business type does not exist.',
        ];
    }
}
