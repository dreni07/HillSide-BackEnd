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
        if ($this->has('whatsappPhoneNumberId') && ! $this->has('whatsapp_phone_number_id')) {
            $this->merge(['whatsapp_phone_number_id' => $this->input('whatsappPhoneNumberId')]);
        }
        if ($this->has('platformPageId') && ! $this->has('meta_page_id')) {
            $this->merge(['meta_page_id' => $this->input('platformPageId')]);
        }
        if ($this->has('viberBotId') && ! $this->has('viber_bot_id')) {
            $this->merge(['viber_bot_id' => $this->input('viberBotId')]);
        }
        if ($this->has('accessToken') && ! $this->has('meta_access_token')) {
            $this->merge(['meta_access_token' => $this->input('accessToken')]);
        }
        if ($this->has('whatsappBusinessAccountId') && ! $this->has('whatsapp_business_account_id')) {
            $this->merge(['whatsapp_business_account_id' => $this->input('whatsappBusinessAccountId')]);
        }
        if ($this->has('whatsappDisplayPhoneNumber') && ! $this->has('whatsapp_display_phone_number')) {
            $this->merge(['whatsapp_display_phone_number' => $this->input('whatsappDisplayPhoneNumber')]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:50'],
            'ai_instructions' => ['sometimes', 'nullable', 'string'],
            'whatsapp_phone_number_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_page_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'viber_bot_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_access_token' => ['sometimes', 'nullable', 'string', 'min:1'],
            'whatsapp_business_account_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'whatsapp_display_phone_number' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
