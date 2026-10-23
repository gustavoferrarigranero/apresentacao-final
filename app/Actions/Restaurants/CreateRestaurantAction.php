<?php

namespace App\Actions\Restaurants;

use App\Domains\Restaurants\Data\CreateRestaurantData;
use App\Domains\Restaurants\Exceptions\RestaurantException;
use App\Models\Restaurant;
use App\Repositories\RestaurantRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Executa o caso de uso `CreateRestaurantAction` no domínio de restaurantes, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class CreateRestaurantAction
{
    public function __construct(private RestaurantRepository $restaurantRepository)
    {
    }

    public function execute(CreateRestaurantData $data): Restaurant
    {
        try {
            return DB::transaction(fn (): Restaurant => $this->restaurantRepository->create($data));
        } catch (Throwable $exception) {
            throw RestaurantException::creationFailed($exception);
        }
    }
}
