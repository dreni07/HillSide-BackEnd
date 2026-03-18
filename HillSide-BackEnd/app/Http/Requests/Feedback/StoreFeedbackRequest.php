<?php

namespace App\Http\Requests\Feedback;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conversationId' => ['required', 'integer', 'exists:conversations,id'],
            'messageId' => ['nullable', 'integer', 'exists:messages,id'],
            'sentiment' => ['nullable', 'string', 'max:50'],
            'reasonCategory' => ['nullable', 'string', 'max:100'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ];
    }
}

