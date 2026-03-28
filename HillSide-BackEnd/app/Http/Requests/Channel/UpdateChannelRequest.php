<?php

namespace App\Http\Requests\Channel;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('aiInstructions') && ! $this->has('ai_instructions')) {
            $this->merge(['ai_instructions' => $this->input('aiInstructions')]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:50'],
            'ai_instructions' => ['sometimes', 'nullable', 'string'],
        ];
    }
}

