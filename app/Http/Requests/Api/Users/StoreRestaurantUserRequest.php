<?php

namespace App\Http\Requests\Api\Users;

use App\Domains\Users\Data\CreateStaffData;
use App\Domains\Users\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Define e valida os dados de entrada aceites por `StoreRestaurantUserRequest` antes de permitir que o controlador execute a operação.
 */
class StoreRestaurantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::RESTAURANT_ADMIN->value;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ];
    }

    public function staffData(): CreateStaffData
    {
        return CreateStaffData::from($this->validated());
    }
}
