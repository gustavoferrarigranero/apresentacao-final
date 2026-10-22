<?php

namespace App\Http\Requests\Api\Users;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Define e valida os dados de entrada aceites por `ForgotPasswordRequest` antes de permitir que o controlador execute a operação.
 */
class ForgotPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return ['email' => ['required', 'email']];
    }
}
