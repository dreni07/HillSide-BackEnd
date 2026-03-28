<?php

namespace App\Http\Requests\ProductUpload;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'string', 'max:32'],
            'sku' => ['nullable', 'string', 'max:128'],
            'category' => ['nullable', 'string', 'max:255'],
            'stock' => ['nullable', 'string', 'max:32'],
            'unit' => ['nullable', 'string', 'max:64'],
            'tags' => ['nullable', 'string', 'max:500'],
        ];
    }
}
