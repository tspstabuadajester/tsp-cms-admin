<?php

namespace App\Http\Requests\Website;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateWebsiteJsonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sections' => ['required', 'array'],
            'sections.*.key' => ['required', 'string'],
            'sections.*.fields' => ['required', 'array'],
            'sections.*.fields.*.path' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || $value === '' || Str::contains($value, '..')) {
                        $fail('The field path is invalid.');
                    }
                },
            ],
            'sections.*.fields.*.value' => ['present', 'string'],
        ];
    }
}
