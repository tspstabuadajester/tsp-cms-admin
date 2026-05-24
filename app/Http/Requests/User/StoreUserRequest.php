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

class StoreUserRequest extends FormRequest
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', AssignableUserRoles::validationRule()],
        ];

        $rules['business_id'] = $this->businessIdRules();

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
            Rule::exists(Business::class, 'id')->where('status', 'active'),
        ];
    }
}
