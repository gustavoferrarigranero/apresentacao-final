<?php

namespace App\Services;

use App\Actions\Categories\CreateCategoryAction;
use App\Actions\Categories\DeleteCategoryAction;
use App\Actions\Categories\EditCategoryAction;
use App\Actions\Categories\ListCategoriesAction;
use App\Domains\Categories\Data\CategoryFiltersData;
use App\Domains\Categories\Data\CreateCategoryData;
use App\Domains\Categories\Data\EditCategoryData;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Coordena as regras e operações do domínio de categorias, oferecendo aos controladores uma interface para os casos de uso.
 */
final readonly class CategoryService
{
    public function __construct(
        private CreateCategoryAction $createCategoryAction,
        private EditCategoryAction $editCategoryAction,
        private DeleteCategoryAction $deleteCategoryAction,
        private ListCategoriesAction $listCategoriesAction,
    ) {}

    /** @return LengthAwarePaginator<int, Category> */
    public function index(int $restaurantId, CategoryFiltersData $filters): LengthAwarePaginator
    {
        return $this->listCategoriesAction->execute($restaurantId, $filters);
    }

    public function store(int $restaurantId, CreateCategoryData $createCategoryData): Category
    {
        return $this->createCategoryAction->execute($restaurantId, $createCategoryData);
    }

    public function show(int $restaurantId, int $categoryId): Category
    {
        return $this->listCategoriesAction->find($restaurantId, $categoryId);
    }

    public function update(int $restaurantId, EditCategoryData $editCategoryData, int $categoryId): Category
    {
        return $this->editCategoryAction->execute($restaurantId, $editCategoryData, $categoryId);
    }

    public function delete(int $restaurantId, int $categoryId): Category
    {
        return $this->deleteCategoryAction->execute($restaurantId, $categoryId);
    }
}
