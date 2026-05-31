<?php

namespace App\Http\Requests\Website;

use App\Models\Business;
use App\Models\Website;
use App\Support\BusinessUserScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebsiteRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
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
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(Website::class)->ignore($this->route('website')),
            ],
            'primary_domain' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Website::class)->ignore($this->route('website')),
            ],
            'business_id' => $this->businessIdRules(),
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'logo' => ['nullable', 'image', 'max:2048'],
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
            'required',
            'integer',
            Rule::when(
                filled($this->input('business_id'))
                    && (int) $this->input('business_id') === $this->route('website')?->business_id,
                Rule::exists(Business::class, 'id'),
                Rule::exists(Business::class, 'id')->where('status', 'active'),
            ),
        ];
    }
}
