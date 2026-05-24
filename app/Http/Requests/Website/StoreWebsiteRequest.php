<?php

namespace App\Http\Requests\Website;

use App\Models\Business;
use App\Models\Website;
use App\Support\BusinessUserScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebsiteRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['primary_domain', 'business_id'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($businessId = BusinessUserScope::scopedBusinessId($this->user())) {
            $this->merge(['business_id' => $businessId]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique(Website::class)],
            'primary_domain' => ['nullable', 'string', 'max:255', Rule::unique(Website::class)],
            'business_id' => $this->businessIdRules(),
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function businessIdRules(): array
    {
        if ($businessId = BusinessUserScope::scopedBusinessId($this->user())) {
            return ['required', 'integer', Rule::in([$businessId])];
        }

        return [
            'nullable',
            'integer',
            Rule::exists(Business::class, 'id')->where('status', 'active'),
        ];
    }
}
