<?php

namespace App\Domains\Products\Interfaces;

use App\Models\Produt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProdutoInterface
{
    /** @return LengthAwarePaginator<int, Produt> */
    public function paginateForRestaurant(int $restaurantId, ?string $name, int $perPage, bool $activeOnly = false): LengthAwarePaginator;

    public function findForRestaurantOrFail(int $restaurantId, int $productId, bool $activeOnly = false): Produt;

    /** @param array<string, mixed> $attributes */
    public function create(int $restaurantId, array $attributes): Produt;

    /** @param array<string, mixed> $attributes */
    public function update(int $restaurantId, int $productId, array $attributes): Produt;

    public function deactivate(int $restaurantId, int $productId): Produt;
}
