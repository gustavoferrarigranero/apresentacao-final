<?php

namespace App\Http\Requests\Api\Restaurants;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Define e valida os dados de entrada aceites por `CreateRestaurantRequest` antes de permitir que o controlador execute a operação.
 */
class CreateRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === \App\Domains\Users\Enums\UserRole::SUPER_ADMIN->value;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:30'],
            'nif' => ['required', 'string', 'max:20', Rule::unique('restaurants', 'nif')],
            'admin_name' => ['required', 'string', 'min:2', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_password' => ['required', 'string', 'min:12'],
        ];
    }
}
