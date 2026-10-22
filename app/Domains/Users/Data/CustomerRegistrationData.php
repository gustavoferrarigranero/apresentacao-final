<?php

namespace App\Domains\Users\Data;

use Spatie\LaravelData\Data;

/**
 * Define os dados usados pelo caso de uso `CustomerRegistrationData` no domínio de utilizadores; funciona como contrato interno entre validação e lógica de negócio.
 */
final class CustomerRegistrationData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {
    }

    /**
     * @param array{name: string, email: string, password: string} $attributes
     */
    public static function fromValidated(array $attributes): self
    {
        return new self(
            name: $attributes['name'],
            email: $attributes['email'],
            password: $attributes['password'],
        );
    }
}
