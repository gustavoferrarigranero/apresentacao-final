<?php

namespace App\Http\Requests\Api\Users;

use App\Domains\Users\Data\LoginCredentialsData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Define e valida os dados de entrada aceites por `LoginRequest` antes de permitir que o controlador execute a operação.
 */
class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function credentials(): LoginCredentialsData
    {
        return new LoginCredentialsData(
            email: $this->validated('email'),
            password: $this->validated('password'),
        );
    }
}
