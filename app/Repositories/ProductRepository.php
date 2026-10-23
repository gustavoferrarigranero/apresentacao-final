<?php

namespace App\Repositories;

use App\Domains\Products\Enums\ProductStatus;
use App\Domains\Products\Interfaces\ProdutoInterface;
use App\Domains\Restaurants\Enums\RestaurantStatus;
use App\Models\Category;
use App\Models\Produt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;

/**
 * Centraliza a leitura e a persistência dos dados do domínio de produtos, evitando consultas à base de dados espalhadas pelos casos de uso.
 */
final class ProductRepository implements ProdutoInterface
{
    /** @return LengthAwarePaginator<int, Produt> */
    public function paginateForRestaurant(int $restaurantId, ?string $name, int $perPage, bool $activeOnly = false): LengthAwarePaginator
    {
        return Produt::query()
            ->where('restaurant_id', $restaurantId)
            ->when($activeOnly, fn ($query) => $query
                ->whereHas('restaurant', fn ($restaurantQuery) => $restaurantQuery->where('status', RestaurantStatus::ACTIVE->value))
                ->where('status', ProductStatus::ACTIVE->value))
            ->when($name !== null && $name !== '', fn ($query) => $query->whereLike('name', "%{$name}%"))
            ->with('category')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForRestaurantOrFail(int $restaurantId, int $productId, bool $activeOnly = false): Produt
    {
        return Produt::query()
            ->where('restaurant_id', $restaurantId)
            ->when($activeOnly, fn ($query) => $query
                ->whereHas('restaurant', fn ($restaurantQuery) => $restaurantQuery->where('status', RestaurantStatus::ACTIVE->value))
                ->where('status', ProductStatus::ACTIVE->value))
            ->with('category')
            ->findOrFail($productId);
    }

    /** @param array<string, mixed> $attributes */
    public function create(int $restaurantId, array $attributes): Produt
    {
        $this->assertCategoryBelongsToRestaurant($restaurantId, $attributes['category_id']);
        $product = new Produt([...$attributes, 'restaurant_id' => $restaurantId]);

        if (! $product->save()) {
            throw new RuntimeException('A gravação do produto foi interrompida.');
        }

        return $product->load('category');
    }

    /** @param array<string, mixed> $attributes */
    public function update(int $restaurantId, int $productId, array $attributes): Produt
    {
        $product = $this->findForRestaurantOrFail($restaurantId, $productId);

        if (isset($attributes['category_id'])) {
            $this->assertCategoryBelongsToRestaurant($restaurantId, $attributes['category_id']);
        }

        $product->fill($attributes);
        if (! $product->save()) {
            throw new RuntimeException('A atualização do produto foi interrompida.');
        }

        return $product->refresh()->load('category');
    }

    public function deactivate(int $restaurantId, int $productId): Produt
    {
        $product = $this->findForRestaurantOrFail($restaurantId, $productId);
        $product->status = ProductStatus::INACTIVE->value;

        if (! $product->save()) {
            throw new RuntimeException('A desativação do produto foi interrompida.');
        }

        return $product->refresh()->load('category');
    }

    private function assertCategoryBelongsToRestaurant(int $restaurantId, int $categoryId): void
    {
        if (! Category::query()
            ->where('restaurant_id', $restaurantId)
            ->whereKey($categoryId)
            ->exists()) {
            throw (new ModelNotFoundException)->setModel(Category::class, $categoryId);
        }
    }
}
