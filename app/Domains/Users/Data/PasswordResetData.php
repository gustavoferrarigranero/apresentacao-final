<?php

namespace App\Domains\Users\Data;

use Spatie\LaravelData\Data;

/**
 * Define os dados usados pelo caso de uso `PasswordResetData` no domínio de utilizadores; funciona como contrato interno entre validação e lógica de negócio.
 */
final class PasswordResetData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $token,
        public readonly string $password,
    ) {}
}
