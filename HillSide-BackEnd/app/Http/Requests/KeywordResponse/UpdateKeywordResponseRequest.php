<?php

namespace App\Http\Requests\KeywordResponse;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKeywordResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['sometimes', 'string', 'max:255'],
            'responseText' => ['sometimes', 'string'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}

