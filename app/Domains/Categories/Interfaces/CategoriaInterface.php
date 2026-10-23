<?php

namespace App\Domains\Categories\Interfaces;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoriaInterface
{
    public function create(array $attributes): Category;

    public function update(array $attributes, Category $category): Category;

    public function deactivate(Category $category): Category;

    public function paginate(int $restaurantId, ?int $id, ?string $name, int $perPage): LengthAwarePaginator;

    public function findOrFail(int $restaurantId, int $id): Category;
}
