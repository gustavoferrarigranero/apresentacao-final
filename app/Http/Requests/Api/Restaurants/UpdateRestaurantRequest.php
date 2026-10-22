<?php

namespace App\Http\Requests\Api\Restaurants;

use App\Domains\Restaurants\Enums\RestaurantStatus;
use App\Domains\Users\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Define e valida os dados de entrada aceites por `UpdateRestaurantRequest` antes de permitir que o controlador execute a operação.
 */
class UpdateRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [
            UserRole::SUPER_ADMIN->value,
            UserRole::RESTAURANT_ADMIN->value,
        ], true);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'required', 'string', 'max:30'],
            'nif' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('restaurants', 'nif')->ignore($this->route('restaurant')),
            ],
            'status' => ['sometimes', 'required', 'string', Rule::enum(RestaurantStatus::class)],
        ];
    }
}
