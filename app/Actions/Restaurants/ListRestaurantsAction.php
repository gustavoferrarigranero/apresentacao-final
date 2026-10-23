<?php

namespace App\Actions\Restaurants;

use App\Domains\Restaurants\Data\RestaurantFiltersData;
use App\Domains\Restaurants\Exceptions\RestaurantException;
use App\Models\Restaurant;
use App\Models\User;
use App\Repositories\RestaurantRepository;
use App\UseCases\UserCase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

/**
 * Executa o caso de uso `ListRestaurantsAction` no domínio de restaurantes, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class ListRestaurantsAction
{
    public function __construct(private RestaurantRepository $restaurantRepository) {}

    public function execute(?User $user, RestaurantFiltersData $filters): LengthAwarePaginator
    {
        try {
            if ($user === null || UserCase::isCustomer($user)) {
                return $this->restaurantRepository->paginatePublic(
                    $filters->id,
                    $filters->name,
                    $filters->perPage,
                );
            }

            if (UserCase::isSuperAdmin($user)) {
                return $this->restaurantRepository->paginateAll($filters->id, $filters->name, $filters->perPage);
            }

            return $this->restaurantRepository->paginateForUser($user, $filters->id, $filters->name, $filters->perPage);
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RestaurantException::retrievalFailed($exception);
        }
    }

    public function find(User $user, int $restaurantId): Restaurant
    {
        try {
            return $this->restaurantRepository->findForUserOrFail($user, $restaurantId);
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RestaurantException::retrievalFailed($exception);
        }
    }

    public function findPublic(int $restaurantId): Restaurant
    {
        try {
            return $this->restaurantRepository->findActiveOrFail($restaurantId);
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RestaurantException::retrievalFailed($exception);
        }
    }
}
