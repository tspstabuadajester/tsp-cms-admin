<?php

namespace App\Http\Requests\User;

use App\Models\Business;
use App\Models\User;
use App\Support\AssignableUserRoles;
use App\Support\BusinessUserScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UpdateUserRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('business_id') === '') {
            $this->merge(['business_id' => null]);
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->route('user')),
            ],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'business_id' => $this->businessIdRules(),
        ];

        if (! $this->route('user')?->hasRole('super-admin')) {
            $rules['role'] = ['required', 'string', AssignableUserRoles::validationRule()];
        }

        return $rules;
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
            Rule::when(
                filled($this->input('business_id'))
                    && (int) $this->input('business_id') === $this->route('user')?->business_id,
                Rule::exists(Business::class, 'id'),
                Rule::exists(Business::class, 'id')->where('status', 'active'),
            ),
        ];
    }
}
