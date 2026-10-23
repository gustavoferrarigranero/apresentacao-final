<?php

namespace App\Http\Controllers;

use App\Domains\Restaurants\Data\CreateRestaurantData;
use App\Domains\Restaurants\Data\RestaurantFiltersData;
use App\Domains\Restaurants\Data\UpdateRestaurantData;
use App\Http\Requests\Api\Restaurants\CreateRestaurantRequest;
use App\Http\Requests\Api\Restaurants\IndexRestaurantRequest;
use App\Http\Requests\Api\Restaurants\UpdateRestaurantRequest;
use App\Http\Resources\RestaurantResource;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Recebe pedidos HTTP em `RestaurantController`, delega o processamento à camada de domínio e devolve respostas para a interface ou para o cliente da API.
 */
class RestaurantController extends Controller
{
    public function __construct(private readonly RestaurantService $restaurantService) {}

    public function index(IndexRestaurantRequest $request): AnonymousResourceCollection
    {
        return RestaurantResource::collection(
            $this->restaurantService->index(
                $request->user(),
                RestaurantFiltersData::from($request->validated()),
            )
        );
    }

    public function store(CreateRestaurantRequest $request): JsonResponse
    {
        $restaurant = $this->restaurantService->store(
            CreateRestaurantData::from($request->validated()),
        );

        return (new RestaurantResource($restaurant))
            ->additional(['message' => 'Restaurante criado com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $restaurant): RestaurantResource
    {
        return new RestaurantResource($this->restaurantService->showPublic($restaurant));
    }

    public function update(UpdateRestaurantRequest $request, int $restaurant): RestaurantResource
    {
        return (new RestaurantResource(
            $this->restaurantService->update(
                $request->user(),
                $restaurant,
                UpdateRestaurantData::from($request->validated()),
            )
        ))->additional(['message' => 'Restaurante atualizado com sucesso.']);
    }

    public function destroy(Request $request, int $restaurant): RestaurantResource
    {
        return (new RestaurantResource(
            $this->restaurantService->delete(
                $request->user(),
                $restaurant,
            )
        ))->additional(['message' => 'Restaurante desativado com sucesso.']);
    }
}
