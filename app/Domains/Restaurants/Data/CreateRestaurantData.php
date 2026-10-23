<?php

namespace App\Domains\Restaurants\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Define os dados usados pelo caso de uso `CreateRestaurantData` no domínio de restaurantes; funciona como contrato interno entre validação e lógica de negócio.
 */
#[MapName(SnakeCaseMapper::class)]
final class CreateRestaurantData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $address,
        public readonly string $phoneNumber,
        public readonly string $nif,
        public readonly string $adminName,
        public readonly string $adminEmail,
        public readonly string $adminPassword,
    ) {}
}
