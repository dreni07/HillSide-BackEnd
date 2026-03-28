<?php

namespace App\Http\Requests\ProductUpload;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductImageRequest extends FormRequest
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
                'max:15360',
                'mimes:jpeg,jpg,png,gif,webp,bmp,tiff',
            ],
        ];
    }
}
