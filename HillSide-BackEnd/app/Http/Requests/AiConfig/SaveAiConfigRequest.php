<?php

namespace App\Http\Requests\AiConfig;

use App\Enums\AiResponseStyle;
use App\Enums\AiTone;
use App\Enums\SalesApproach;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAiConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $personality = $this->input('personality', []);
        if (! isset($personality['language']) || $personality['language'] === '') {
            $personality['language'] = 'en';
        }
        $this->merge(['personality' => $personality]);

        $rawQuestions = $this->input('expected_questions', []);
        if (! is_array($rawQuestions)) {
            $this->merge(['expected_questions' => []]);

            return;
        }
        $filtered = [];
        foreach ($rawQuestions as $row) {
            if (! is_array($row)) {
                continue;
            }
            $q = isset($row['question']) ? trim((string) $row['question']) : '';
            $a = isset($row['answer']) ? trim((string) $row['answer']) : '';
            if ($q === '' || $a === '') {
                continue;
            }
            $filtered[] = ['question' => $q, 'answer' => $a];
        }
        $this->merge(['expected_questions' => $filtered]);
    }

    public function rules(): array
    {
        return [
            'personality' => ['required', 'array'],
            'personality.tone' => ['required', Rule::enum(AiTone::class)],
            'personality.response_style' => ['required', Rule::enum(AiResponseStyle::class)],
            'personality.language' => ['required', 'string', 'max:16'],
            'personality.greeting_message' => ['nullable', 'string', 'max:1000'],
            'personality.farewell_message' => ['nullable', 'string', 'max:1000'],
            'personality.custom_instructions' => ['nullable', 'string', 'max:5000'],

            'restrictions' => ['required', 'array'],
            'restrictions.allowed_topics' => ['nullable', 'array', 'max:500'],
            'restrictions.allowed_topics.*' => ['string', 'max:255'],
            'restrictions.restricted_topics' => ['nullable', 'array', 'max:500'],
            'restrictions.restricted_topics.*' => ['string', 'max:255'],
            'restrictions.blocked_words' => ['nullable', 'array', 'max:500'],
            'restrictions.blocked_words.*' => ['string', 'max:100'],
            'restrictions.max_response_length' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'restrictions.content_guidelines' => ['nullable', 'string', 'max:5000'],

            'salesman' => ['required', 'array'],
            'salesman.sales_approach' => ['required', Rule::enum(SalesApproach::class)],
            'salesman.upsell_enabled' => ['sometimes', 'boolean'],
            'salesman.product_knowledge' => ['nullable', 'string', 'max:5000'],
            'salesman.pricing_info' => ['nullable', 'string', 'max:5000'],
            'salesman.call_to_action' => ['nullable', 'string', 'max:1000'],
            'salesman.objection_handling' => ['nullable', 'string', 'max:5000'],

            'expected_questions' => ['sometimes', 'array', 'max:500'],
            'expected_questions.*.question' => ['required', 'string', 'max:2000'],
            'expected_questions.*.answer' => ['required', 'string', 'max:10000'],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'personality.required' => 'Personality configuration is required.',
            'personality.tone' => 'Invalid AI tone.',
            'personality.response_style' => 'Invalid response style.',
            'restrictions.allowed_topics.max' => 'Too many allowed topic entries.',
            'restrictions.restricted_topics.max' => 'Too many restricted topic entries.',
            'restrictions.blocked_words.max' => 'Too many blocked word entries.',
            'expected_questions.max' => 'Too many expected question entries.',
            'salesman.sales_approach' => 'Invalid sales approach.',
        ];
    }
}
