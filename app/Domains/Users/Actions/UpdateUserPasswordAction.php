<?php

namespace App\Domains\Users\Actions;

use App\Domains\Users\Repositories\UserRepository;
use App\Models\User;

/**
 * Executa o caso de uso `UpdateUserPasswordAction` no domínio de utilizadores, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class UpdateUserPasswordAction
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function execute(User $user, string $password): void
    {
        $this->userRepository->updatePassword($user, $password);
    }
}
