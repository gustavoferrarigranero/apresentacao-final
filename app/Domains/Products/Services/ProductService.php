<?php

namespace App\Domains\Products\Services;

use App\Domains\Products\Data\ProductData;
use App\Domains\Products\Actions\CreateProductAction;
use App\Domains\Products\Actions\DeactivateProductAction;
use App\Domains\Products\Actions\ListProductsAction;
use App\Domains\Products\Actions\UpdateProductAction;
use App\Models\Produt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Coordena as regras e operações do domínio de produtos, oferecendo aos controladores uma interface para os casos de uso.
 */
final readonly class ProductService
{
    public function __construct(
        private ListProductsAction $listProductsAction,
        private CreateProductAction $createProductAction,
        private UpdateProductAction $updateProductAction,
        private DeactivateProductAction $deactivateProductAction,
    ) {
    }

    public function index(int $restaurantId, ?string $name, int $perPage, bool $activeOnly = false): LengthAwarePaginator
    {
        return $this->listProductsAction->execute($restaurantId, $name, $perPage, $activeOnly);
    }

    public function show(int $restaurantId, int $productId, bool $activeOnly = false): Produt
    {
        return $this->listProductsAction->find($restaurantId, $productId, $activeOnly);
    }

    public function store(int $restaurantId, ProductData $data): Produt
    {
        return $this->createProductAction->execute($restaurantId, $data);
    }

    public function update(int $restaurantId, int $productId, ProductData $data): Produt
    {
        return $this->updateProductAction->execute($restaurantId, $productId, $data);
    }

    public function delete(int $restaurantId, int $productId): Produt
    {
        return $this->deactivateProductAction->execute($restaurantId, $productId);
    }
}
