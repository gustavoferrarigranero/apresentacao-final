<?php

namespace App\Actions\Restaurants;

use App\Domains\Restaurants\Exceptions\RestaurantException;
use App\Domains\Users\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use App\Repositories\RestaurantRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Executa o caso de uso `DeactivateRestaurantAction` no domínio de restaurantes, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class DeactivateRestaurantAction
{
    public function __construct(private RestaurantRepository $restaurantRepository) {}

    public function execute(User $actor, int $restaurantId): Restaurant
    {
        if (! in_array($actor->role, [UserRole::SUPER_ADMIN->value, UserRole::RESTAURANT_ADMIN->value], true)) {
            throw new AuthorizationException;
        }

        try {
            return DB::transaction(function () use ($actor, $restaurantId): Restaurant {
                $scopeActor = $actor->role === UserRole::SUPER_ADMIN->value ? null : $actor;
                $restaurant = $this->restaurantRepository->findForManagementOrFail($scopeActor, $restaurantId);

                return $this->restaurantRepository->deactivate($restaurant);
            });
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RestaurantException::deactivationFailed($exception);
        }
    }
}
