<?php

namespace App\Http\Controllers;

use App\Domains\Products\Data\ProductData;
use App\Http\Requests\Api\Products\IndexProductRequest;
use App\Http\Requests\Api\Products\StoreProductRequest;
use App\Http\Requests\Api\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Restaurant;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Recebe pedidos HTTP em `ProdutController`, delega o processamento à camada de domínio e devolve respostas para a interface ou para o cliente da API.
 */
class ProdutController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(IndexProductRequest $request, Restaurant $restaurant): AnonymousResourceCollection
    {
        return ProductResource::collection($this->productService->index(
            (int) $restaurant->id,
            $request->validated('name'),
            (int) $request->validated('per_page', 10),
            activeOnly: true,
        ));
    }

    public function show(Restaurant $restaurant, int $product): ProductResource
    {
        return new ProductResource($this->productService->show(
            (int) $restaurant->id,
            $product,
            activeOnly: true,
        ));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->store(
            (int) $request->user()->restaurant_id,
            ProductData::from($request->validated()),
        );

        return (new ProductResource($product))
            ->additional(['message' => 'Produto criado com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, int $product): ProductResource
    {
        return (new ProductResource($this->productService->update(
            (int) $request->user()->restaurant_id,
            $product,
            ProductData::from($request->validated()),
        )))->additional(['message' => 'Produto atualizado com sucesso.']);
    }

    public function destroy(Request $request, int $product): ProductResource
    {
        return (new ProductResource($this->productService->delete(
            (int) $request->user()->restaurant_id,
            $product,
        )))->additional(['message' => 'Produto desativado com sucesso.']);
    }
}
