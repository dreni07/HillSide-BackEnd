<?php

namespace App\Http\Requests\KeywordResponse;

use Illuminate\Foundation\Http\FormRequest;

class StoreKeywordResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channelId' => ['required', 'integer', 'exists:channels,id'],
            'keyword' => ['required', 'string', 'max:255'],
            'responseText' => ['required', 'string'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }
}

