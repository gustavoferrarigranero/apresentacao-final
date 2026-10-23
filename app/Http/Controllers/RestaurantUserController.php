<?php

namespace App\Http\Controllers;

use App\Actions\Users\CreateRestaurantStaffAction;
use App\Http\Requests\Api\Users\StoreRestaurantUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Recebe pedidos HTTP em `RestaurantUserController`, delega o processamento à camada de domínio e devolve respostas para a interface ou para o cliente da API.
 */
class RestaurantUserController extends Controller
{
    public function __construct(private readonly CreateRestaurantStaffAction $createRestaurantStaffAction)
    {
    }

    public function store(StoreRestaurantUserRequest $request): JsonResponse
    {
        $user = $this->createRestaurantStaffAction->execute($request->user(), $request->staffData());

        return (new UserResource($user))
            ->additional(['message' => 'Utilizador criado para o restaurante.'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
