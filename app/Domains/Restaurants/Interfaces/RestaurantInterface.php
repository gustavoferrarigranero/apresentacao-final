<?php

namespace App\Domains\Restaurants\Interfaces;

use App\Domains\Restaurants\Data\CreateRestaurantData;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RestaurantInterface
{
    public function paginateForUser(User $user, ?int $id, ?string $name, int $perPage): LengthAwarePaginator;

    public function paginateAll(?int $id, ?string $name, int $perPage): LengthAwarePaginator;

    public function paginatePublic(?int $id, ?string $name, int $perPage): LengthAwarePaginator;

    public function create(CreateRestaurantData $data): Restaurant;

    public function findForUserOrFail(User $user, int $restaurantId): Restaurant;

    public function findActiveOrFail(int $restaurantId): Restaurant;

    public function findForManagementOrFail(?User $actor, int $restaurantId): Restaurant;

    public function update(Restaurant $restaurant, array $attributes): Restaurant;

    public function deactivate(Restaurant $restaurant): Restaurant;
}
