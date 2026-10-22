<?php

namespace App\Http\Requests\Api\Restaurants;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Define e valida os dados de entrada aceites por `IndexRestaurantRequest` antes de permitir que o controlador execute a operação.
 */
class IndexRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'id' => ['sometimes', 'integer', 'min:1'],
            'name' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
