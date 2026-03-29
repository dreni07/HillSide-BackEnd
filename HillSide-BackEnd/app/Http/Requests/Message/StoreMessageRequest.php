<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('senderType') && ! $this->has('sender_type')) {
            $this->merge(['sender_type' => $this->input('senderType')]);
        }
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string'],
            'sender_type' => ['nullable', 'string', 'in:human_agent,ai'],
        ];
    }
}
