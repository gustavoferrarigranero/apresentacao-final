<?php

namespace App\Http\Requests\Api\Users;

use App\Domains\Users\Data\CustomerRegistrationData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Define e valida os dados de entrada aceites por `RegisterCustomerRequest` antes de permitir que o controlador execute a operação.
 */
class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ];
    }

    public function registrationData(): CustomerRegistrationData
    {
        return CustomerRegistrationData::fromValidated($this->validated());
    }
}
