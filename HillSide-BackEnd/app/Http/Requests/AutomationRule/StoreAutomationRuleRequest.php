<?php

namespace App\Http\Requests\AutomationRule;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutomationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channelId' => ['required', 'integer', 'exists:channels,id'],
            'name' => ['required', 'string', 'max:255'],
            'conditions' => ['nullable', 'array'],
            'action' => ['nullable', 'array'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }
}

