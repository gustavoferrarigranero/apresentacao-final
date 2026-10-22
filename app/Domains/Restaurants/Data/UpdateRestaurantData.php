<?php

namespace App\Domains\Restaurants\Data;

use Spatie\LaravelData\Data;

/**
 * Define os dados usados pelo caso de uso `UpdateRestaurantData` no domínio de restaurantes; funciona como contrato interno entre validação e lógica de negócio.
 */
final class UpdateRestaurantData extends Data
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $address = null,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $nif = null,
        public readonly ?string $status = null,
    ) {
    }

    /**
     * @return array{name?: string, address?: string, phone_number?: string, nif?: string, status?: string}
     */
    public function attributes(): array
    {
        return array_filter([
            'name' => $this->name,
            'address' => $this->address,
            'phone_number' => $this->phoneNumber,
            'nif' => $this->nif,
            'status' => $this->status,
        ], static fn (?string $value): bool => $value !== null);
    }

    /**
     * @param array{name?: string, address?: string, phone_number?: string, nif?: string, status?: string} $attributes
     */
    public static function fromValidated(array $attributes): self
    {
        return new self(
            name: $attributes['name'] ?? null,
            address: $attributes['address'] ?? null,
            phoneNumber: $attributes['phone_number'] ?? null,
            nif: $attributes['nif'] ?? null,
            status: $attributes['status'] ?? null,
        );
    }
}
