<?php

namespace App\Http\Requests\Api\Products;

use App\Domains\Categories\Enums\CategoryEnums;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Define e valida os dados de entrada aceites por `UpdateProductRequest` antes de permitir que o controlador execute a operação.
 */
class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['restaurant_admin', 'staff'], true);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $restaurantId = $this->user()?->restaurant_id;

        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'max:100'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId),
            ],
            'status' => ['sometimes', 'required', 'string', Rule::enum(CategoryEnums::class)],
        ];
    }
}
