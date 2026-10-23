<?php

namespace App\Http\Controllers;

use App\Domains\Categories\Data\CategoryFiltersData;
use App\Domains\Categories\Data\CreateCategoryData;
use App\Domains\Categories\Data\EditCategoryData;
use App\Http\Requests\Api\Categories\CreateCategoryRequest;
use App\Http\Requests\Api\Categories\EditCategoryRequest;
use App\Http\Requests\Api\Categories\IndexCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Recebe pedidos HTTP em `CategoryController`, delega o processamento à camada de domínio e devolve respostas para a interface ou para o cliente da API.
 */
class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(IndexCategoryRequest $request): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            $this->categoryService->index(
                (int) $request->user()->restaurant_id,
                CategoryFiltersData::from($request->validated()),
            )
        );
    }

    public function store(CreateCategoryRequest $categoryRequest): JsonResponse
    {
        $category = $this->categoryService->store(
            (int) $categoryRequest->user()->restaurant_id,
            CreateCategoryData::from($categoryRequest->validated()),
        );

        return (new CategoryResource($category))
            ->additional(['message' => 'Categoria criada com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $category): CategoryResource
    {
        return new CategoryResource(
            $this->categoryService->show((int) $request->user()->restaurant_id, $category)
        );
    }

    public function update(EditCategoryRequest $editCategoryRequest, int $category): CategoryResource
    {
        $updatedCategory = $this->categoryService->update(
            (int) $editCategoryRequest->user()->restaurant_id,
            EditCategoryData::from($editCategoryRequest->validated()),
            $category
        );

        return (new CategoryResource($updatedCategory))
            ->additional(['message' => 'Categoria atualizada com sucesso.']);
    }

    public function destroy(Request $request, int $category): CategoryResource
    {
        $deactivatedCategory = $this->categoryService->delete(
            (int) $request->user()->restaurant_id,
            $category,
        );

        return (new CategoryResource($deactivatedCategory))
            ->additional(['message' => 'Categoria desativada com sucesso.']);
    }
}
