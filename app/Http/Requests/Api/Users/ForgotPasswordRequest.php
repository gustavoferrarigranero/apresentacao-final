<?php

namespace App\Http\Requests\Api\Users;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Define e valida os dados de entrada aceites por `ForgotPasswordRequest` antes de permitir que o controlador execute a operação.
 */
class ForgotPasswordRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return ['email' => ['required', 'email']];
    }
}
