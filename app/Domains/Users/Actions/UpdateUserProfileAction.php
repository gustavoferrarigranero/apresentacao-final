<?php

namespace App\Domains\Users\Actions;

use App\Domains\Users\Repositories\UserRepository;
use App\Models\User;

/**
 * Executa o caso de uso `UpdateUserProfileAction` no domínio de utilizadores, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class UpdateUserProfileAction
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /**
     * @param array{name?: string, email?: string} $attributes
     */
    public function execute(User $user, array $attributes): User
    {
        return $this->userRepository->updateProfile($user, $attributes);
    }
}
