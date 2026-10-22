<?php

namespace App\Domains\Products\Actions;

use App\Domains\Products\Exceptions\ProductException;
use App\Domains\Products\Repositories\ProductRepository;
use App\Models\Produt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

/**
 * Executa o caso de uso `ListProductsAction` no domínio de produtos, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class ListProductsAction
{
    public function __construct(private ProductRepository $productRepository)
    {
    }

    public function execute(int $restaurantId, ?string $name, int $perPage, bool $activeOnly): LengthAwarePaginator
    {
        try {
            return $this->productRepository->paginateForRestaurant($restaurantId, $name, $perPage, $activeOnly);
        } catch (Throwable $exception) {
            throw ProductException::retrievalFailed($exception);
        }
    }

    public function find(int $restaurantId, int $productId, bool $activeOnly): Produt
    {
        try {
            return $this->productRepository->findForRestaurantOrFail($restaurantId, $productId, $activeOnly);
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ProductException::retrievalFailed($exception);
        }
    }
}
