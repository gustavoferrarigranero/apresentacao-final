<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Executa o caso de uso `UpdateUserPasswordAction` no domínio de utilizadores, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class UpdateUserPasswordAction
{
    public function __construct(private UserRepository $userRepository) {}

    public function execute(User $user, string $password): void
    {
        $this->userRepository->updatePassword($user, $password);
    }
}
