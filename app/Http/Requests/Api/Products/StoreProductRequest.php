<?php

namespace App\Http\Requests\Api\Products;

use App\Domains\Categories\Enums\CategoryEnums;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Define e valida os dados de entrada aceites por `StoreProductRequest` antes de permitir que o controlador execute a operação.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['restaurant_admin', 'staff'], true);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'quantity' => ['required', 'integer', 'min:0'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('restaurant_id', $this->user()?->restaurant_id),
            ],
            'status' => ['sometimes', 'string', Rule::enum(CategoryEnums::class)],
        ];
    }
}
