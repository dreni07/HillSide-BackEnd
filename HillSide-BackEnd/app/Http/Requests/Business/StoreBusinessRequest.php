<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBusinessRequest extends FormRequest
{
    private const DESCRIPTION_MIN = 10;

    private const ADDRESS_MIN = 5;

    private const PHONE_MIN_DIGITS = 8;

    private const PHONE_MAX_DIGITS = 15;

    /**
     * Përputhet me listën e zonave në CRM (`TIMEZONES` në onboarding).
     *
     * @var list<string>
     */
    private const ALLOWED_TIMEZONES = [
        'Europe/Tirane',
        'Europe/London',
        'Europe/Berlin',
        'Europe/Rome',
        'Europe/Zurich',
        'America/New_York',
        'America/Chicago',
        'America/Los_Angeles',
        'Asia/Istanbul',
        'Asia/Dubai',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $keys = ['name', 'description', 'phone', 'email', 'address', 'website', 'timezone'];
        $merged = [];
        foreach ($keys as $key) {
            if ($this->has($key) && is_string($this->input($key))) {
                $merged[$key] = trim($this->input($key));
            }
        }
        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['required', 'string', 'min:'.self::DESCRIPTION_MIN, 'max:2000'],
            'phone' => ['required', 'string', 'max:50', $this->phoneDigitsRule()],
            'email' => ['required', 'string', 'email', 'max:255'],
            'address' => ['required', 'string', 'min:'.self::ADDRESS_MIN, 'max:500'],
            'logo' => ['nullable', 'string', 'max:500'],
            'website' => ['required', 'string', 'regex:/^https?:\/\/.+/i', 'max:500'],
            'timezone' => ['required', 'string', Rule::in(self::ALLOWED_TIMEZONES)],
        ];
    }

    /**
     * @return \Closure(string, mixed, \Closure(string): void): void
     */
    private function phoneDigitsRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $trimmed = trim((string) $value);
            if ($trimmed === '') {
                $fail('Telefoni është i detyrueshëm.');

                return;
            }
            $digits = preg_replace('/\D/', '', $trimmed) ?? '';
            if (strlen($digits) < self::PHONE_MIN_DIGITS) {
                $fail('Vendosni një numër telefoni të vlefshëm (të paktën '.self::PHONE_MIN_DIGITS.' shifra).');

                return;
            }
            if (strlen($digits) > self::PHONE_MAX_DIGITS) {
                $fail('Numri i telefonit është shumë i gjatë.');
            }
        };
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Emri i kompanisë është i detyrueshëm.',
            'name.min' => 'Emri duhet të ketë të paktën 2 karaktere.',
            'name.max' => 'Emri i kompanisë nuk mund të kalojë 255 karaktere.',
            'description.required' => 'Përshkrimi i kompanisë është i detyrueshëm.',
            'description.min' => 'Përshkrimi duhet të ketë të paktën '.self::DESCRIPTION_MIN.' karaktere.',
            'description.max' => 'Përshkrimi nuk mund të kalojë 2000 karaktere.',
            'email.required' => 'Email i biznesit është i detyrueshëm.',
            'email.email' => 'Ju lutem vendosni një email të vlefshëm.',
            'address.required' => 'Adresa është e detyrueshme.',
            'address.min' => 'Adresa duhet të ketë të paktën '.self::ADDRESS_MIN.' karaktere.',
            'address.max' => 'Adresa nuk mund të kalojë 500 karaktere.',
            'website.required' => 'Website është i detyrueshëm.',
            'website.regex' => 'URL duhet të fillojë me http:// ose https://',
            'website.max' => 'Website nuk mund të kalojë 500 karaktere.',
            'timezone.required' => 'Ju lutem zgjidhni zonën kohore.',
            'timezone.in' => 'Zona kohore nuk është e vlefshme.',
        ];
    }
}
