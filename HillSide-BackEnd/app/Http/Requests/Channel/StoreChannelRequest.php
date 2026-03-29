<?php

namespace App\Http\Requests\Channel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            /** WhatsApp Cloud API: Phone number ID from Meta (metadata.phone_number_id in webhooks). */
            'whatsappPhoneNumberId' => ['nullable', 'string', 'max:255'],
            /** WhatsApp Business Account ID (WABA) nga Meta Business Manager. */
            'whatsappBusinessAccountId' => ['nullable', 'string', 'max:255'],
            /** Numër telefoni për shfaqje (opsional, për CRM). */
            'whatsappDisplayPhoneNumber' => ['nullable', 'string', 'max:255'],
            'viberBotId' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($this->input('platform') !== 'whatsapp') {
                return;
            }
            $pid = $this->input('whatsappPhoneNumberId');
            $page = $this->input('platformPageId');
            $phone = is_string($pid) ? trim($pid) : '';
            $pageTrim = is_string($page) ? trim($page) : '';
            if ($phone === '' && $pageTrim === '') {
                $v->errors()->add('whatsappPhoneNumberId', 'Phone Number ID është i detyrueshëm për WhatsApp.');
            }
        });
    }
}
