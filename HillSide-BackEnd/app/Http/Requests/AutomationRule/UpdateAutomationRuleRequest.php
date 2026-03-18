<?php

namespace App\Http\Requests\AutomationRule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAutomationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'conditions' => ['sometimes', 'nullable', 'array'],
            'action' => ['sometimes', 'nullable', 'array'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}

