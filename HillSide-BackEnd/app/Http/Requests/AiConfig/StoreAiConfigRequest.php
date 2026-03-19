<?php

namespace App\Http\Requests\AiConfig;

use App\Enums\AiResponseStyle;
use App\Enums\AiTone;
use App\Enums\SalesApproach;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'personality' => ['required', 'array'],
            'personality.tone' => ['required', Rule::enum(AiTone::class)],
            'personality.response_style' => ['required', Rule::enum(AiResponseStyle::class)],
            'personality.language' => ['sometimes', 'string', 'max:10'],
            'personality.greeting_message' => ['nullable', 'string', 'max:1000'],
            'personality.farewell_message' => ['nullable', 'string', 'max:1000'],
            'personality.custom_instructions' => ['nullable', 'string', 'max:5000'],

            'restrictions' => ['required', 'array'],
            'restrictions.allowed_topics' => ['nullable', 'array'],
            'restrictions.allowed_topics.*' => ['string', 'max:255'],
            'restrictions.restricted_topics' => ['nullable', 'array'],
            'restrictions.restricted_topics.*' => ['string', 'max:255'],
            'restrictions.blocked_words' => ['nullable', 'array'],
            'restrictions.blocked_words.*' => ['string', 'max:100'],
            'restrictions.max_response_length' => ['nullable', 'integer', 'min:50', 'max:10000'],
            'restrictions.content_guidelines' => ['nullable', 'string', 'max:5000'],

            'salesman' => ['required', 'array'],
            'salesman.sales_approach' => ['required', Rule::enum(SalesApproach::class)],
            'salesman.upsell_enabled' => ['sometimes', 'boolean'],
            'salesman.product_knowledge' => ['nullable', 'string', 'max:5000'],
            'salesman.pricing_info' => ['nullable', 'string', 'max:5000'],
            'salesman.call_to_action' => ['nullable', 'string', 'max:1000'],
            'salesman.objection_handling' => ['nullable', 'string', 'max:5000'],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'personality.required' => 'Personality configuration is required.',
            'personality.tone.required' => 'AI tone is required.',
            'personality.tone' => 'Invalid AI tone. Allowed: ' . implode(', ', array_column(AiTone::cases(), 'value')),
            'personality.response_style.required' => 'AI response style is required.',
            'personality.response_style' => 'Invalid response style. Allowed: ' . implode(', ', array_column(AiResponseStyle::cases(), 'value')),

            'restrictions.required' => 'Restrictions configuration is required.',
            'restrictions.allowed_topics.array' => 'Allowed topics must be a list.',
            'restrictions.restricted_topics.array' => 'Restricted topics must be a list.',
            'restrictions.blocked_words.array' => 'Blocked words must be a list.',
            'restrictions.max_response_length.min' => 'Max response length must be at least 50 characters.',
            'restrictions.max_response_length.max' => 'Max response length cannot exceed 10,000 characters.',

            'salesman.required' => 'Salesman configuration is required.',
            'salesman.sales_approach.required' => 'Sales approach is required.',
            'salesman.sales_approach' => 'Invalid sales approach. Allowed: ' . implode(', ', array_column(SalesApproach::cases(), 'value')),
        ];
    }
}
