<?php

namespace App\Domains\Restaurants\Actions;

use App\Domains\Restaurants\Data\UpdateRestaurantData;
use App\Domains\Restaurants\Exceptions\RestaurantException;
use App\Models\Restaurant;
use App\Models\User;
use App\Domains\Restaurants\Repositories\RestaurantRepository;
use App\Domains\Users\Enums\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Executa o caso de uso `UpdateRestaurantAction` no domínio de restaurantes, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class UpdateRestaurantAction
{
    public function __construct(private RestaurantRepository $restaurantRepository)
    {
    }

    public function execute(User $actor, int $restaurantId, UpdateRestaurantData $data): Restaurant
    {
        if (! in_array($actor->role, [UserRole::SUPER_ADMIN->value, UserRole::RESTAURANT_ADMIN->value], true)) {
            throw new AuthorizationException();
        }

        try {
            return DB::transaction(function () use ($actor, $restaurantId, $data): Restaurant {
                $scopeActor = $actor->role === UserRole::SUPER_ADMIN->value ? null : $actor;
                $restaurant = $this->restaurantRepository->findForManagementOrFail($scopeActor, $restaurantId);

                return $this->restaurantRepository->update($restaurant, $data->attributes());
            });
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RestaurantException::updateFailed($exception);
        }
    }
}
