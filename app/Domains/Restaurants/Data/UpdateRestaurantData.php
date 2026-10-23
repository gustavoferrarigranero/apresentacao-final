<?php

namespace App\Domains\Restaurants\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Define os dados usados pelo caso de uso `UpdateRestaurantData` no domínio de restaurantes; funciona como contrato interno entre validação e lógica de negócio.
 */
#[MapName(SnakeCaseMapper::class)]
final class UpdateRestaurantData extends Data
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $address = null,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $nif = null,
        public readonly ?string $status = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return array_filter([
            'name' => $this->name,
            'address' => $this->address,
            'phone_number' => $this->phoneNumber,
            'nif' => $this->nif,
            'status' => $this->status,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
