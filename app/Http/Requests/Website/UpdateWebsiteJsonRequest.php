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
            'sections.*.fields' => ['present', 'array'],
            'sections.*.fields.*.path' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || $value === '' || Str::contains($value, '..')) {
                        $label = $this->attributes()[$attribute] ?? null;
                        $fail($label ? "{$label} is not allowed." : 'This field name is not allowed.');
                    }
                },
            ],
            'sections.*.fields.*.value' => ['present', 'string'],
            'sections.*.arrays' => ['present', 'array'],
            'sections.*.arrays.*.key' => ['required', 'string'],
            'sections.*.arrays.*.items' => ['present', 'array'],
            'sections.*.arrays.*.items.*.fields' => ['present', 'array'],
            'sections.*.arrays.*.items.*.fields.*.key' => ['required', 'string'],
            'sections.*.arrays.*.items.*.fields.*.value' => ['present', 'string'],
            'sections.*.arrays.*.items.*.hidden' => ['present', 'array'],
            'sections.*.arrays.*.items.*.hidden.*.key' => ['required', 'string'],
            'sections.*.arrays.*.items.*.hidden.*.value' => ['present', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sections.required' => 'Content sections are missing. Please reload the editor and try again.',
            'sections.*.key.required' => 'A section name is missing.',
            'sections.*.fields.*.path.required' => ':attribute is missing a name.',
            'sections.*.fields.*.value.present' => ':attribute is required.',
            'sections.*.fields.*.value.string' => ':attribute must be text.',
            'sections.*.arrays.*.key.required' => 'A list name is missing in :attribute.',
            'sections.*.arrays.*.items.*.fields.*.key.required' => ':attribute is missing a name.',
            'sections.*.arrays.*.items.*.fields.*.value.present' => ':attribute is required.',
            'sections.*.arrays.*.items.*.fields.*.value.string' => ':attribute must be text.',
            'sections.*.arrays.*.items.*.hidden.*.value.present' => ':attribute is required.',
            'sections.*.arrays.*.items.*.hidden.*.value.string' => ':attribute must be text.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->input('sections', []) as $sectionIndex => $section) {
            if (! is_array($section)) {
                continue;
            }

            $sectionLabel = $this->label((string) ($section['key'] ?? 'Section'));

            foreach ($section['fields'] ?? [] as $fieldIndex => $field) {
                if (! is_array($field)) {
                    continue;
                }

                $fieldLabel = $this->label((string) ($field['path'] ?? 'Field'));

                $attributes["sections.{$sectionIndex}.fields.{$fieldIndex}.path"] = "{$sectionLabel} → {$fieldLabel}";
                $attributes["sections.{$sectionIndex}.fields.{$fieldIndex}.value"] = "{$sectionLabel} → {$fieldLabel}";
            }

            foreach ($section['arrays'] ?? [] as $arrayIndex => $array) {
                if (! is_array($array)) {
                    continue;
                }

                $arrayLabel = $this->label((string) ($array['key'] ?? 'List'));

                foreach ($array['items'] ?? [] as $itemIndex => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $itemNumber = (int) $itemIndex + 1;
                    $itemPrefix = "{$sectionLabel} → {$arrayLabel} #{$itemNumber}";

                    foreach ($item['fields'] ?? [] as $fieldIndex => $field) {
                        if (! is_array($field)) {
                            continue;
                        }

                        $fieldLabel = $this->label((string) ($field['key'] ?? 'Field'));

                        $attributes["sections.{$sectionIndex}.arrays.{$arrayIndex}.items.{$itemIndex}.fields.{$fieldIndex}.key"] = "{$itemPrefix} → {$fieldLabel}";
                        $attributes["sections.{$sectionIndex}.arrays.{$arrayIndex}.items.{$itemIndex}.fields.{$fieldIndex}.value"] = "{$itemPrefix} → {$fieldLabel}";
                    }

                    foreach ($item['hidden'] ?? [] as $fieldIndex => $field) {
                        if (! is_array($field)) {
                            continue;
                        }

                        $fieldLabel = $this->label((string) ($field['key'] ?? 'Field'));

                        $attributes["sections.{$sectionIndex}.arrays.{$arrayIndex}.items.{$itemIndex}.hidden.{$fieldIndex}.value"] = "{$itemPrefix} → {$fieldLabel}";
                    }
                }
            }
        }

        return $attributes;
    }

    private function label(string $key): string
    {
        if ($key === '') {
            return 'Field';
        }

        return Str::headline(str_replace(['.', '_', '-'], ' ', $key));
    }
}
