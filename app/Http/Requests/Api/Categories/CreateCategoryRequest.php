<?php

namespace App\Http\Requests\Api\Categories;

use App\Domains\Categories\Enums\CategoryEnums;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Define e valida os dados de entrada aceites por `CreateCategoryRequest` antes de permitir que o controlador execute a operação.
 */
class CreateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                Rule::unique('categories', 'name')
                    ->where('restaurant_id', $this->user()?->restaurant_id),
            ],
            'status' => ['sometimes', 'string', Rule::enum(CategoryEnums::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'O campo nome deve ter pelo menos 3 caracteres',
            'name.required' => 'O campo nome é obrigatorio',
            'name.unique' => 'Já foi cadastrada uma categoria com o mesmo nome',
            'status.enum' => 'O campo status deve ser um valor valido',
        ];
    }
}
