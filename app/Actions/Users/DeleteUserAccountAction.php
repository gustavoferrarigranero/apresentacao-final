<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Executa o caso de uso `DeleteUserAccountAction` no domínio de utilizadores, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class DeleteUserAccountAction
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function execute(User $user): void
    {
        $this->userRepository->delete($user);
    }
}
