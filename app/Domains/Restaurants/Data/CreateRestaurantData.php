<?php

namespace App\Domains\Restaurants\Data;

use Spatie\LaravelData\Data;

/**
 * Define os dados usados pelo caso de uso `CreateRestaurantData` no domínio de restaurantes; funciona como contrato interno entre validação e lógica de negócio.
 */
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
    ) {
    }

    /**
     * @param array{name: string, address: string, phone_number: string, nif: string, admin_name: string, admin_email: string, admin_password: string} $attributes
     */
    public static function fromValidated(array $attributes): self
    {
        return new self(
            name: $attributes['name'],
            address: $attributes['address'],
            phoneNumber: $attributes['phone_number'],
            nif: $attributes['nif'],
            adminName: $attributes['admin_name'],
            adminEmail: $attributes['admin_email'],
            adminPassword: $attributes['admin_password'],
        );
    }
}
