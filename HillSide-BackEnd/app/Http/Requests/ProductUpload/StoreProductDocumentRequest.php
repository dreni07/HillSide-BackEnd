<?php

namespace App\Http\Requests\ProductUpload;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductDocumentRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:pdf,csv,txt,xlsx,ods',
            ],
        ];
    }
}
