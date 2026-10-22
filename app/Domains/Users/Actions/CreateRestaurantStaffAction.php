<?php

namespace App\Domains\Users\Actions;

use App\Domains\Users\Data\CreateStaffData;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Executa o caso de uso `CreateRestaurantStaffAction` no domínio de utilizadores, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class CreateRestaurantStaffAction
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function execute(User $actor, CreateStaffData $data): User
    {
        if ($actor->role !== UserRole::RESTAURANT_ADMIN->value || $actor->restaurant_id === null) {
            throw new AuthorizationException();
        }

        return $this->userRepository->createRestaurantStaff((int) $actor->restaurant_id, $data);
    }
}
