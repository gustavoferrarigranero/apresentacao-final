<?php

namespace App\Domains\Categories\Interfaces;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoriaInterface
{
    /** @param array{name: string, status: string, restaurant_id: int} $attributes */
    public function create(array $attributes): Category;

    /** @param array{name?: string, status?: string} $attributes */
    public function update(array $attributes, Category $category): Category;

    public function deactivate(Category $category): Category;

    /** @return LengthAwarePaginator<int, Category> */
    public function paginate(int $restaurantId, ?int $id, ?string $name, int $perPage): LengthAwarePaginator;

    public function findOrFail(int $restaurantId, int $id): Category;
}
