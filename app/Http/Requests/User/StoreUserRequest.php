<?php

namespace App\Http\Requests\User;

use App\Models\Business;
use App\Models\User;
use App\Support\AssignableUserRoles;
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'business_id' => [
                'nullable',
                'integer',
                Rule::exists(Business::class, 'id')->where('status', 'active'),
            ],
            'role' => ['required', 'string', AssignableUserRoles::validationRule()],
        ];
    }
}
