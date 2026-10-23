<?php

namespace App\Domains\Restaurants\Interfaces;

use App\Domains\Restaurants\Data\CreateRestaurantData;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RestaurantInterface
{
    /** @return LengthAwarePaginator<int, Restaurant> */
    public function paginateForUser(User $user, ?int $id, ?string $name, int $perPage): LengthAwarePaginator;

    /** @return LengthAwarePaginator<int, Restaurant> */
    public function paginateAll(?int $id, ?string $name, int $perPage): LengthAwarePaginator;

    /** @return LengthAwarePaginator<int, Restaurant> */
    public function paginatePublic(?int $id, ?string $name, int $perPage): LengthAwarePaginator;

    public function create(CreateRestaurantData $data): Restaurant;

    public function findForUserOrFail(User $user, int $restaurantId): Restaurant;

    public function findActiveOrFail(int $restaurantId): Restaurant;

    public function findForManagementOrFail(?User $actor, int $restaurantId): Restaurant;

    /** @param array{name?: string, address?: string, phone_number?: string, nif?: string, status?: string} $attributes */
    public function update(Restaurant $restaurant, array $attributes): Restaurant;

    public function deactivate(Restaurant $restaurant): Restaurant;
}
