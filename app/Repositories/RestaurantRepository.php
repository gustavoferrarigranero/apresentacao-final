<?php

namespace App\Repositories;

use App\Domains\Restaurants\Data\CreateRestaurantData;
use App\Domains\Restaurants\Enums\RestaurantStatus;
use App\Domains\Restaurants\Interfaces\RestaurantInterface;
use App\Domains\Users\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

/**
 * Centraliza a leitura e a persistência dos dados do domínio de restaurantes, evitando consultas à base de dados espalhadas pelos casos de uso.
 */
final class RestaurantRepository Implements RestaurantInterface
{
    public function paginateForUser(User $user, ?int $id, ?string $name, int $perPage): LengthAwarePaginator
    {
        return Restaurant::query()
            ->whereKey($user->restaurant_id)
            ->when($id !== null, fn ($query) => $query->whereKey($id))
            ->when($name !== null && $name !== '', fn ($query) => $query->whereLike('name', "%{$name}%"))
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateAll(?int $id, ?string $name, int $perPage): LengthAwarePaginator
    {
        return Restaurant::query()
            ->when($id !== null, fn ($query) => $query->whereKey($id))
            ->when($name !== null && $name !== '', fn ($query) => $query->whereLike('name', "%{$name}%"))
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginatePublic(?int $id, ?string $name, int $perPage): LengthAwarePaginator
    {
        return Restaurant::query()
            ->where('status', RestaurantStatus::ACTIVE->value)
            ->when($id !== null, fn ($query) => $query->whereKey($id))
            ->when($name !== null && $name !== '', fn ($query) => $query->whereLike('name', "%{$name}%"))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(CreateRestaurantData $data): Restaurant
    {
        $restaurant = new Restaurant([
            'name' => $data->name,
            'address' => $data->address,
            'phone_number' => $data->phoneNumber,
            'nif' => $data->nif,
        ]);

        if (! $restaurant->save()) {
            throw new RuntimeException('A gravação do restaurante foi interrompida.');
        }

        $restaurantAdmin = new User([
            'name' => $data->adminName,
            'email' => $data->adminEmail,
            'password' => $data->adminPassword,
            'role' => UserRole::RESTAURANT_ADMIN->value,
            'status' => 'active',
        ]);
        $restaurantAdmin->restaurant()->associate($restaurant);

        if (! $restaurantAdmin->save()) {
            throw new RuntimeException('A criação do administrador do restaurante foi interrompida.');
        }

        return $restaurant->refresh();
    }

    public function findForUserOrFail(User $user, int $restaurantId): Restaurant
    {
        return Restaurant::query()
            ->whereKey($user->restaurant_id)
            ->findOrFail($restaurantId);
    }

    public function findActiveOrFail(int $restaurantId): Restaurant
    {
        return Restaurant::query()
            ->where('status', RestaurantStatus::ACTIVE->value)
            ->findOrFail($restaurantId);
    }

    public function findForManagementOrFail(?User $actor, int $restaurantId): Restaurant
    {
        if ($actor === null) {
            return Restaurant::query()->findOrFail($restaurantId);
        }

        return $this->findForUserOrFail($actor, $restaurantId);
    }

    /**
     * @param array{name?: string, address?: string, phone_number?: string, nif?: string, status?: string} $attributes
     */
    public function update(Restaurant $restaurant, array $attributes): Restaurant
    {
        $restaurant->fill($attributes);

        if (! $restaurant->save()) {
            throw new RuntimeException('A atualização do restaurante foi interrompida.');
        }

        return $restaurant->refresh();
    }

    public function deactivate(Restaurant $restaurant): Restaurant
    {
        $restaurant->status = RestaurantStatus::INACTIVE->value;

        if (! $restaurant->save()) {
            throw new RuntimeException('A desativação do restaurante foi interrompida.');
        }

        return $restaurant->refresh();
    }
}
