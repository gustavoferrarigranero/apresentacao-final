<?php

namespace App\Actions\Categories;

use App\Domains\Categories\Exceptions\CategoryException;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Executa o caso de uso `DeleteCategoryAction` no domínio de categorias, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class DeleteCategoryAction
{
    public function __construct(private CategoryRepository $categoryRepository) {}

    public function execute(int $restaurantId, int $categoryId): Category
    {
        try {
            return DB::transaction(function () use ($restaurantId, $categoryId): Category {
                $category = $this->categoryRepository->findOrFail($restaurantId, $categoryId);

                return $this->categoryRepository->deactivate($category);
            });
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw CategoryException::deletionFailed($exception);
        }
    }
}
