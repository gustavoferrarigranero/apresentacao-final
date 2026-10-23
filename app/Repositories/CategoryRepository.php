<?php

namespace App\Repositories;

use App\Domains\Categories\Enums\CategoryEnums;
use App\Domains\Categories\Interfaces\CategoriaInterface;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

/**
 * Centraliza a leitura e a persistência dos dados do domínio de categorias, evitando consultas à base de dados espalhadas pelos casos de uso.
 */
final class CategoryRepository implements CategoriaInterface
{
    /**
     * @param  array{name: string, status: string, restaurant_id: int}  $data
     */
    public function create(array $data): Category
    {
        $category = new Category($data);

        if (! $category->save()) {
            throw new RuntimeException('A gravação da categoria foi interrompida.');
        }

        return $category;
    }

    /**
     * @param  array{name?: string, status?: string}  $data
     */
    public function update(array $data, Category $category): Category
    {
        $category->fill($data);

        if (! $category->save()) {
            throw new RuntimeException('A atualização da categoria foi interrompida.');
        }

        return $category->refresh();
    }

    public function deactivate(Category $category): Category
    {
        $category->status = CategoryEnums::INACTIVE->value;

        if (! $category->save()) {
            throw new RuntimeException('A desativação da categoria foi interrompida.');
        }

        return $category->refresh();
    }

    /** @return LengthAwarePaginator<int, Category> */
    public function paginate(int $restaurantId, ?int $id, ?string $name, int $perPage): LengthAwarePaginator
    {
        return Category::query()
            ->where('restaurant_id', $restaurantId)
            ->when($id !== null, fn ($query) => $query->whereKey($id))
            ->when($name !== null && $name !== '', fn ($query) => $query->whereLike('name', "%{$name}%"))
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findOrFail(int $restaurantId, int $id): Category
    {
        return Category::query()
            ->where('restaurant_id', $restaurantId)
            ->findOrFail($id);
    }
}
