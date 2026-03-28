<?php

namespace App\Http\Requests\Channel;

use Illuminate\Foundation\Http\FormRequest;

class StoreChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Body from CRM uses camelCase (accessToken, platformPageId, viberBotId).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'in:instagram,facebook,whatsapp,viber'],
            'accessToken' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'platformPageId' => ['nullable', 'string', 'max:255'],
            'viberBotId' => ['nullable', 'string', 'max:255'],
        ];
    }
}
