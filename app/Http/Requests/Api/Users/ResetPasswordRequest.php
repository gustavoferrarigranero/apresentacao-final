<?php

namespace App\Http\Requests\Api\Users;

use App\Domains\Users\Data\PasswordResetData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Define e valida os dados de entrada aceites por `ResetPasswordRequest` antes de permitir que o controlador execute a operação.
 */
class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ];
    }

    public function passwordReset(): PasswordResetData
    {
        return new PasswordResetData(
            email: $this->validated('email'),
            token: $this->validated('token'),
            password: $this->validated('password'),
        );
    }
}
