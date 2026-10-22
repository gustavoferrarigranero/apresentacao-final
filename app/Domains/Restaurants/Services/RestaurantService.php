<?php

namespace App\Domains\Restaurants\Services;

use App\Domains\Restaurants\Actions\CreateRestaurantAction;
use App\Domains\Restaurants\Actions\DeactivateRestaurantAction;
use App\Domains\Restaurants\Actions\ListRestaurantsAction;
use App\Domains\Restaurants\Actions\UpdateRestaurantAction;
use App\Domains\Restaurants\Data\CreateRestaurantData;
use App\Domains\Restaurants\Data\RestaurantFiltersData;
use App\Domains\Restaurants\Data\UpdateRestaurantData;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Coordena as regras e operações do domínio de restaurantes, oferecendo aos controladores uma interface para os casos de uso.
 */
final readonly class RestaurantService
{
    public function __construct(
        private CreateRestaurantAction $createRestaurantAction,
        private ListRestaurantsAction $listRestaurantsAction,
        private UpdateRestaurantAction $updateRestaurantAction,
        private DeactivateRestaurantAction $deactivateRestaurantAction,
    ) {
    }

    public function index(?User $user, RestaurantFiltersData $filters): LengthAwarePaginator
    {
        return $this->listRestaurantsAction->execute($user, $filters);
    }

    public function show(User $user, int $restaurantId): Restaurant
    {
        return $this->listRestaurantsAction->find($user, $restaurantId);
    }

    public function showPublic(int $restaurantId): Restaurant
    {
        return $this->listRestaurantsAction->findPublic($restaurantId);
    }

    public function store(CreateRestaurantData $data): Restaurant
    {
        return $this->createRestaurantAction->execute($data);
    }

    public function update(User $actor, int $restaurantId, UpdateRestaurantData $data): Restaurant
    {
        return $this->updateRestaurantAction->execute($actor, $restaurantId, $data);
    }

    public function delete(User $actor, int $restaurantId): Restaurant
    {
        return $this->deactivateRestaurantAction->execute($actor, $restaurantId);
    }
}
