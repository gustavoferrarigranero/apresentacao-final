<?php

namespace App\Domains\Categories\Actions;

use App\Domains\Categories\Data\CategoryFiltersData;
use App\Domains\Categories\Exceptions\CategoryException;
use App\Models\Category;
use App\Domains\Categories\Repositories\CategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Throwable;

/**
 * Executa o caso de uso `ListCategoriesAction` no domínio de categorias, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class ListCategoriesAction
{
    public function __construct(private CategoryRepository $categoryRepository)
    {
    }

    public function execute(int $restaurantId, CategoryFiltersData $filters): LengthAwarePaginator
    {
        try {
            return $this->categoryRepository->paginate(
                restaurantId: $restaurantId,
                id: $filters->id,
                name: $filters->name,
                perPage: $filters->perPage,
            );
        } catch (Throwable $exception) {
            throw CategoryException::retrievalFailed($exception);
        }
    }

    public function find(int $restaurantId, int $categoryId): Category
    {
        return $this->categoryRepository->findOrFail($restaurantId, $categoryId);
    }
}
