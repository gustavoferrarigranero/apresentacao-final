<?php

namespace App\Domains\Categories\Actions;

use App\Domains\Categories\Data\CreateCategoryData;
use App\Domains\Categories\Exceptions\CategoryException;
use App\Models\Category;
use App\Domains\Categories\Repositories\CategoryRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Executa o caso de uso `CreateCategoryAction` no domínio de categorias, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class CreateCategoryAction
{
    public function __construct(private CategoryRepository $categoryRepository)
    {
    }

    public function execute(int $restaurantId, CreateCategoryData $createCategoryData): Category
    {
        try {
            return DB::transaction(fn (): Category => $this->categoryRepository->create([
                'restaurant_id' => $restaurantId,
                'name' => $createCategoryData->name,
                'status' => $createCategoryData->status,
            ]));
        } catch (Throwable $exception) {
            throw CategoryException::creationFailed($exception);
        }
    }
}
